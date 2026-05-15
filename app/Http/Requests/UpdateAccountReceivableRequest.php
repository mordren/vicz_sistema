<?php

namespace App\Http\Requests;

use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $receivable = $this->route('receivable');

        return $receivable instanceof AccountReceivable
            && ($this->user()?->can('update', $receivable) ?? false);
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
