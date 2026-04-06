<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders,id',
            'gateway' => 'nullable|in:manual,stripe,paypal,razorpay,square',
            'payment_method' => 'required|in:bank_transfer,cash_on_delivery,card,upi,wallet,cash,check',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'transaction_id' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,completed,failed,refunded',
            'payment_notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Selected order does not exist',
            'payment_method.required' => 'Payment method is required',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than 0',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default gateway to 'manual' if not provided
        if (!$this->has('gateway')) {
            $this->merge(['gateway' => 'manual']);
        }
        
        // Set default status to 'completed' for manual payments
        if ($this->gateway === 'manual' && !$this->has('status')) {
            $this->merge(['status' => 'completed']);
        }
    }
}
