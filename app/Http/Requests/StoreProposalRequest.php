<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Proposal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'string', 'max:255'],
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

            if (!in_array($salesOwner->role, [UserRole::ADMIN, UserRole::SALES], true)) {
                $validator->errors()->add('sales_owner_id', 'O responsável comercial deve ser usuário de Comercial ou Administrador.');
            }
        });
    }
}
