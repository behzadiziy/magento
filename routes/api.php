<?php
// routes/api.php

use App\Http\Controllers\Api\V1\Tenant\MagentoCustomerController;
use App\Http\Controllers\Api\V1\StoresController;

use App\Http\Controllers\Api\V1\Tenant\MagentoCategoryController;
use App\Http\Controllers\Api\V1\Tenant\StoreAddressController;
use App\Http\Controllers\Api\V1\Tenant\TenantAuthController;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
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

Route::middleware('auth.apikey')->post('/v1/products/ingest', [ProductIngestionController::class, 'store']);


Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::apiResource('products', AdminProductController::class)->only(['index', 'show', 'update']);
   Route::post('magento/sync', [MagentoSyncController::class, 'sync']);

    Route::post('products/{product}/sync', MagentoSyncController::class);

    Route::post('stores', [StoresController::class, 'create']);
 //   Route::post('stores/sync', [MagentoSyncController::class, 'sync']);
    Route::get('customers/group/{storeGroupId}', [MagentoCustomerController::class, 'getCustomersByStoreGroup']);


});

Route::middleware('auth:sanctum')->prefix('v1/admin/tenant')->group(function () {
    Route::prefix('categories')->group(function () {
        Route::get('{id}', [MagentoCategoryController::class, 'getCategory']);
        Route::post('/', [MagentoCategoryController::class, 'createCategory']);
        Route::put('{id}', [MagentoCategoryController::class, 'updateCategory']);
    });

    Route::post('/store_address', [StoreAddressController::class, 'store']);
});
Route::apiResource('tenant',TenantController::class);
Route::post('tenant/login', [TenantAuthController::class, 'login']);
Route::post('tenant/register', [TenantAuthController::class, 'register']);

