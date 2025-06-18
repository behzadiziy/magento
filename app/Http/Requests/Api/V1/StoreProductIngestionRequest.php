<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductIngestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products'               => ['required', 'array', 'min:1'],
            'products.*.sku'         => ['required', 'string', 'distinct', 'unique:products,sku'],
            'products.*.name'        => ['required', 'string', 'max:255'],
            'products.*.price'       => ['required', 'numeric', 'min:0'],
            'products.*.description' => ['nullable', 'string'],
        ];
    }
}
