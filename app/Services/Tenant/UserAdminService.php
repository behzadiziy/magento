<?php

namespace App\Services\Tenant;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class UserAdminService
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

    // Get or reuse access token
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

    public function storeAdminUser(array $userData): array
    {
        $token = $this->getAccessToken();

        Log::info('Creating admin user with data: ', $userData);

        $payload = [
            'user' => [
                'username'   => $userData['username'],
                'firstname'  => $userData['firstname'],
                'lastname'   => $userData['lastname'],
                'email'      => $userData['email'],
                'password'   => $userData['password'],
                'interface_locale' => 'en_US',
                'is_active'  => 1,
            ]
        ];


        try {
            $endpoint = "{$this->apiUrl}/rest/V1/users";

            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $createdUser =  json_decode($response->getBody(), true);

            return $createdUser;
        } catch (\Exception $e) {
            // Correctly access the nested username key
            Log::info("The user with the username '" . $payload['user']['username'] . "' could not be created.");

            // It's also highly recommended to log the actual error from Magento!
            Log::error("Magento API Error: " . $e->getMessage());

            throw $e;
        }
    }
}
