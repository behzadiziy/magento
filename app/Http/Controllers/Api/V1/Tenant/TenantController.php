<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenant,email',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
        ]);

        $tenant = new Tenant();

        $tenant->name= $validated['name'];
        $tenant->email= $validated['email'];
        $tenant->address= $validated['address'];
        $tenant->phone= $validated['phone'];

        $tenant->save();
        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant' => $tenant
        ], 201);

    }

    public function update(Request $request, $id) {

        $tenant= Tenant::findorFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
           // 'email' => 'required|email|unique:tenant,email',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
        ]);

        $tenant->update($validated);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant
        ]);


    }


    public function index() {
        return Tenant::all();
    }

    public function show($id) {
        return Tenant::findOrFail($id);
    }

    public function destroy($id) {
        Tenant::destroy($id);
        return response()->json(['message' => 'Tenant deleted']);
    }


}
