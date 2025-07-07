<?php
namespace App\Services;

use App\Models\ShippingAddress;
use Illuminate\Support\Facades\Http;

class MagentoShippingService
{

    public function getShippingMethods($cartId, ShippingAddress $address)
    {

        $magento_user= new MagentoSyncService();
        $token= $magento_user->getAccessToken();
        $payload = [
            'address' => [
                'country_id' => $address->country_id,
                'postcode' => $address->postcode,
                'region' => $address->region,
                'region_code' => $address->region_code,
                'region_id' => $address->region_id,
                'city' => $address->city,
                'street' => $address->street,
            ]
        ];

        $url = "{$magento_user->apiUrl}/rest/V1/carts/{$cartId}/estimate-shipping-methods";

        $response = Http::withToken($token)->post($url, $payload);

        return $response->json();
    }
}
