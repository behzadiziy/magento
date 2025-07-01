<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Services\MagentoSyncService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class MagentoCategoryController extends Controller
{
    protected $client;
    protected $syncService;

    public function __construct(MagentoSyncService $syncService)
    {
        $this->client = new Client();
        $this->syncService = $syncService;

    }
    public function getAccessToken()
    {

        if ($this->token) {
            return $this->token;
        }

        $response = $this->client->post("{$this->syncService->apiUrl}/rest/V1/integration/admin/token", [
            'json' => [
                'username' => $this->syncService->username,
                'password' => $this->syncService->password,
            ],
        ]);
        $this->token = json_decode($response->getBody(), true);
        return $this->token;
    }


    public function getCategory($id)
    {

        $token = $this->syncService->getAccessToken();
        $url="{$this->syncService->apiUrl}/rest/V1/categories/{$id}";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
    }


    public function createCategory(Request $request)
    {
        $token = $this->syncService->getAccessToken();

        $storeCode = $request->input('store_code', 'default');
        $rootCategoryId = $this->getRootCategoryByStoreCode($storeCode);

        if (!$rootCategoryId) {
            return response()->json(['error' => 'Invalid store code or root category not found'], 400);
        }

        $name = $request->input('name');
        if (!$name) {
            return response()->json(['error' => 'The "name" field is required.'], 422);
        }

        $request->validate([
            'store_code' => 'required|string',
            'name' => 'required|string|max:255',
        ]);


        $data = [
            'category' => [
                'name' => $request->input('name'),
                'is_active' => true,
                'parent_id' => $rootCategoryId,
                'include_in_menu' => true,
            ]
        ];

        \Log::info('data is'.json_encode($data, JSON_PRETTY_PRINT));


        $response = $this->client->post("{$this->syncService->apiUrl}/rest/V1/categories", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());

    }



    public function updateCategory(Request $request, $id)
    {
        $token = $this->syncService->getAccessToken();
        $data = [
            'category' => [
                'id' => $id,
                'name' => $request->name,
                'isActive' => $request->is_active,
                'include_in_menu' => $request->include_in_menu ?? true,
            ]
        ];

        $response = $this->client->put("{$this->syncService->apiUrl}/rest/V1/categories/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);


        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
    }


    protected function getRootCategoryByStoreCode(string $storeCode): ?int
    {

        $service= new MagentoSyncService();
        $storeGroups = $service->getStoreGroups();


        $storeList = [
            'admin' => 1,
            'en_us' => 2,
            'en' => 3
        ];

        $groupId = $storeList[$storeCode] ?? null;

        if (!$groupId) return null;


        $group = collect($storeGroups)->firstWhere('id', $groupId);

        return $group['root_category_id'] ?? null;
    }

}
