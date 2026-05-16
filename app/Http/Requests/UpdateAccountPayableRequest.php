<?php

namespace App\Http\Requests;

use App\Enums\PayableCategory;
use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payable = $this->route('payable');

        return $payable instanceof AccountPayable
            && ($this->user()?->can('update', $payable) ?? false);
    }

    public function rules(): array
    {
        return [
            'supplier' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PayableCategory::class)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cost_center' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
