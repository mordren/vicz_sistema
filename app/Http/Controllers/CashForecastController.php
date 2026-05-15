<?php

namespace App\Http\Controllers;

use App\Services\CashForecastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashForecastController extends Controller
{
    public function __construct(private readonly CashForecastService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('report.viewCashFlow');

        $period = (int) $request->integer('period', 30);
        $includeWeightedProposals = (bool) $request->boolean('include_weighted_proposals');

        $forecast = $this->service->buildForecast($period, $includeWeightedProposals);

        return view('reports.cash-flow', [
            'forecast' => $forecast,
            'periods' => $this->service->periods(),
            'filters' => [
                'period' => $forecast['period'],
                'include_weighted_proposals' => $includeWeightedProposals,
            ],
        ]);
    }

    public function updateBalance(Request $request): RedirectResponse
    {
        $this->authorize('report.viewCashFlow');

        $data = $request->validate([
            'current_balance' => ['required', 'numeric'],
            'period' => ['nullable', 'integer'],
            'include_weighted_proposals' => ['nullable', 'boolean'],
        ]);

        $this->service->setCurrentBalance((float) $data['current_balance']);

        return redirect()
            ->route('reports.cash-flow', [
                'period' => (int) ($data['period'] ?? 30),
                'include_weighted_proposals' => (bool) ($data['include_weighted_proposals'] ?? false),
            ])
            ->with('success', 'Saldo inicial atualizado com sucesso.');
    }
}
