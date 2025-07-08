<?php

namespace App\Services\Tenant;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class CmsBlockService
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

    protected function getAccessToken()
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


    public function createCmsBlock(array $cmsBlock)
    {

        $token = $this->getAccessToken();

        $endpoint = "{$this->apiUrl}/rest/V1/cmsBlock";

        Log::info('Creating CMS Block in Magento for Store ID: ' . $cmsBlock['store_id']);

        $payload = [
            'block' => [
                'identifier' => $cmsBlock['identifier'],
                'title' => $cmsBlock['title'],
                'content' => $cmsBlock['content'],
                'active' => $cmsBlock['is_active'],
            ]
        ];

        Log::info('Payload for CMS Block creation: ' . json_encode($payload));

        try {

            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to create CMS Block in Magento for Store ID');
            throw $e;
        }
    }

    public function getCmsBlocks()
    {
        $token = $this->getAccessToken();

        $endpoint = "{$this->apiUrl}/rest/V1/cmsBlock";


        $searchCriteria = [
            'searchCriteria' => [
                'sortOrders' => [
                    [
                        'field' => 'identifier',
                        'direction' => 'ASC'
                    ]
                ]
            ]
        ];

        $endpoint = "{$this->apiUrl}/rest/V1/cmsBlock/search?" . http_build_query($searchCriteria);

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to fetch CMS Blocks from Magento: ' . $e->getMessage());
            throw $e;
        }
    }


    public function deleteCmsByBlock(int $blockId)
    {
        $token = $this->getAccessToken();

        $endpoint = "{$this->apiUrl}/rest/V1/cmsBlock";

        try {
            $response = $this->client->delete("{$endpoint}/{$blockId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $ex) {
            Log::error('Failed to delete CMS Block with ID ' . $blockId . ': ' . $ex->getMessage());
            throw $ex;
        }
    }

    public function getCmsBlockById(int $blockId)
    {
        $token = $this->getAccessToken();

        $endpoint = "{$this->apiUrl}/rest/V1/cmsBlock";

        try {
            $response = $this->client->get("{$endpoint}/{$blockId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $ex) {
            Log::error('Failed to get CMS Block with ID ' . $blockId . ': ' . $ex->getMessage());
            throw $ex;
        }
    }
}
