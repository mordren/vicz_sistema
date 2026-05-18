<?php

namespace Tests\Feature\Reports;

use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowForecastModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_supports_all_required_periods(): void
    {
        $user = User::factory()->finance()->create();

        foreach ([7, 15, 30, 60, 90] as $period) {
            $this->actingAs($user)
                ->get(route('reports.cash-flow', ['period' => $period]))
                ->assertOk()
                ->assertViewHas('forecast', function (array $forecast) use ($period): bool {
                    return $forecast['period'] === $period
                        && count($forecast['rows']) === $period;
                });
        }
    }

    public function test_can_update_current_cash_balance_setting(): void
    {
        $user = User::factory()->finance()->create();

        $this->actingAs($user)
            ->patch(route('reports.cash-flow.update-balance'), [
                'current_balance' => 12345.67,
                'period' => 30,
                'include_weighted_proposals' => 0,
            ])
            ->assertRedirect(route('reports.cash-flow', ['period' => 30, 'include_weighted_proposals' => false]));

        $this->assertSame('12345.67', Setting::query()->where('key', 'cash_balance')->value('value'));
    }

    public function test_negative_projected_balance_days_are_flagged(): void
    {
        $user = User::factory()->finance()->create();
        Setting::set('cash_balance', '100.00');

        AccountPayable::factory()->create([
            'status' => PayableStatus::OPEN,
            'due_date' => now()->toDateString(),
            'amount' => 500,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.cash-flow', ['period' => 7]))
            ->assertOk()
            ->assertViewHas('forecast', function (array $forecast): bool {
                return collect($forecast['rows'])->contains(fn (array $row) => $row['is_negative'] === true);
            });
    }

    public function test_paid_and_received_and_cancelled_records_are_excluded_from_confirmed_flow(): void
    {
        $user = User::factory()->finance()->create();
        Setting::set('cash_balance', '0.00');

        AccountPayable::factory()->create([
            'status' => PayableStatus::OPEN,
            'due_date' => now()->toDateString(),
            'amount' => 300,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->create([
            'status' => PayableStatus::PAID,
            'due_date' => now()->toDateString(),
            'amount' => 900,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->create([
            'status' => PayableStatus::CANCELLED,
            'due_date' => now()->toDateString(),
            'amount' => 700,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->create([
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => now()->toDateString(),
            'expected_amount' => 450,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->create([
            'status' => ReceivableStatus::RECEIVED,
            'expected_date' => now()->toDateString(),
            'expected_amount' => 1000,
            'received_amount' => 1000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->create([
            'status' => ReceivableStatus::CANCELLED,
            'expected_date' => now()->toDateString(),
            'expected_amount' => 800,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.cash-flow', ['period' => 7]))
            ->assertOk()
            ->assertViewHas('forecast', function (array $forecast): bool {
                return (float) $forecast['totals']['expectedInflows'] === 450.0
                    && (float) $forecast['totals']['expectedOutflows'] === 300.0;
            });
    }

    public function test_overdue_inflows_and_outflows_are_visible_as_risk(): void
    {
        $user = User::factory()->finance()->create();

        AccountReceivable::factory()->create([
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => now()->subDay()->toDateString(),
            'expected_amount' => 220,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->create([
            'status' => PayableStatus::OPEN,
            'due_date' => now()->subDay()->toDateString(),
            'amount' => 330,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.cash-flow', ['period' => 7]))
            ->assertOk()
            ->assertViewHas('forecast', function (array $forecast): bool {
                return (float) $forecast['totals']['overdueInflows'] === 220.0
                    && (float) $forecast['totals']['overdueOutflows'] === 330.0;
            });
    }

    public function test_weighted_proposal_scenario_is_separated_and_optional(): void
    {
        $user = User::factory()->finance()->create();
        Setting::set('cash_balance', '500.00');

        Proposal::factory()->create([
            'status' => ProposalStatus::NEGOTIATING,
            'expected_close_date' => now()->toDateString(),
            'amount' => 1000,
            'close_probability' => 40,
            'weighted_amount' => 400,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $withoutScenario = $this->actingAs($user)
            ->get(route('reports.cash-flow', ['period' => 7, 'include_weighted_proposals' => 0]))
            ->assertOk()
            ->viewData('forecast');

        $withScenario = $this->actingAs($user)
            ->get(route('reports.cash-flow', ['period' => 7, 'include_weighted_proposals' => 1]))
            ->assertOk()
            ->viewData('forecast');

        $this->assertSame(0.0, (float) $withoutScenario['rows'][0]['weighted_proposal_impact']);
        $this->assertSame(400.0, (float) $withScenario['rows'][0]['weighted_proposal_impact']);
        $this->assertSame(
            (float) $withoutScenario['rows'][0]['projected_balance'],
            (float) $withScenario['rows'][0]['projected_balance']
        );
    }
}
