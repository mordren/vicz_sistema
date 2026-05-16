<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && ($this->user()?->can('update', $customer) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->normalizeDigits($this->input('cpf')),
            'cnpj' => $this->normalizeDigits($this->input('cnpj')),
            'phone' => $this->normalizeDigits($this->input('phone')),
            'whatsapp' => $this->normalizeDigits($this->input('whatsapp')),
            'secondary_phone' => $this->normalizeDigits($this->input('secondary_phone')),
        ]);
    }

    public function rules(): array
    {
        /** @var Customer $customer */
        $customer = $this->route('customer');

        return [
            'type' => ['required', Rule::enum(CustomerType::class)],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'digits:11', Rule::unique('customers', 'cpf')->ignore($customer->id)],
            'cnpj' => ['nullable', 'digits:14', Rule::unique('customers', 'cnpj')->ignore($customer->id)],
            'state_registration' => ['nullable', 'string', 'max:60'],
            'municipal_registration' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'secondary_phone' => ['nullable', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = CustomerType::tryFrom((string) $this->input('type'));

            if ($type === CustomerType::INDIVIDUAL && empty($this->input('cpf'))) {
                $validator->errors()->add('cpf', 'CPF e recomendado para clientes pessoa fisica.');
            }

            if ($type === CustomerType::COMPANY && empty($this->input('cnpj'))) {
                $validator->errors()->add('cnpj', 'CNPJ e recomendado para clientes pessoa juridica.');
            }
        });
    }

    private function normalizeDigits(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }
}
