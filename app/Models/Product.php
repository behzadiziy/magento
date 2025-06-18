<?php

namespace App\Models;

use App\Enums\ProductStatus;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;


    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'status',
        'crawler_payload',
        'magento_product_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'crawler_payload' => 'array',
        'status' => ProductStatus::class
    ];
}
