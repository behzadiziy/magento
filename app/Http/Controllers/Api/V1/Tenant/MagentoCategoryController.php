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
        $this->username = config('magento.username');
        $this->password = config('magento.password');
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

        $data = [
            'category' => [
                'name' => $request->name,
                'isActive' => $request->is_active ?? true,
                'parentId' => $request->parent_id ?? 2,
                'include_in_menu' => $request->include_in_menu ?? true,
                'description' => $request->description ?? '',
                'url_key' => $request->url_key ?? \Str::slug($request->name),
                'position' => $request->position ?? 0,
                'meta_title' => $request->meta_title ?? $request->name,
                'meta_keywords' => $request->meta_keywords ?? '',
                'meta_description' => $request->meta_description ?? '',
                'display_mode' => $request->display_mode ?? 'PRODUCTS_AND_PAGE',
                'is_anchor' => $request->is_anchor ?? 1,
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
}
