<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'           => 'required|integer|exists:orders,id',
            'reason'             => 'required|string|in:damaged,wrong_item,not_as_described,changed_mind,other',
            'reason_details'     => 'nullable|string|max:1000',
            'items'              => 'nullable|array',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.reason'        => 'nullable|string|max:255',
        ];
    }
}
