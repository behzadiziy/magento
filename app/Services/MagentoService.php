<?php

namespace App\Services;

use App\Models\AttributeMapping;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class MagentoService
{
    private string $baseUrl;
    private string $accessToken;
    private MagentoAttributeManager $attributeManager;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.magento.base_url'), '/');
        $this->accessToken = config('services.magento.access_token');

        if (!$this->baseUrl || !$this->accessToken) {
            throw new Exception('Magento service credentials are not configured.');
        }

        // Initialize the manager with the base URL and token
        $this->attributeManager = new MagentoAttributeManager($this->baseUrl, $this->accessToken);
    }

    public function createOrUpdateProduct(Product $product): array
    {
        $sku = rawurlencode($product->sku);
        $productExists = $this->productExists($sku);
        $attributeSetId = 4; // Your default attribute set ID

        $payload = [
            'product' => [
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'status' => 1,
                'visibility' => 4,
                'type_id' => 'simple',
                'attribute_set_id' => $attributeSetId,
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => $product->stock_quantity,
                        'is_in_stock' => $product->stock_quantity > 0,
                    ],
                ],
                'custom_attributes' => $this->handleCustomAttributes($product, $attributeSetId),
                //'media_gallery_entries' => $this->handleMediaGallery($product),
            ],
        ];

        $endpoint = $productExists ? "/rest/V1/products/{$sku}" : '/rest/V1/products';
        $method = $productExists ? 'put' : 'post';

        if ($productExists) {
            $payload['saveOptions'] = true;
        }

        return $this->makeApiRequest($method, $endpoint, $payload);
    }

    // In app/Services/MagentoService.php

    private function handleCustomAttributes(Product $product, int $attributeSetId): array
    {
        $magentoAttributes = [
            ['attribute_code' => 'description', 'value' => $product->description ?? ''],
        ];

        foreach ($product->attributes as $label => $value) {
            if (empty($label) || is_null($value) || $value === '') {
                continue;
            }

            $trimmedLabel = trim($label);

            // Step 1: Find the mapping. DO NOT create it here anymore.
            $mapping = AttributeMapping::where('source_label', $trimmedLabel)->first();

            // Step 2: If the mapping doesn't exist or isn't complete, skip.
            if (!$mapping || !$mapping->is_mapped || empty($mapping->magento_attribute_code)) {
                Log::info("Skipping attribute '{$trimmedLabel}' for product SKU '{$product->sku}'. It is unmapped or pending review.");
                continue;
            }

            // Step 3: The mapping is complete. Use it.
            $magentoCode = $mapping->magento_attribute_code;
            $magentoType = $mapping->magento_attribute_type;

            if ($magentoType === 'select') {
                $optionId = $this->attributeManager->getOrCreateOptionId(
                    $magentoCode,
                    (string)$value,
                    $attributeSetId,
                    $trimmedLabel,
                    $magentoType
                );
                if ($optionId) {
                    $magentoAttributes[] = ['attribute_code' => $magentoCode, 'value' => $optionId];
                }
            } else { // Handles 'text', 'textarea'
                $this->attributeManager->ensureAttributeExists(
                    $magentoCode,
                    $attributeSetId,
                    $trimmedLabel,
                    $magentoType
                );
                $magentoAttributes[] = ['attribute_code' => $magentoCode, 'value' => (string)$value];
            }
        }
        return $magentoAttributes;
    }

    private function productExists(string $sku): bool
    {
        try {
            $product = $this->makeApiRequest('get', "/rest/V1/products/{$sku}", [], true);
            return !is_null($product);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function handleMediaGallery(Product $product): array
    {
        $mediaEntries = [];
        if (empty($product->images)) {
            return [];
        }

        foreach ($product->images as $index => $relativeImagePath) {
            try {
                // Convert the relative storage path to a full, public URL
                $fullImageUrl = Storage::url($relativeImagePath);

                $imageContent = @file_get_contents($fullImageUrl);
                if ($imageContent) {
                    $mediaEntries[] = [
                        'media_type' => 'image',
                        'label' => $product->name . ' - Image ' . ($index + 1),
                        'position' => $index + 1,
                        'disabled' => false,
                        'types' => ($index === 0) ? ['image', 'small_image', 'thumbnail'] : [],
                        'content' => [
                            'base64_encoded_data' => base64_encode($imageContent),
                            'type' => 'image/jpeg',
                            'name' => "{$product->sku}-{$index}.jpg",
                        ],
                    ];
                } else {
                    Log::warning("file_get_contents failed to download image for product {$product->sku} from URL: {$fullImageUrl}");
                }
            } catch (Exception $e) {
                Log::warning("Could not process image for product {$product->sku}: {$relativeImagePath}. Error: " . $e->getMessage());
                continue;
            }
        }
        return $mediaEntries;
    }

    private function makeApiRequest(string $method, string $endpoint, array $payload = [], bool $ignoreNotFound = false): mixed
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(120) // Increased timeout for slow Magento APIs
            ->acceptJson()
            ->withToken($this->accessToken)
            ->$method($endpoint, $payload);

        if ($ignoreNotFound && $response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            $errorDetails = $response->json() ?? ['raw_response' => $response->body()];
            Log::error('Magento API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'message' => $response->json('message', 'Unknown error.'),
                'details' => $errorDetails
            ]);

            throw new Exception(
                'Magento API Error: ' . $response->json('message', 'Unknown error.'),
                $response->status()
            );
        }

        return $response->json();
    }
}
