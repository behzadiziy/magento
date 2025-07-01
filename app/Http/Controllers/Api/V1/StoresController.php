<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SyncStoreDataToMagento;
use App\Models\Stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoresController extends Controller
{

    protected function generateCode($name)
    {
        $code = strtolower($name);
        $code = preg_replace('/[^a-z0-9]+/', '_', $code);
        $code = trim($code, '_');
        return substr($code, 0, 32);
    }


    public function create(Request $request)
    {
        // Update validation to match the new columns
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_id' => 'required|integer|exists:users,id',
            'domain_name' => 'required|url',
            'store_category' => 'required|string|max:255',
            'registration_date' => 'required|date',
            'expiration_date' => 'required|date',
            'is_active' => 'required|boolean',
            'group_id' => 'required|integer',
            'website_id' => 'required|integer',
            'sort_order' => 'nullable|integer',
            'code' => 'required|string|max:255',
        ]);



        $data = new Stores();

        $data->name = $validated['name'];
        $data->code= $this->generateCode($data->name);
        $data->owner_id = $validated['owner_id'] = Auth::id();
        $data->domain_name = $validated['domain_name'];
        $data->store_category = $validated['store_category'];
        $data->registration_date = $validated['registration_date'];
        $data->expiration_date = $validated['expiration_date'];
        $data->is_active = $validated['is_active'];
        $data->group_id = $validated['group_id'];
        $data->website_id = $validated['website_id'];
        $data->sort_order = isset($validated['sort_order']) ? $validated['sort_order'] : 0;
      //  $data->code = $validated['code'];

      //  $data->website_code = $validated['website_code'];

        \Log::info('Validated Data:', $validated);

        $data->save();
        \Log::info('Website Saved.');

        SyncStoreDataToMagento::dispatch($data->name, $data->code);



        return response()->json([
            'status' => 'success',
            'message' => 'Website created successfully.',
          //  'data' => $data,
        ], 201);
    }
}
