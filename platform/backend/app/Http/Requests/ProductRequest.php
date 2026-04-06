<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        $productId = $this->route('product');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'sku' => 'sometimes|nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => ($isUpdate ? 'sometimes|' : 'required|') . 'numeric|min:0|max:999999.99',
            'compare_price' => 'nullable|numeric|min:0|max:999999.99',
            'cost_price' => 'nullable|numeric|min:0|max:999999.99',
            'track_inventory' => 'sometimes|boolean',
            'stock_quantity' => 'sometimes|integer|min:0',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'sometimes|string|in:kg,g,lb,oz',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'required_with:dimensions|numeric|min:0',
            'dimensions.width' => 'required_with:dimensions|numeric|min:0',
            'dimensions.height' => 'required_with:dimensions|numeric|min:0',
            'dimensions.unit' => 'required_with:dimensions|string|in:cm,m,in,ft',
            'status' => 'sometimes|string|in:draft,active,archived',
            'is_featured' => 'sometimes|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'category_ids' => 'categories',
            'stock_quantity' => 'stock',
            'low_stock_threshold' => 'low stock alert threshold',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'price.required' => 'Product price is required.',
            'price.min' => 'Price must be at least 0.',
            'compare_price.min' => 'Compare price must be at least 0.',
            'category_ids.*.exists' => 'One or more selected categories do not exist.',
        ];
    }
}
