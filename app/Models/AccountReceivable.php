<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountReceivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer',
        'work_order_id',
        'work_order_number',
        'description',
        'receivable_type',
        'expected_date',
        'expected_amount',
        'received_date',
        'received_amount',
        'payment_method',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'receivable_type' => ReceivableType::class,
            'status' => ReceivableStatus::class,
            'payment_method' => PaymentMethod::class,
            'expected_date' => 'date',
            'received_date' => 'date',
            'expected_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function customerRef(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', ReceivableStatus::OVERDUE->value);
    }

    public function scopeExpectedWithinDays($query, int $days)
    {
        return $query->whereBetween('expected_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
