<?php

namespace App\Services;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Mail\AlertsDigestMail;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class AlertDigestEmailService
{
    /**
     * @return array{recipients:int,payables:int,receivables:int,workOrders:int}
     */
    public function sendDigest(): array
    {
        $today = now()->startOfDay();
        $end3d = $today->copy()->addDays(3);
        $staleDays = max(1, (int) Setting::get('alert_work_order_stale_days', 2));

        $payablesNext3Days = AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->whereDate('due_date', '>=', $today->toDateString())
            ->whereDate('due_date', '<=', $end3d->toDateString())
            ->orderBy('due_date')
            ->limit(50)
            ->get(['id', 'supplier', 'description', 'due_date', 'amount'])
            ->map(static fn (AccountPayable $row): array => [
                'id' => $row->id,
                'supplier' => $row->supplier,
                'description' => $row->description,
                'due_date' => $row->due_date?->format('d/m/Y'),
                'amount' => (float) $row->amount,
            ])
            ->all();

        $receivablesToCollect = AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->whereDate('expected_date', '<=', $end3d->toDateString())
            ->orderBy('expected_date')
            ->limit(50)
            ->get(['id', 'customer', 'expected_date', 'expected_amount', 'status'])
            ->map(static fn (AccountReceivable $row): array => [
                'id' => $row->id,
                'customer' => $row->customer,
                'expected_date' => $row->expected_date?->format('d/m/Y'),
                'expected_amount' => (float) $row->expected_amount,
                'status' => $row->status->value,
            ])
            ->all();

        $staleThreshold = now()->subDays($staleDays);

        $staleWorkOrders = WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->where('updated_at', '<=', $staleThreshold)
            ->with('responsibleUser:id,name')
            ->orderBy('updated_at')
            ->limit(50)
            ->get(['id', 'number', 'customer', 'responsible_user_id', 'updated_at'])
            ->map(static fn (WorkOrder $wo): array => [
                'id' => $wo->id,
                'number' => $wo->number,
                'customer' => $wo->customer,
                'responsible' => $wo->responsibleUser?->name,
                'updated_at' => $wo->updated_at?->format('d/m/Y H:i'),
            ])
            ->all();

        if ($payablesNext3Days === [] && $receivablesToCollect === [] && $staleWorkOrders === []) {
            return ['recipients' => 0, 'payables' => 0, 'receivables' => 0, 'workOrders' => 0];
        }

        $recipients = $this->resolveRecipients();

        if ($recipients->isEmpty()) {
            return ['recipients' => 0, 'payables' => count($payablesNext3Days), 'receivables' => count($receivablesToCollect), 'workOrders' => count($staleWorkOrders)];
        }

        foreach ($recipients as $email) {
            Mail::to($email)->send(new AlertsDigestMail(
                $payablesNext3Days,
                $receivablesToCollect,
                $staleWorkOrders,
                $staleDays,
            ));
        }

        return [
            'recipients' => $recipients->count(),
            'payables' => count($payablesNext3Days),
            'receivables' => count($receivablesToCollect),
            'workOrders' => count($staleWorkOrders),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function resolveRecipients(): Collection
    {
        $configured = trim((string) Setting::get('alert_email_recipients', ''));

        if ($configured !== '') {
            $items = preg_split('/[,;\s]+/', $configured) ?: [];

            return collect($items)
                ->map(static fn (string $email): string => strtolower(trim($email)))
                ->filter(static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
                ->unique()
                ->values();
        }

        $companyEmail = trim((string) Setting::get('company_email', ''));
        if ($companyEmail !== '' && filter_var($companyEmail, FILTER_VALIDATE_EMAIL) !== false) {
            return collect([strtolower($companyEmail)]);
        }

        return User::query()
            ->where('active', true)
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::FINANCE->value])
            ->whereNotNull('email')
            ->pluck('email')
            ->map(static fn (string $email): string => strtolower(trim($email)))
            ->filter(static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values();
    }
}
