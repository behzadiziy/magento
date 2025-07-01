<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TenantAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tenant,email',
            'password' => 'required',
        ]);

        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant || !Hash::check($request->password, $tenant->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $tenant->createToken('TenantToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'tenant' => $tenant
        ]);
    }
}
