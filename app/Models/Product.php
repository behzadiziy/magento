<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'status',
        'sync_error_message',
        'crawler_payload',
        'magento_product_id',
    ];

    protected $casts = [
        'crawler_payload' => 'array',
        'status' => ProductStatus::class,
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];
}
