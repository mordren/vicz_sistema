<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order') ?? $this->route('workOrder');

        return $workOrder instanceof WorkOrder
            && ($this->user()?->can('updateStatus', $workOrder) ?? false);
    }

    public function rules(): array
    {
        $routeName = (string) $this->route()?->getName();

        if ($routeName === 'work-orders.deliver') {
            return [
                'actual_delivery_date' => ['required', 'date'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ];
        }

        if ($routeName === 'work-orders.cancel') {
            return [
                'cancel_reason' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'recalculated_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $responsibleId = $this->integer('responsible_user_id');
            if ($responsibleId > 0) {
                /** @var User|null $responsible */
                $responsible = User::query()->find($responsibleId);
                if ($responsible !== null && ! in_array($responsible->role, [UserRole::ADMIN, UserRole::OPERATIONS], true)) {
                    $validator->errors()->add('responsible_user_id', 'O responsável deve ser um usuário de Operações ou Administrador.');
                }
            }

            $routeName = (string) $this->route()?->getName();
            if ($routeName !== 'work-orders.operational-update') {
                return;
            }

            $status = WorkOrderStatus::tryFrom((string) $this->input('status'));
            $progress = $this->integer('progress_percentage');

            if ($status === WorkOrderStatus::DELIVERED) {
                $validator->errors()->add('status', 'Use a ação específica de entrega para concluir a OS.');
            }

            if ($status === WorkOrderStatus::CANCELLED) {
                $validator->errors()->add('status', 'Use a ação específica de cancelamento para cancelar a OS.');
            }

            if ($status === WorkOrderStatus::READY_FOR_DELIVERY && $progress < 90) {
                $validator->errors()->add('progress_percentage', 'Pronto para entrega exige progresso mínimo de 90%.');
            }
        });
    }
}
