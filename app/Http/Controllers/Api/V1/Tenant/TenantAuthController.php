<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
  public function register(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenant,email',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
            'password' => 'required|string|min:8|confirmed', // requires password_confirmation
        ]);


        $tenant = Tenant::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'address'  => $validated['address'] ?? null,
            'status'   => $validated['status'] ?? 'pending',
            'password' => Hash::make($validated['password']),
        ]);


        $token = $tenant->createToken('TenantToken')->plainTextToken;

        return response()->json([
            'message' => 'Tenant registered successfully',
            'token'   => $token,
            'tenant'  => $tenant
        ], 201);
    }


}
