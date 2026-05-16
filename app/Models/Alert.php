<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'entity_type',
        'entity_id',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function relatedRoute(): ?array
    {
        if ($this->entity_type === null || $this->entity_id === null) {
            return null;
        }

        return match ($this->entity_type) {
            AccountPayable::class => ['name' => 'payables.show', 'params' => [$this->entity_id]],
            AccountReceivable::class => ['name' => 'receivables.show', 'params' => [$this->entity_id]],
            WorkOrder::class => ['name' => 'work-orders.show', 'params' => [$this->entity_id]],
            Proposal::class => ['name' => 'proposals.show', 'params' => [$this->entity_id]],
            default => null,
        };
    }

    public function scopeOpen($query)
    {
        return $query->where('status', AlertStatus::OPEN->value);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverity::CRITICAL->value);
    }

    public function markRead(): void
    {
        $this->update(['status' => AlertStatus::READ]);
    }

    public function resolve(): void
    {
        $this->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
    }
}
