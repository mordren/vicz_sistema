<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal instanceof Proposal
            && ($this->user()?->can('update', $proposal) ?? false);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('status', CustomerStatus::ACTIVE->value)],
            'customer' => ['nullable', 'string', 'max:255', 'required_without:customer_id'],
            'product_or_service_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'sent_at' => ['nullable', 'date'],
            'expected_close_date' => ['nullable', 'date'],
            'close_probability' => ['required', 'integer', 'min:0', 'max:100'],
            'sales_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $salesOwnerId = $this->integer('sales_owner_id');
            if ($salesOwnerId <= 0) {
                return;
            }

            /** @var User|null $salesOwner */
            $salesOwner = User::query()->find($salesOwnerId);
            if ($salesOwner === null) {
                return;
            }

            if (! in_array($salesOwner->role, [UserRole::ADMIN, UserRole::SALES], true)) {
                $validator->errors()->add('sales_owner_id', 'O responsável comercial deve ser usuário de Comercial ou Administrador.');
            }

            $customerId = $this->integer('customer_id');
            if ($customerId > 0) {
                $customer = Customer::query()->find($customerId);
                if ($customer !== null && $customer->status !== CustomerStatus::ACTIVE) {
                    $validator->errors()->add('customer_id', 'Somente clientes ativos podem ser usados em novas propostas.');
                }

                return;
            }

            if (trim((string) $this->input('customer', '')) === '') {
                $validator->errors()->add('customer', 'Informe um cliente.');
            }
        });
    }
}
