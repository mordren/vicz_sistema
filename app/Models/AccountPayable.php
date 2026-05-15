<?php

namespace App\Models;

use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountPayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier',
        'description',
        'category',
        'issue_date',
        'due_date',
        'amount',
        'installment_number',
        'total_installments',
        'payment_method',
        'status',
        'payment_date',
        'paid_amount',
        'cost_center',
        'work_order_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => PayableCategory::class,
            'status' => PayableStatus::class,
            'payment_method' => PaymentMethod::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'installment_number' => 'integer',
            'total_installments' => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
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

    public function scopeOpen($query)
    {
        return $query->where('status', PayableStatus::OPEN->value);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', PayableStatus::OVERDUE->value);
    }

    public function scopeDueWithinDays($query, int $days)
    {
        return $query->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
