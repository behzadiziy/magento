<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MagentoSyncController;
use App\Http\Controllers\Api\V1\AdminProductController;
use App\Http\Controllers\Api\V1\ProductIngestionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->post('/v1/products/ingest', [ProductIngestionController::class, 'store']);


Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::apiResource('products', AdminProductController::class)->only(['index', 'show', 'update']);
    Route::post('magento/sync', [MagentoSyncController::class, 'sync']);
});
