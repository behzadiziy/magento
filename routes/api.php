<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\StoresController;
use App\Http\Controllers\Api\V1\MagentoSyncController;
use App\Http\Controllers\Api\V1\AdminProductController;
use App\Http\Controllers\Api\V1\Tenant\OrderController;
use App\Http\Controllers\Api\V1\Tenant\ProductController;
use App\Http\Controllers\Api\V1\MagentoCustomerController;
use App\Http\Controllers\Api\V1\ProductIngestionController;
use App\Http\Controllers\Api\V1\Tenant\CmsBlockController;
use App\Http\Controllers\Api\V1\Tenant\UserAdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth.apikey')->post('/v1/products/ingest', [ProductIngestionController::class, 'store']);


Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::apiResource('products', AdminProductController::class)->only(['index', 'show', 'update']);
    //Route::post('magento/sync', [MagentoSyncController::class, 'sync']);

    Route::post('products/{product}/sync', MagentoSyncController::class);

    Route::post('stores', [StoresController::class, 'create']);
    //Route::post('stores/sync', [MagentoSyncController::class, 'sync']);
    //Route::get('/magento/customers', [MagentoCustomerController::class, 'getCustomers']);

    Route::apiResource('tenant/products', ProductController::class);
    Route::apiResource('tenant/orders', OrderController::class);
    Route::apiResource('tenant/cmsBlocks', CmsBlockController::class);
    Route::apiResource('tenant/userAdmin', UserAdminController::class);

});
