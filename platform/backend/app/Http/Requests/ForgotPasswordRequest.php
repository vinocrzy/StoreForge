<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string', // Accept phone or email
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Please provide your phone number or email address.',
        ];
    }
}
