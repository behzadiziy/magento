<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MagentoSyncService;
use Illuminate\Http\Request;

class MagentoSyncStoresController extends Controller
{
    protected $magentoSyncService;

    public function __construct(MagentoSyncService $magentoSyncService)
    {
        $this->magentoSyncService = $magentoSyncService;
    }

    public function sync(Request $request)
    {
        // Example data structure, you can customize this based on your requirements
        $data = [
            'name' => $request->input('name'),
            'owner_id' => $request->input('owner_id'),
            'domain_name' => $request->input('domain_name'),
            'store_category' => $request->input('store_category'),
            'registration_date' => $request->input('registration_date'),
            'expiration_date' => $request->input('expiration_date'),
            'status' => $request->input('status'),
        ];

        // Call MagentoSyncService to sync the data with Magento
        $response = $this->magentoSyncService->syncDataToMagento($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data synced with Magento successfully.',
            'response' => $response,
        ], 200);
    }
}
