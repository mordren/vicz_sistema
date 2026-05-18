<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $type = CustomerType::tryFrom((string) $this->input('type'));

        $this->merge([
            'cpf' => $this->normalizeDigits($this->input('cpf')),
            'cnpj' => $this->normalizeDigits($this->input('cnpj')),
            'phone' => $this->normalizeDigits($this->input('phone')),
            'whatsapp' => $this->normalizeDigits($this->input('whatsapp')),
            'secondary_phone' => $this->normalizeDigits($this->input('secondary_phone')),
            'zip_code' => $this->normalizeDigits($this->input('zip_code')),
        ]);

        if ($type === CustomerType::COMPANY) {
            $this->merge([
                'name' => $this->input('legal_name'),
                'cpf' => null,
            ]);
        }

        if ($type === CustomerType::INDIVIDUAL) {
            $this->merge([
                'legal_name' => null,
                'cnpj' => null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CustomerType::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'digits:11', 'unique:customers,cpf'],
            'cnpj' => ['nullable', 'digits:14', 'unique:customers,cnpj'],
            'state_registration' => ['nullable', 'string', 'max:60'],
            'municipal_registration' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'digits_between:10,11'],
            'whatsapp' => ['nullable', 'digits_between:10,11'],
            'secondary_phone' => ['nullable', 'digits_between:10,11'],
            'zip_code' => ['nullable', 'digits:8'],
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

            if ($type === CustomerType::INDIVIDUAL) {
                if (blank($this->input('name'))) {
                    $validator->errors()->add('name', 'Nome e obrigatorio para clientes pessoa fisica.');
                }

                if (blank($this->input('cpf'))) {
                    $validator->errors()->add('cpf', 'CPF e obrigatorio para clientes pessoa fisica.');
                }

            }

            if ($type === CustomerType::COMPANY) {
                if (blank($this->input('legal_name'))) {
                    $validator->errors()->add('legal_name', 'Razao social e obrigatoria para clientes pessoa juridica.');
                }

                if (blank($this->input('cnpj'))) {
                    $validator->errors()->add('cnpj', 'CNPJ e obrigatorio para clientes pessoa juridica.');
                }
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
