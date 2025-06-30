<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Services\MagentoSyncService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class MagentoCategoryController extends Controller
{
    protected  $client;
    protected  $apiUrl;
    protected  $username;
    protected  $password;
    protected  $token = null;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('magento.api_url');
        $this->username = config('magento.api_username');
        $this->password = config('magento.api_password');

    }

    public function getAccessToken()
    {

        if ($this->token) {
            return $this->token;
        }

        $response = $this->client->post("{$this->apiUrl}/rest/V1/integration/admin/token", [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);
        $this->token = json_decode($response->getBody(), true);
        return $this->token;
    }


    public function getCategory($id)
    {

        $token = $this->getAccessToken();
        $url="{$this->apiUrl}/rest/V1/categories/{$id}";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return response()->json($response->json(), $response);
    }


    public function createCategory(Request $request)
    {
        $token = $this->getAccessToken();

        $storeCode = $request->input('store_code', 'default');
        $rootCategoryId = $this->getRootCategoryByStoreCode($storeCode);

        if (!$rootCategoryId) {
            return response()->json(['error' => 'Invalid store code or root category not found'], 400);
        }

        $data = [
            'category' => [
                'name' => $request->name,
                'isActive' => $request->input('is_active', true),
                'parentId' => $rootCategoryId,
                'include_in_menu' => $request->input('include_in_menu', true),
                'url_key' => $request->input('url_key', \Str::slug($request->name)),
                'position' => $request->input('position', 0),
                'meta_title' => $request->input('meta_title', $request->name),
                'display_mode' => $request->input('display_mode', 'PRODUCTS_AND_PAGE'),
                'is_anchor' => $request->input('is_anchor', 1),

                'custom_attributes' => [
                    [
                        'attribute_code' => 'description',
                        'value' => $request->input('description', ''),
                    ],
                    [
                        'attribute_code' => 'meta_keywords',
                        'value' => $request->input('meta_keywords', ''),
                    ],
                    [
                        'attribute_code' => 'meta_description',
                        'value' => $request->input('meta_description', ''),
                    ]
                ]
            ]
        ];

        $response = $this->client->post("{$this->apiUrl}/rest/V1/categories", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return response()->json($response->json(), $response->status());
    }



    public function updateCategory(Request $request, $id)
    {
        $token = $this->getAccessToken();
        $data = [
            'category' => [
                'id' => $id,
                'name' => $request->name,
                'isActive' => $request->is_active,
                'include_in_menu' => $request->include_in_menu ?? true,
            ]
        ];

        $response = $this->client->put("{$this->apiUrl}/rest/V1/categories/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);


        return response()->json($response->json(), $response->status());
    }


    protected function getRootCategoryByStoreCode(string $storeCode): ?int
    {

        $service= new MagentoSyncService();
        $storeGroups = $service->getStoreGroups();


        $storeList = [
            'default' => 1,     // store_code => store_group_id
            'fr_store' => 2,
            'us_store' => 3
        ];

        $groupId = $storeList[$storeCode] ?? null;

        if (!$groupId) return null;


        $group = collect($storeGroups)->firstWhere('id', $groupId);

        return $group['root_category_id'] ?? null;
    }

}
