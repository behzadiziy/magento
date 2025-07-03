<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class MagentoSyncService
{
    public  $client;
    public  $apiUrl;
    public  $username;
    public  $password;
    public  $token = null;


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

    public function syncDataToMagento( $name,  $code)
    {
        $token = $this->getAccessToken();

        $websitePayload = [
            'website' => [
                'code' => $code,
                'name' => $name,
                'sort_order' => 0,
                'default_group_id' => 0,
                'is_default' => false,
            ],
        ];

        try {
            Log::info('Attempting to create Magento website', ['code' => $code]);
            $response = $this->client->post("{$this->apiUrl}/rest/V1/store/websites", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $websitePayload,
            ]);
            Log::info('Magento website creation response received.');
            $responseData = json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $body = (string) $e->getResponse()->getBody();
            // If website code already exists, log and continue
            if (str_contains($body, 'website with code') && str_contains($body, 'already exists')) {
                Log::warning("Website code '{$code}' already exists. Will fetch existing website ID.");
            } else {
                Log::error('Error creating Magento website.', ['error' => $e->getMessage(), 'body' => $body]);
                throw $e;
            }
        }

        // Fetch all websites to get the website ID
        try {
            Log::info('Fetching all websites to find created website ID');
            $getWebsitesResponse = $this->client->get("{$this->apiUrl}/rest/V1/store/websites", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $websites = json_decode($getWebsitesResponse->getBody(), true);
            Log::info('Fetched websites list from Magento', ['websites' => $websites]);

            $matchedWebsite = collect($websites)->firstWhere('code', $code);
            if (!$matchedWebsite) {
                throw new \Exception("Website with code '{$code}' not found after creation attempt.");
            }
            $websiteId = $matchedWebsite['id'];
            Log::info('Website ID found: ' . $websiteId);
        } catch (\Exception $e) {
            Log::error('Failed to fetch website ID from Magento.', ['error' => $e->getMessage()]);
            throw $e;
        }

        //   $uniqueSuffix = time() . '_' . rand(10, 20);
        $uniqueCode = strtolower(
                preg_replace('/[^a-z0-9_]/', '_', $name)
            ) . '_' . uniqid();
        $storeGroupName = 'StoreGroup_' . $uniqueCode;
        $storeGroupCode = strtolower(str_replace(' ', '_', $storeGroupName));


        // Create store group
        $storeGroup = $this->createStoreGroup($websiteId, $storeGroupName);
        $storeGroupId = $storeGroup['id'];
        Log::info('Store Group created with ID: ' . $storeGroupId);


        $storeViewCode = 'en_us_' . $uniqueCode;
        $storeViewName = 'English';

        $storeView = $this->createStoreView($websiteId, $storeGroupId, $storeViewCode, $storeViewName);
        Log::info('Store View created.', ['storeView' => $storeView]);

        return [
            'website' => $matchedWebsite,
            'store_group' => $storeGroup,
            'store_view' => $storeView,
        ];
    }

    public function createStoreGroup($websiteId, $name = 'aqaq', $rootCategoryId = 2)
    {
        $token = $this->getAccessToken();

        // Generate a unique, lowercase, underscore-safe code from the name (or you can pass it explicitly)
        $code = strtolower(str_replace(' ', '_', $name));

        $payload = [
            'storeGroup' => [
                'name' => $name,
                'code' => $code,
                'website_id' => $websiteId,
                'root_category_id' => $rootCategoryId,
                'default_store_id' => 1,
            ],
        ];

        try {
            Log::info('Sending store group payload to Magento', ['payload' => $payload]);

            $response = $this->client->post("{$this->apiUrl}/rest/V1/store/storeGroups", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody(), true);
            Log::info('Magento store group created successfully.', ['response' => $responseData]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Error creating store group in Magento.', ['error' => $e->getMessage()]);
            throw new \Exception('Error creating store group: ' . $e->getMessage());
        }
    }


    public function createStoreView($websiteId, $groupId, $code, $name, $sortOrder = 0, $storeId=null)
    {
        $token = $this->getAccessToken();

        $payload = [
            'store' => [
                'code' => $code,
                'name' => $name,
                'website_id' => $websiteId,
                'group_id' => $groupId,
                'is_active' => 1,
                'sort_order' => $sortOrder,
            ],
        ];

        try {
            $response = $this->client->post("{$this->apiUrl}/rest/V1/store/storeViews", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);

            if (!is_array($responseData)) {
                Log::error('Invalid store view response JSON.', ['response' => $responseBody]);
                throw new \Exception('Invalid response received from Magento store view API.');
            }

            Log::info('Magento store view created successfully.', ['response' => $responseData]);

            if (isset($responseData['code'])) {
                \App\Models\Stores::where('id', $storeId)->update([
                    'code' => $responseData['code']
                ]);
                Log::info("Store model updated with Magento store view code: {$responseData['code']}");
            }

            return $responseData;


        } catch (ClientException $e) {
            $body = (string) $e->getResponse()->getBody();
            Log::error('Magento store view API returned client error.', ['body' => $body]);
            throw new \Exception('Client error when creating store view: ' . $body);
        } catch (\Exception $e) {
            Log::error('Error creating store view in Magento.', ['error' => $e->getMessage()]);
            throw new \Exception('Error creating store view: ' . $e->getMessage());
        }
    }

    public function getStoreGroups()
    {
        $token = $this->getAccessToken();
        $response = $this->client->get("{$this->apiUrl}/rest/V1/store/storeGroups", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }


    public function getCustomersByWebsiteId($websiteId)
    {
        $token = $this->getAccessToken();

        $url = "{$this->apiUrl}/rest/V1/customers/search?" . http_build_query([
                'searchCriteria[filter_groups][0][filters][0][field]' => 'website_id',
                'searchCriteria[filter_groups][0][filters][0][value]' => $websiteId,
                'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
                'searchCriteria[pageSize]' => 100,
                'searchCriteria[currentPage]' => 1,
            ]);

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }


}
