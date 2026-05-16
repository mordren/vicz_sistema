<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Services\AlertGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private readonly AlertGenerationService $generationService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Alert::class);

        $filters = [
            'type' => (string) $request->string('type'),
            'severity' => (string) $request->string('severity'),
            'status' => (string) $request->string('status'),
            'date_from' => (string) $request->string('date_from'),
            'date_to' => (string) $request->string('date_to'),
            'search' => (string) $request->string('search'),
        ];

        if ($request->boolean('refresh', false)) {
            $this->generationService->generate();
        }

        $query = Alert::query()
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 0 WHEN 'WARNING' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE status WHEN 'OPEN' THEN 0 WHEN 'READ' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        if ($filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if (AlertSeverity::tryFrom($filters['severity']) !== null) {
            $query->where('severity', $filters['severity']);
        }

        if (AlertStatus::tryFrom($filters['status']) !== null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($filters['search'] !== '') {
            $query->where(function ($inner) use ($filters): void {
                $inner->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('message', 'like', '%'.$filters['search'].'%');
            });
        }

        $alerts = $query->paginate(20)->withQueryString();

        $types = Alert::query()
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $summary = [
            'open' => (int) Alert::query()->where('status', AlertStatus::OPEN->value)->count(),
            'criticalOpen' => (int) Alert::query()->where('status', AlertStatus::OPEN->value)->where('severity', AlertSeverity::CRITICAL->value)->count(),
            'resolved' => (int) Alert::query()->where('status', AlertStatus::RESOLVED->value)->count(),
        ];

        return view('alerts.index', [
            'alerts' => $alerts,
            'filters' => $filters,
            'types' => $types,
            'severities' => AlertSeverity::cases(),
            'statuses' => AlertStatus::cases(),
            'summary' => $summary,
        ]);
    }

    public function show(Alert $alert): View
    {
        $this->authorize('view', $alert);

        if ($alert->status === AlertStatus::OPEN) {
            $alert->markRead();
            $alert->refresh();
        }

        return view('alerts.show', [
            'alert' => $alert,
        ]);
    }

    public function markRead(Alert $alert): RedirectResponse
    {
        $this->authorize('markRead', $alert);

        if ($alert->status === AlertStatus::OPEN) {
            $alert->markRead();
        }

        return back()->with('success', 'Alerta marcado como lido.');
    }

    public function resolve(Alert $alert): RedirectResponse
    {
        $this->authorize('resolve', $alert);

        if ($alert->status !== AlertStatus::RESOLVED) {
            $alert->resolve();
        }

        return back()->with('success', 'Alerta resolvido com sucesso.');
    }

    public function generate(): RedirectResponse
    {
        $this->authorize('create', Alert::class);

        $summary = $this->generationService->generate();

        return redirect()
            ->route('alerts.index')
            ->with('success', 'Alertas atualizados. Criados: '.$summary['created'].', atualizados: '.$summary['updated'].', resolvidos: '.$summary['resolved'].'.');
    }
}
