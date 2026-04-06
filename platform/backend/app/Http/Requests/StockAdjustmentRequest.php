<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
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
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|string|in:purchase,sale,return,adjustment,damage,lost',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'variant_id' => 'product variant',
            'warehouse_id' => 'warehouse',
            'quantity' => 'quantity',
            'type' => 'movement type',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required for stock adjustment.',
            'product_id.exists' => 'The selected product does not exist.',
            'variant_id.exists' => 'The selected product variant does not exist.',
            'warehouse_id.required' => 'Warehouse is required for stock adjustment.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'type.required' => 'Movement type is required.',
            'type.in' => 'Invalid movement type. Must be: purchase, sale, return, adjustment, damage, or lost.',
        ];
    }
}
