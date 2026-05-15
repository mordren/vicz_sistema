<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\AccountReceivable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAccountReceivableAsReceivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $receivable = $this->route('receivable');

        return $receivable instanceof AccountReceivable
            && ($this->user()?->can('markAsReceived', $receivable) ?? false);
    }

    public function rules(): array
    {
        return [
            'received_date' => ['required', 'date'],
            'received_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receipt_file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }
}
