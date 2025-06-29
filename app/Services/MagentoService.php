<?php
namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class MagentoService
{
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct()
    {

        $this->baseUrl = Config::get('services.magento.base_url');
        $this->accessToken = Config::get('services.magento.access_token');

        if (!$this->baseUrl || !$this->accessToken) {
            throw new Exception('Magento service credentials (base_url or access_token) are not configured.');
        }
    }

    /**
     * Creates or updates a product in Magento 2 using an Integration Access Token.
     *
     * @throws Exception
     */
    public function createOrUpdateProduct(Product $product): array
    {
        $endpoint = '/rest/V1/products';

        $payload = [
            'product' => [
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'status' => 1, // 1 = Enabled
                'visibility' => 4, // Catalog, Search
                'type_id' => 'simple',
                'attribute_set_id' => 4, // Default
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => $product->stock_quantity,
                        'is_in_stock' => $product->stock_quantity > 0,
                    ],
                ],
                'custom_attributes' => [
                    ['attribute_code' => 'description', 'value' => $product->description],
                ],
            ],
        ];


        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->accessToken)
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new Exception(
                'Magento API Error: ' . $response->json('message', 'Unknown error.'),
                $response->status()
            );
        }

        return $response->json();
    }


}
