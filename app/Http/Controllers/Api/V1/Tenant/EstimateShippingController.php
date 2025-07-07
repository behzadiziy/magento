<?php
namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use App\Services\MagentoShippingService;
use Illuminate\Http\Request;

class EstimateShippingController extends Controller
{
    protected $magento;

    public function __construct(MagentoShippingService $magento)
    {
        $this->magento = $magento;
    }

    public function estimate(Request $request, $cartId)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
        ]);

        $address = ShippingAddress::findOrFail($request->shipping_address_id);
        $methods = $this->magento->getShippingMethods($cartId, $address);

        return response()->json($methods);
    }
}
