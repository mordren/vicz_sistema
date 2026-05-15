<?php

namespace App\Http\Requests;

use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AccountReceivable::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'string', 'max:160'],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'description' => ['required', 'string', 'max:255'],
            'receivable_type' => ['required', Rule::enum(ReceivableType::class)],
            'expected_date' => ['required', 'date'],
            'expected_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
