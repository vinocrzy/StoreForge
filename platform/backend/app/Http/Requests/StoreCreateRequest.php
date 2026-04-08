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

            'owner_name' => 'required|string|max:255',
            'owner_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'owner_email' => 'nullable|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:8|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Store phone must be in E.164 format (e.g., +12025551234).',
            'owner_phone.required' => 'Owner phone is required.',
            'owner_phone.regex' => 'Owner phone must be in E.164 format (e.g., +12025551234).',
        ];
    }
}
