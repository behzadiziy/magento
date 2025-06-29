<?php
// routes/api.php

<<<<<<< HEAD
use App\Http\Controllers\Api\V1\StoresController;
=======
>>>>>>> 7b98e8e6aebf847768cf0a55b85e4b069b4aced1
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
    //Route::post('magento/sync', [MagentoSyncController::class, 'sync']);

    Route::post('products/{product}/sync', MagentoSyncController::class);
<<<<<<< HEAD
    Route::post('stores', [StoresController::class, 'create']);
    Route::post('stores/sync', [MagentoSyncController::class, 'sync']);
=======
>>>>>>> 7b98e8e6aebf847768cf0a55b85e4b069b4aced1

});
