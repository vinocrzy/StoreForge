<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number'       => 'required|string|max:255',
            'tracking_carrier'      => 'nullable|string|max:100',
            'tracking_url'          => 'nullable|url|max:500',
            'estimated_delivery_at' => 'nullable|date',
        ];
    }
}
