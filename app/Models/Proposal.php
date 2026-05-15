<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'customer',
        'product_or_service_type',
        'description',
        'amount',
        'sent_at',
        'expected_close_date',
        'close_probability',
        'weighted_amount',
        'sales_owner_id',
        'status',
        'loss_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'sent_at' => 'date',
            'expected_close_date' => 'date',
            'amount' => 'decimal:2',
            'weighted_amount' => 'decimal:2',
            'close_probability' => 'integer',
        ];
    }

    public function salesOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
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

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            ProposalStatus::SENT->value,
            ProposalStatus::NEGOTIATING->value,
        ]);
    }

    public function recalculateWeightedAmount(): void
    {
        $this->weighted_amount = bcmul(
            (string) $this->amount,
            bcdiv((string) $this->close_probability, '100', 6),
            2
        );
    }
}
