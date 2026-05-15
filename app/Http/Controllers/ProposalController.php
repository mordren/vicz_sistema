<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Http\Requests\ApproveProposalRequest;
use App\Http\Requests\MarkProposalAsLostRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function __construct(private readonly ProposalConversionService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Proposal::class);

        $filters = [
            'customer' => (string) $request->string('customer'),
            'status' => (string) $request->string('status'),
            'sales_owner_id' => (string) $request->string('sales_owner_id'),
            'close_from' => (string) $request->string('close_from'),
            'close_to' => (string) $request->string('close_to'),
            'probability_min' => (string) $request->string('probability_min'),
            'probability_max' => (string) $request->string('probability_max'),
        ];

        $query = Proposal::query()
            ->with(['salesOwner:id,name', 'workOrder:id,proposal_id,number'])
            ->orderByRaw("CASE status WHEN 'NEGOTIATING' THEN 0 WHEN 'SENT' THEN 1 WHEN 'APPROVED' THEN 2 WHEN 'LOST' THEN 3 ELSE 4 END")
            ->orderBy('expected_close_date')
            ->orderByDesc('id');

        if ($filters['customer'] !== '') {
            $query->where('customer', 'like', '%' . $filters['customer'] . '%');
        }

        if (($status = ProposalStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status);
        }

        if ($filters['sales_owner_id'] !== '') {
            $query->where('sales_owner_id', (int) $filters['sales_owner_id']);
        }

        if ($filters['close_from'] !== '') {
            $query->whereDate('expected_close_date', '>=', $filters['close_from']);
        }

        if ($filters['close_to'] !== '') {
            $query->whereDate('expected_close_date', '<=', $filters['close_to']);
        }

        if ($filters['probability_min'] !== '' && is_numeric($filters['probability_min'])) {
            $query->where('close_probability', '>=', (int) $filters['probability_min']);
        }

        if ($filters['probability_max'] !== '' && is_numeric($filters['probability_max'])) {
            $query->where('close_probability', '<=', (int) $filters['probability_max']);
        }

        $proposals = $query->paginate(15)->withQueryString();

        $proposals->getCollection()->each(function (Proposal $proposal) use ($request): void {
            $this->service->recalculateWeightedAmount($proposal, $request->user());
        });

        $now = now();

        $summaryCards = [
            'openProposals' => (int) Proposal::query()->whereIn('status', [ProposalStatus::SENT, ProposalStatus::NEGOTIATING])->count(),
            'closeThisMonth' => (int) Proposal::query()
                ->whereIn('status', [ProposalStatus::SENT, ProposalStatus::NEGOTIATING])
                ->whereMonth('expected_close_date', $now->month)
                ->whereYear('expected_close_date', $now->year)
                ->count(),
            'weightedForecast' => (float) Proposal::query()
                ->whereIn('status', [ProposalStatus::SENT, ProposalStatus::NEGOTIATING, ProposalStatus::APPROVED])
                ->sum('weighted_amount'),
            'approvedTotal' => (float) Proposal::query()->where('status', ProposalStatus::APPROVED)->sum('amount'),
            'lostTotal' => (float) Proposal::query()->where('status', ProposalStatus::LOST)->sum('amount'),
        ];

        $salesOwners = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::SALES])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('proposals.index', [
            'proposals' => $proposals,
            'filters' => $filters,
            'statuses' => ProposalStatus::cases(),
            'summaryCards' => $summaryCards,
            'salesOwners' => $salesOwners,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Proposal::class);

        $salesOwners = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::SALES])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('proposals.create', [
            'salesOwners' => $salesOwners,
        ]);
    }

    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $proposal = Proposal::query()->create([
            'number' => $this->generateProposalNumber(),
            'customer' => $data['customer'],
            'product_or_service_type' => $data['product_or_service_type'],
            'description' => $data['description'] ?? null,
            'amount' => (float) $data['amount'],
            'sent_at' => $data['sent_at'] ?? now()->toDateString(),
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'close_probability' => (int) $data['close_probability'],
            'weighted_amount' => 0,
            'sales_owner_id' => $data['sales_owner_id'] ?: null,
            'status' => ProposalStatus::SENT,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->service->recalculateWeightedAmount($proposal, $request->user());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta criada com sucesso.');
    }

    public function show(Proposal $proposal): View
    {
        $this->authorize('view', $proposal);

        $this->service->recalculateWeightedAmount($proposal, request()->user());

        $proposal->load([
            'salesOwner:id,name',
            'workOrder:id,proposal_id,number,status',
            'attachments.uploadedBy:id,name',
        ]);

        $canConvert = auth()->user()->can('convertToWorkOrder', $proposal)
            && $proposal->status === ProposalStatus::APPROVED;

        return view('proposals.show', [
            'proposal' => $proposal,
            'canConvert' => $canConvert,
            'statuses' => ProposalStatus::cases(),
        ]);
    }

    public function edit(Proposal $proposal): View|RedirectResponse
    {
        $this->authorize('update', $proposal);

        if (in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas perdidas ou canceladas não podem ser editadas.');
        }

        $salesOwners = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::SALES])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('proposals.edit', [
            'proposal' => $proposal,
            'salesOwners' => $salesOwners,
        ]);
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        if (in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas perdidas ou canceladas não podem ser editadas.');
        }

        $data = $request->validated();

        $proposal->update([
            'customer' => $data['customer'],
            'product_or_service_type' => $data['product_or_service_type'],
            'description' => $data['description'] ?? null,
            'amount' => (float) $data['amount'],
            'sent_at' => $data['sent_at'] ?? $proposal->sent_at,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'close_probability' => (int) $data['close_probability'],
            'sales_owner_id' => $data['sales_owner_id'] ?: null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        if ($proposal->status === ProposalStatus::APPROVED) {
            $proposal->update([
                'close_probability' => 100,
                'updated_by' => $request->user()->id,
            ]);
        }

        $this->service->recalculateWeightedAmount($proposal, $request->user());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta atualizada com sucesso.');
    }

    public function approve(ApproveProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        if (in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas perdidas ou canceladas não podem ser aprovadas.');
        }

        $this->service->approve($proposal, $request->user(), $request->string('notes')->toString());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta aprovada com sucesso.');
    }

    public function markAsLost(MarkProposalAsLostRequest $request, Proposal $proposal): RedirectResponse
    {
        if (in_array($proposal->status, [ProposalStatus::APPROVED, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas aprovadas ou canceladas não podem ser marcadas como perdidas.');
        }

        $this->service->markAsLost(
            $proposal,
            $request->user(),
            (string) $request->input('loss_reason'),
            $request->string('notes')->toString()
        );

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta marcada como perdida.');
    }

    public function cancel(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('cancel', $proposal);

        if (in_array($proposal->status, [ProposalStatus::APPROVED, ProposalStatus::LOST], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas aprovadas ou perdidas não podem ser canceladas.');
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->cancel($proposal, $request->user(), $request->string('notes')->toString());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta cancelada com sucesso.');
    }

    public function convertToWorkOrder(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('convertToWorkOrder', $proposal);

        $validated = $request->validate([
            'create_down_payment_receivable' => ['nullable', 'boolean'],
            'down_payment_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $createDownPayment = (bool) ($validated['create_down_payment_receivable'] ?? false);
        $downPaymentAmount = (float) ($validated['down_payment_amount'] ?? 0);

        if ($createDownPayment && $downPaymentAmount <= 0) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Informe um valor de entrada válido para criar o recebível inicial.');
        }

        try {
            $workOrder = $this->service->convertToWorkOrder($proposal, $request->user(), $createDownPayment, $downPaymentAmount);
        } catch (\DomainException $exception) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Proposta convertida para ordem de serviço com sucesso.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        return redirect()
            ->route('proposals.show', $proposal)
            ->with('error', 'Exclusão física não permitida. Use as ações de cancelamento/perda.');
    }

    private function generateProposalNumber(): string
    {
        $year = now()->format('Y');

        $last = Proposal::query()
            ->where('number', 'like', 'PROP-' . $year . '-%')
            ->orderByDesc('id')
            ->value('number');

        if ($last === null) {
            return 'PROP-' . $year . '-0001';
        }

        $sequence = (int) Str::of($last)->afterLast('-')->toString();

        return 'PROP-' . $year . '-' . str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
