<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'proposal_id',
        'customer_id',
        'customer',
        'service_type',
        'description',
        'responsible_user_id',
        'opened_at',
        'expected_delivery_date',
        'recalculated_delivery_date',
        'actual_delivery_date',
        'status',
        'progress_percentage',
        'contracted_amount',
        'down_payment_amount',
        'received_amount',
        'receivable_balance',
        'estimated_cost',
        'actual_cost',
        'estimated_margin',
        'actual_margin',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'opened_at' => 'date',
            'expected_delivery_date' => 'date',
            'recalculated_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'progress_percentage' => 'integer',
            'contracted_amount' => 'decimal:2',
            'down_payment_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'receivable_balance' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'estimated_margin' => 'decimal:4',
            'actual_margin' => 'decimal:4',
        ];
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payables(): HasMany
    {
        return $this->hasMany(AccountPayable::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(AccountReceivable::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function customerRef(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            WorkOrderStatus::DELIVERED->value,
            WorkOrderStatus::CANCELLED->value,
        ]);
    }

    public function scopeOverdueDelivery($query)
    {
        return $query->active()
            ->whereDate('expected_delivery_date', '<', now()->toDateString());
    }

    public function isDeliveryOverdue(): bool
    {
        if (in_array($this->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return false;
        }

        $deadline = $this->deliveryDeadline();

        return $deadline !== null && $deadline->lt(now()->startOfDay());
    }

    public function isNearDelivery(): bool
    {
        if (in_array($this->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return false;
        }

        $deadline = $this->deliveryDeadline();
        if ($deadline === null) {
            return false;
        }

        return $deadline->between(now()->startOfDay(), now()->addDays(3)->endOfDay());
    }

    public function deliveryDeadline()
    {
        return $this->recalculated_delivery_date ?? $this->expected_delivery_date;
    }
}
