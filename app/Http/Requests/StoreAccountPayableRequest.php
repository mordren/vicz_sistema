<?php

namespace App\Http\Requests;

use App\Enums\PayableCategory;
use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AccountPayable::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('total_installments') || $this->input('total_installments') === null || $this->input('total_installments') === '') {
            $this->merge(['total_installments' => 1]);
        }
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
            'total_installments' => ['required', 'integer', 'min:1', 'max:60'],
            'cost_center' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receipt_file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }
}
