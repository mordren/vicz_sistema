<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkOrder::class) ?? false;
    }

    public function rules(): array
    {
        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $customerIdRules = ['nullable', 'integer'];
        if ($hasWorkOrderCustomerId && $hasCustomersTable) {
            $customerIdRules = ['nullable', 'integer', Rule::exists('customers', 'id')->where('status', CustomerStatus::ACTIVE->value)];
        }

        return [
            'customer_id' => $customerIdRules,
            'customer' => ['nullable', 'string', 'max:255', 'required_without:customer_id'],
            'service_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'opened_at' => ['required', 'date'],
            'expected_delivery_date' => ['required', 'date', 'after_or_equal:opened_at'],
            'recalculated_delivery_date' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'contracted_amount' => ['required', 'numeric', 'min:0'],
            'down_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $responsibleId = $this->integer('responsible_user_id');

            if ($responsibleId <= 0) {
                return;
            }

            /** @var User|null $responsible */
            $responsible = User::query()->find($responsibleId);
            if ($responsible === null) {
                return;
            }

            if (! in_array($responsible->role, [UserRole::ADMIN, UserRole::OPERATIONS], true)) {
                $validator->errors()->add('responsible_user_id', 'O responsável deve ser um usuário de Operações ou Administrador.');
            }

            if (! Schema::hasTable('customers')) {
                if (trim((string) $this->input('customer', '')) === '' && $this->integer('customer_id') <= 0) {
                    $validator->errors()->add('customer', 'Informe um cliente.');
                }

                return;
            }

            $customerId = $this->integer('customer_id');
            if ($customerId > 0) {
                $customer = Customer::query()->find($customerId);
                if ($customer !== null && $customer->status !== CustomerStatus::ACTIVE) {
                    $validator->errors()->add('customer_id', 'Somente clientes ativos podem ser usados em novas ordens de servico.');
                }

                return;
            }

            if (trim((string) $this->input('customer', '')) === '') {
                $validator->errors()->add('customer', 'Informe um cliente.');
            }
        });
    }
}
