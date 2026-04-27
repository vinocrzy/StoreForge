<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_enabled'           => 'sometimes|boolean',
            'tax_rate'              => 'sometimes|numeric|min:0|max:100',
            'tax_display'           => 'sometimes|string|in:inclusive,exclusive',
            'tax_label'             => 'sometimes|string|max:50',
            'category_tax_rates'    => 'sometimes|array',
            'category_tax_rates.*'  => 'numeric|min:0|max:100',
        ];
    }
}
