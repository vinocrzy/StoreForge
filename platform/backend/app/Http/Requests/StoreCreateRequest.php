<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash|unique:stores,slug',
            'domain' => 'nullable|string|max:255|unique:stores,domain',
            'status' => 'sometimes|string|in:active,inactive,suspended',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'currency' => 'sometimes|string|size:3',
            'timezone' => 'sometimes|string|max:100',
            'language' => 'sometimes|string|size:2',
            'settings' => 'sometimes|array',

            'admin_name' => 'required|string|max:255',
            'admin_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'admin_email' => 'nullable|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Store phone must be in E.164 format (e.g., +12025551234).',
            'admin_phone.required' => 'Store admin phone is required.',
            'admin_phone.regex' => 'Store admin phone must be in E.164 format (e.g., +12025551234).',
        ];
    }
}
