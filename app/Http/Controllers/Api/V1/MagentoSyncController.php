<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Http\Request;
use App\Jobs\SyncProductToMagento;
use App\Http\Controllers\Controller;

class MagentoSyncController extends Controller
{
    public function sync()
    {
        $productsToSync = Product::where('status', ProductStatus::Approved)->get();

        if ($productsToSync->isEmpty()) {
            return response()->json(['message' => 'No approved products to sync.'], 200);
        }

        foreach ($productsToSync as $product) {
            SyncProductToMagento::dispatch($product);
        }

        return response()->json([
            'message' => 'Synchronization process has been initiated for ' . $productsToSync->count() . ' products.'
        ], 202);
    }
}
