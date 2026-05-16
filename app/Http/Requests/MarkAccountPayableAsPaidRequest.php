<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAccountPayableAsPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payable = $this->route('payable');

        return $payable instanceof AccountPayable
            && ($this->user()?->can('markAsPaid', $payable) ?? false);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receipt_file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }
}
