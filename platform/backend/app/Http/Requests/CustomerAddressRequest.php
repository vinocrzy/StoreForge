<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            // Address Type
            'type' => $isUpdate ? 'sometimes|string|in:billing,shipping,both' : 'required|string|in:billing,shipping,both',
            
            // Label (optional)
            'label' => 'nullable|string|max:50', // Home, Office, etc.
            
            // Recipient Information
            'first_name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'last_name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            
            // Address Details
            'address_line1' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'state_province' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'postal_code' => $isUpdate ? 'sometimes|string|max:20' : 'required|string|max:20',
            'country' => $isUpdate ? 'sometimes|string|size:2' : 'required|string|size:2', // ISO 3166-1 alpha-2
            
            // Contact
            'phone' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'regex:/^\+[1-9]\d{1,14}$/', // E.164 format
            ],
            
            // Default flag
            'is_default' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'type' => 'address type',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'company' => 'company name',
            'address_line1' => 'street address',
            'address_line2' => 'apartment/suite',
            'city' => 'city',
            'state_province' => 'state/province',
            'postal_code' => 'postal code',
            'country' => 'country',
            'phone' => 'phone number',
            'is_default' => 'default address',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Address type is required (billing, shipping, or both).',
            'type.in' => 'Address type must be billing, shipping, or both.',
            'phone.required' => 'Phone number is required for address.',
            'phone.regex' => 'Phone number must be in E.164 format (e.g., +12025551234).',
            'country.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2).',
            'postal_code.required' => 'Postal code is required.',
        ];
    }
}
