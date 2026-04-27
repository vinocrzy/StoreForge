<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|in:flat_rate,weight_based,free_above,local_pickup',
            'rate'          => 'nullable|numeric|min:0',
            'free_above'    => 'nullable|numeric|min:0',
            'config'        => 'nullable|array',
            'is_active'     => 'sometimes|boolean',
            'display_order' => 'sometimes|integer|min:0',
        ];
    }
}
