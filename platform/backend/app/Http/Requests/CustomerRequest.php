<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
        $customerId = $this->route('customer');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $storeId = tenant()->id;

        return [
            // Basic Information
            'first_name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'last_name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            
            // Phone (required, unique per store, E.164 format)
            'phone' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'regex:/^\+[1-9]\d{1,14}$/', // E.164 format: +12025551234
                Rule::unique('customers')->where(function ($query) use ($storeId) {
                    return $query->where('store_id', $storeId);
                })->ignore($customerId),
            ],
            
            // Email (optional, unique per store if provided)
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->where(function ($query) use ($storeId) {
                    return $query->where('store_id', $storeId);
                })->ignore($customerId),
            ],
            
            // Password (required for creation, optional for update)
            'password' => $isUpdate
                ? 'sometimes|string|min:8|max:255'
                : 'required|string|min:8|max:255',
            
            // Status
            'status' => 'sometimes|string|in:active,inactive,banned',
            
            // Optional Personal Information
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            
            // Verification flags (admin only)
            'email_verified_at' => 'nullable|date',
            'phone_verified_at' => 'nullable|date',
            
            // Address (nested, optional)
            'address' => 'sometimes|array',
            'address.type' => 'required_with:address|string|in:billing,shipping,both',
            'address.label' => 'nullable|string|max:50',
            'address.first_name' => 'required_with:address|string|max:255',
            'address.last_name' => 'required_with:address|string|max:255',
            'address.company' => 'nullable|string|max:255',
            'address.address_line1' => 'required_with:address|string|max:255',
            'address.address_line2' => 'nullable|string|max:255',
            'address.city' => 'required_with:address|string|max:100',
            'address.state_province' => 'required_with:address|string|max:100',
            'address.postal_code' => 'required_with:address|string|max:20',
            'address.country' => 'required_with:address|string|size:2', // ISO 3166-1 alpha-2
            'address.phone' => [
                'required_with:address',
                'string',
                'regex:/^\+[1-9]\d{1,14}$/', // E.164 format
            ],
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone' => 'phone number',
            'email' => 'email address',
            'date_of_birth' => 'date of birth',
            'address.type' => 'address type',
            'address.first_name' => 'address first name',
            'address.last_name' => 'address last name',
            'address.address_line1' => 'street address',
            'address.address_line2' => 'apartment/suite',
            'address.city' => 'city',
            'address.state_province' => 'state/province',
            'address.postal_code' => 'postal code',
            'address.country' => 'country',
            'address.phone' => 'address phone',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required for customer accounts.',
            'phone.regex' => 'Phone number must be in E.164 format (e.g., +12025551234).',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required for new customers.',
            'password.min' => 'Password must be at least 8 characters.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'address.type.in' => 'Address type must be billing, shipping, or both.',
            'address.phone.regex' => 'Address phone must be in E.164 format (e.g., +12025551234).',
            'address.country.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2).',
        ];
    }
}
