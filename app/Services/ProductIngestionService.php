<?php

namespace App\Services;

use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Support\Facades\DB;

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
        $batchSize = 50;

        DB::beginTransaction();

        try {
            collect($productsData)->chunk($batchSize)->each(function ($chunk) use (&$ingestedCount) {
                $productsToInsert = [];

                foreach ($chunk as $productData) {
                    $productsToInsert[] = [
                        'sku' => $productData['sku'],
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'description' => $productData['description'] ?? null,
                        'status' => ProductStatus::PendingReview,
                        'crawler_payload' => json_encode($productData),
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
}
