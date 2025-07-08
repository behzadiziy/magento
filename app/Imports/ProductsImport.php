<?php

namespace App\Imports;

use App\Models\Product;
use App\Enums\ProductStatus;
use App\Models\CategoryMapping;
use App\Models\AttributeMapping;
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


            // --- NEW LOGIC FOR IMAGES ---
            $imagesString = $row['images'] ?? null;
            $imagesArray = []; // Default to an empty array

            if (!empty($imagesString)) {
                // The string from the cell is already JSON. We just need to decode it.
                // The `?? []` ensures that if decoding fails, we get an empty array.
                $imagesArray = json_decode($imagesString, true) ?? [];
            }
            // --- END OF NEW LOGIC ---

            // --- NEW: Handle Attribute Mapping ---
            if (!empty($attributesArray)) {
                foreach (array_keys($attributesArray) as $label) {
                    $trimmedLabel = trim($label);
                    if (!empty($trimmedLabel)) {
                        AttributeMapping::firstOrCreate(
                            ['source_label' => $trimmedLabel],
                            ['is_mapped' => false]
                        );
                    }
                }
            }

            // --- NEW: Handle Category Mapping ---
            $categoryName = $row['category'] ?? null;
            if (!empty($categoryName)) {
                $trimmedCategory = trim($categoryName);
                CategoryMapping::firstOrCreate(
                    ['source_name' => $trimmedCategory],
                    ['is_mapped' => false]
                );
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
            'images'            => $imagesArray,
        ]);
    }
}
