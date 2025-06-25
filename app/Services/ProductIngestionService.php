<?php

namespace App\Services;

use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductIngestionService
{
    /**
     * Ingest products into the database using chunking.
     *
     * @param array $productsData
     * @return int
     */
    public function ingestProducts(array $productsData): int
    {
        $ingestedCount = 0;
        $batchSize = 500;

        DB::beginTransaction();

        try {
            collect($productsData)->chunk($batchSize)->each(function ($chunk) use (&$ingestedCount) {
                $productsToInsert = [];

                foreach ($chunk as $productData) {
                    // Handle image processing
                    if (isset($productData['images']) && is_array($productData['images'])) {
                        $imagePaths = [];
                        foreach ($productData['images'] as $imageUrl) {
                            // Check if the image URL is valid and download it if necessary
                            $imagePath = $this->downloadImage($imageUrl, $productData['sku']);
                            if ($imagePath) {
                                $imagePaths[] = $imagePath;
                            }
                        }
                        // Save images as JSON array in the database
                        $productData['images'] = $imagePaths;
                    }

                    // Prepare product data for insertion
                    $productsToInsert[] = [
                        'sku'              => $productData['sku'],
                        'name'             => $productData['name'],
                        'price'            => $productData['price'],
                        'description'      => $productData['description'] ?? null,
                        'status'           => ProductStatus::PendingReview,  // Default status
                        'images'           => json_encode($productData['images'] ?? []), // Save images as JSON
                        'category'         => $productData['category'] ?? null,
                        'brand'            => $productData['brand'] ?? null,
                        'stock_quantity'   => $productData['stock_quantity'] ?? 0,
                        'source_url'       => $productData['source_url'] ?? null,
                        'attributes'       => json_encode($productData['attributes']) ?? null
                    ];
                }

                if (!empty($productsToInsert)) {
                    Product::insert($productsToInsert);
                    $ingestedCount += count($productsToInsert);
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $ingestedCount;
    }

    /**
     * Download the product image and store it in the public storage.
     *
     * @param string $imageUrl
     * @param string $sku
     * @return string|null
     */
    private function downloadImage(string $imageUrl, string $sku): ?string
    {
        try {
            // Get the image content from the URL
            $imageContent = file_get_contents($imageUrl);  // Using file_get_contents to get image content
            if (!$imageContent) {
                return null; // If the image is not accessible, return null
            }

            // Get the image file extension from the URL
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            $imageName = "{$sku}-" . uniqid() . ".{$extension}";  // Generate unique image name
            $imagePath = "products/{$imageName}";  // Save in the 'products' directory

            // Store the image in the public storage
            Storage::disk('public')->put($imagePath, $imageContent);

            // Return the public URL of the image
            return $imagePath;;
        } catch (\Exception $e) {
            // Log the error if needed
            return null;  // Return null if image download fails
        }
    }
}
