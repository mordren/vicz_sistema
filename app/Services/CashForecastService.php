<?php

namespace App\Services;

use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\Setting;
use Carbon\Carbon;

class CashForecastService
{
    public const SETTING_KEY_CURRENT_BALANCE = 'cash_balance';

    /**
     * @return int[]
     */
    public function periods(): array
    {
        return [7, 15, 30, 60, 90];
    }

    public function getCurrentBalance(): float
    {
        return (float) Setting::get(self::SETTING_KEY_CURRENT_BALANCE, 0);
    }

    public function setCurrentBalance(float $value): void
    {
        Setting::set(self::SETTING_KEY_CURRENT_BALANCE, number_format($value, 2, '.', ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForecast(int $period, bool $includeWeightedProposals): array
    {
        $period = in_array($period, $this->periods(), true) ? $period : 30;

        $startDate = now()->startOfDay();
        $endDate = now()->addDays($period - 1)->startOfDay();

        $currentBalance = $this->getCurrentBalance();

        $expectedInflowsByDate = AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->whereBetween('expected_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('expected_date as forecast_date, SUM(expected_amount) as total')
            ->groupBy('expected_date')
            ->pluck('total', 'forecast_date')
            ->mapWithKeys(fn ($value, $date) => [Carbon::parse((string) $date)->toDateString() => (float) $value])
            ->all();

        $expectedOutflowsByDate = AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('due_date as forecast_date, SUM(amount) as total')
            ->groupBy('due_date')
            ->pluck('total', 'forecast_date')
            ->mapWithKeys(fn ($value, $date) => [Carbon::parse((string) $date)->toDateString() => (float) $value])
            ->all();

        $weightedProposalsByDate = Proposal::query()
            ->whereIn('status', [ProposalStatus::SENT->value, ProposalStatus::NEGOTIATING->value])
            ->whereBetween('expected_close_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('expected_close_date as forecast_date, SUM(weighted_amount) as total')
            ->groupBy('expected_close_date')
            ->pluck('total', 'forecast_date')
            ->mapWithKeys(fn ($value, $date) => [Carbon::parse((string) $date)->toDateString() => (float) $value])
            ->all();

        $totalExpectedOutflows = array_sum($expectedOutflowsByDate);
        $averageOutflow = $period > 0 ? ($totalExpectedOutflows / $period) : 0;

        $rows = [];
        $projectedBalance = $currentBalance;
        $scenarioBalance = $currentBalance;

        $chart = [
            'labels' => [],
            'projectedBalance' => [],
            'projectedBalanceWithScenario' => [],
            'expectedInflows' => [],
            'expectedOutflows' => [],
            'overdueInflows' => [],
            'overdueOutflows' => [],
            'weightedProposalImpact' => [],
        ];

        for ($day = 0; $day < $period; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dateKey = $date->toDateString();

            $openingBalance = $projectedBalance;
            $inflows = $expectedInflowsByDate[$dateKey] ?? 0.0;
            $outflows = $expectedOutflowsByDate[$dateKey] ?? 0.0;

            $overdueInflows = (float) AccountReceivable::query()
                ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
                ->whereDate('expected_date', '<', $dateKey)
                ->sum('expected_amount');

            $overdueOutflows = (float) AccountPayable::query()
                ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
                ->whereDate('due_date', '<', $dateKey)
                ->sum('amount');

            $weightedImpact = $includeWeightedProposals ? ($weightedProposalsByDate[$dateKey] ?? 0.0) : 0.0;

            $projectedBalance = $openingBalance + $inflows - $outflows;
            $scenarioBalance = $scenarioBalance + $inflows - $outflows + $weightedImpact;

            $isNegative = $projectedBalance < 0;
            $hasHighBillConcentration = $outflows > 0 && $averageOutflow > 0 && $outflows >= ($averageOutflow * 1.5);

            $rows[] = [
                'date' => $date,
                'opening_balance' => $openingBalance,
                'expected_inflows' => $inflows,
                'expected_outflows' => $outflows,
                'projected_balance' => $projectedBalance,
                'overdue_inflows' => $overdueInflows,
                'overdue_outflows' => $overdueOutflows,
                'weighted_proposal_impact' => $weightedImpact,
                'projected_balance_with_scenario' => $scenarioBalance,
                'is_negative' => $isNegative,
                'has_high_bill_concentration' => $hasHighBillConcentration,
            ];

            $chart['labels'][] = $date->format('d/m');
            $chart['projectedBalance'][] = round($projectedBalance, 2);
            $chart['projectedBalanceWithScenario'][] = round($scenarioBalance, 2);
            $chart['expectedInflows'][] = round($inflows, 2);
            $chart['expectedOutflows'][] = round($outflows, 2);
            $chart['overdueInflows'][] = round($overdueInflows, 2);
            $chart['overdueOutflows'][] = round($overdueOutflows, 2);
            $chart['weightedProposalImpact'][] = round($weightedImpact, 2);
        }

        return [
            'period' => $period,
            'includeWeightedProposals' => $includeWeightedProposals,
            'currentBalance' => $currentBalance,
            'rows' => $rows,
            'totals' => [
                'expectedInflows' => array_sum(array_column($rows, 'expected_inflows')),
                'expectedOutflows' => array_sum(array_column($rows, 'expected_outflows')),
                'overdueInflows' => $rows[0]['overdue_inflows'] ?? 0,
                'overdueOutflows' => $rows[0]['overdue_outflows'] ?? 0,
                'weightedProposalImpact' => array_sum(array_column($rows, 'weighted_proposal_impact')),
            ],
            'chart' => $chart,
        ];
    }
}
