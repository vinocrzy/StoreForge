<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:500',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    // Prevent setting parent to self
                    if ($categoryId && $value == $categoryId) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'parent_id.exists' => 'The selected parent category does not exist.',
        ];
    }
}
