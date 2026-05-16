<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use App\Models\Customer;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreAccountReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AccountReceivable::class) ?? false;
    }

    public function rules(): array
    {
        $hasCustomersTable = Schema::hasTable('customers');
        $hasReceivableCustomerId = Schema::hasColumn('account_receivables', 'customer_id');

        $customerIdRules = ['nullable', 'integer'];
        if ($hasCustomersTable && $hasReceivableCustomerId) {
            $customerIdRules = ['nullable', 'integer', Rule::exists('customers', 'id')->where('status', CustomerStatus::ACTIVE->value)];
        }

        return [
            'customer_id' => $customerIdRules,
            'customer' => ['nullable', 'string', 'max:255', 'required_without:customer_id'],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'description' => ['required', 'string', 'max:255'],
            'receivable_type' => ['required', Rule::enum(ReceivableType::class)],
            'expected_date' => ['required', 'date'],
            'expected_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $customerId = $this->integer('customer_id');
            $workOrderId = $this->integer('work_order_id');

            if (! Schema::hasTable('customers')) {
                if (trim((string) $this->input('customer', '')) === '' && $customerId <= 0) {
                    $validator->errors()->add('customer', 'Informe um cliente.');
                }

                return;
            }

            if ($customerId > 0) {
                $customer = Customer::query()->find($customerId);
                if ($customer !== null && $customer->status !== CustomerStatus::ACTIVE) {
                    $validator->errors()->add('customer_id', 'Somente clientes ativos podem ser usados em novos recebiveis.');
                }
            } elseif (trim((string) $this->input('customer', '')) === '') {
                $validator->errors()->add('customer', 'Informe um cliente.');
            }

            if ($customerId > 0 && $workOrderId > 0 && Schema::hasColumn('work_orders', 'customer_id')) {
                $workOrder = WorkOrder::query()->find($workOrderId);
                if ($workOrder !== null && $workOrder->customer_id !== $customerId) {
                    $validator->errors()->add('customer_id', 'Cliente deve ser o mesmo da ordem de servico vinculada.');
                }
            }
        });
    }
}
