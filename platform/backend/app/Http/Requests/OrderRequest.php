<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
        $rules = [
            'customer_id' => 'required|integer|exists:customers,id',
            'status' => 'nullable|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded,partially_refunded',
            'currency' => 'nullable|string|size:3',
            'customer_note' => 'nullable|string|max:500',
            'admin_note' => 'nullable|string|max:500',
            'payment_method' => 'nullable|in:manual,bank_transfer,cash_on_delivery,card,upi,wallet',
            'billing_address_id' => 'nullable|integer|exists:customer_addresses,id',
            'shipping_address_id' => 'nullable|integer|exists:customer_addresses,id',
            'coupon_code' => 'nullable|string|max:50',
            'shipping_amount' => 'nullable|numeric|min:0',
            
            // Order items (required for creating new orders)
            'items' => 'required_without:customer_id|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:1',
        ];
        
        // For update requests, make customer_id and items optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['customer_id'] = 'sometimes|integer|exists:customers,id';
            $rules['items'] = 'sometimes|array|min:1';
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'items.required' => 'Order must have at least one item',
            'items.*.product_id.required' => 'Product ID is required for each item',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }
}
