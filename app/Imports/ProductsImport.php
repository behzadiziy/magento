<?php

namespace App\Imports;

use App\Models\Product;
use App\Enums\ProductStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        if (!isset($row['sku']) || empty($row['sku'])) {
            return null;
        }


        $attributesString = $row['attributes'] ?? null;
        $attributesArray = []; // Default to an empty array

        if (!empty($attributesString)) {
            // Replace single quotes with double quotes for valid JSON
            $jsonString = str_replace("'", '"', $attributesString);
            // Decode the JSON string into a PHP array
            $attributesArray = json_decode($jsonString, true);

            // If decoding fails, default to an empty array to prevent errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                $attributesArray = [];
            }
        }

        return Product::firstOrNew(['sku' => $row['sku']], [
            'name'              => $row['name'],
            'description'       => $row['description'] ?? null,
            'price'             => $row['price'],
            'stock_quantity'    => $row['stock_quantity'],
            'status'            => ProductStatus::PendingReview,
            'category'          => $row['category'] ?? null,
            'brand'             => $row['brand'] ?? null,
            'source_url'        => $row['source_url'] ?? null,
            'attributes'        => $attributesArray,
        ]);
    }
}
