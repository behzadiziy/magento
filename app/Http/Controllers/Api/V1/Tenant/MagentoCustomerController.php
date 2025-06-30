<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Services\MagentoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MagentoCustomerController extends Controller
{
    public function getCustomersByStoreGroup($storeGroupId)
    {
        $service = new MagentoSyncService();

        $storeGroups = $service->getStoreGroups();
        \Log::debug('Fetched store groups', ['storeGroups' => $storeGroups]);

        $targetGroup = collect($storeGroups)->firstWhere('id', (int)$storeGroupId);
        \Log::debug('Selected target group', ['targetGroup' => $targetGroup]);

        if (!$targetGroup) {
            return response()->json(['error' => 'Store group not found'], 404);
        }

        $websiteId = $targetGroup['website_id'];
        $customers = $service->getCustomersByWebsiteId($websiteId);

        return response()->json($customers);
    }

}
