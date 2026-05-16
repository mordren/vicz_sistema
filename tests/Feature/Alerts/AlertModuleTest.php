<?php

namespace Tests\Feature\Alerts;

use App\Enums\AlertStatus;
use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AlertGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlertModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_generation_creates_expected_alerts_and_avoids_duplicates(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        try {
            $user = User::factory()->admin()->create();

            AccountPayable::factory()->create([
                'supplier' => 'Fornecedor X',
                'status' => PayableStatus::OPEN,
                'due_date' => now()->toDateString(),
                'amount' => 100,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            AccountReceivable::factory()->create([
                'customer' => 'Cliente Y',
                'status' => ReceivableStatus::EXPECTED,
                'expected_date' => now()->subDay()->toDateString(),
                'expected_amount' => 200,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            WorkOrder::factory()->create([
                'status' => WorkOrderStatus::IN_PRODUCTION,
                'expected_delivery_date' => now()->addDays(2)->toDateString(),
                'responsible_user_id' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Proposal::factory()->create([
                'status' => ProposalStatus::NEGOTIATING,
                'updated_at' => now()->subDays(10),
                'sales_owner_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $service = app(AlertGenerationService::class);

            $first = $service->generate();
            $second = $service->generate();

            $this->assertGreaterThan(0, $first['created']);
            $this->assertSame(0, $second['created']);
            $this->assertGreaterThan(0, $second['updated']);

            $this->assertSame(
                Alert::query()->count(),
                Alert::query()
                    ->selectRaw('type, entity_type, entity_id, COUNT(*) as total')
                    ->groupBy('type', 'entity_type', 'entity_id')
                    ->get()
                    ->sum('total')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stale_alert_is_resolved_when_condition_no_longer_exists(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        try {
            $user = User::factory()->admin()->create();

            $payable = AccountPayable::factory()->create([
                'status' => PayableStatus::OPEN,
                'due_date' => now()->toDateString(),
                'amount' => 100,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            app(AlertGenerationService::class)->generate();

            $this->assertDatabaseHas('alerts', [
                'entity_type' => AccountPayable::class,
                'entity_id' => $payable->id,
                'status' => AlertStatus::OPEN->value,
            ]);

            $payable->update([
                'status' => PayableStatus::PAID,
                'payment_date' => now()->toDateString(),
                'paid_amount' => 100,
                'updated_by' => $user->id,
            ]);

            app(AlertGenerationService::class)->generate();

            $this->assertDatabaseHas('alerts', [
                'entity_type' => AccountPayable::class,
                'entity_id' => $payable->id,
                'status' => AlertStatus::RESOLVED->value,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_user_can_mark_alert_read_and_resolve_via_routes(): void
    {
        $user = User::factory()->operations()->create();
        $alert = Alert::factory()->create(['status' => AlertStatus::OPEN]);

        $this->actingAs($user)
            ->patch(route('alerts.read', $alert))
            ->assertRedirect();

        $alert->refresh();
        $this->assertSame(AlertStatus::READ, $alert->status);

        $this->actingAs($user)
            ->patch(route('alerts.resolve', $alert))
            ->assertRedirect();

        $alert->refresh();
        $this->assertSame(AlertStatus::RESOLVED, $alert->status);
    }
}
