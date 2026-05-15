<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkOrder::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'string', 'max:255'],
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

            if (!in_array($responsible->role, [UserRole::ADMIN, UserRole::OPERATIONS], true)) {
                $validator->errors()->add('responsible_user_id', 'O responsável deve ser um usuário de Operações ou Administrador.');
            }
        });
    }
}
