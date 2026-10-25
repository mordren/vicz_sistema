<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Http\Requests\ApproveProposalRequest;
use App\Http\Requests\MarkProposalAsLostRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalConversionService;
use App\Support\ListSorter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function __construct(private readonly ProposalConversionService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Proposal::class);

        $hasProposalCustomerId = Schema::hasColumn('proposals', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');
        $hasWorkOrderProposalId = Schema::hasColumn('work_orders', 'proposal_id');

        $filters = [
            'customer_id' => (string) $request->string('customer_id'),
            'status' => (string) $request->string('status'),
            'sales_owner_id' => (string) $request->string('sales_owner_id'),
            'close_from' => (string) $request->string('close_from'),
            'close_to' => (string) $request->string('close_to'),
            'probability_min' => (string) $request->string('probability_min'),
            'probability_max' => (string) $request->string('probability_max'),
        ];

        $query = Proposal::query()
            ->with(['salesOwner:id,name']);

        if ($hasWorkOrderProposalId) {
            $query->with(['workOrder:id,proposal_id,number']);
        }

        if ($hasProposalCustomerId && $hasCustomersTable) {
            $query->with('customerRef:id,name');
        }

        if ($hasProposalCustomerId && $filters['customer_id'] !== '') {
            $query->where('customer_id', (int) $filters['customer_id']);
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

        $sorting = ListSorter::apply($query, $request, [
            'number' => 'number',
            'customer' => 'customer',
            'sales_owner' => fn ($q, $dir) => $q->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'proposals.sales_owner_id'),
                $dir,
            ),
            'expected_close_date' => 'expected_close_date',
            'close_probability' => 'close_probability',
            'amount' => 'amount',
            'weighted_amount' => 'weighted_amount',
            'status' => ListSorter::byPriority('status', ['NEGOTIATING', 'SENT', 'APPROVED', 'LOST', 'CANCELLED']),
        ]);

        if ($sorting['sort'] === null) {
            $query->orderByRaw("CASE status WHEN 'NEGOTIATING' THEN 0 WHEN 'SENT' THEN 1 WHEN 'APPROVED' THEN 2 WHEN 'LOST' THEN 3 ELSE 4 END")
                ->orderBy('expected_close_date')
                ->orderByDesc('id');
        }

        $proposals = $query->paginate(15)->withQueryString();

        if (! $hasWorkOrderProposalId) {
            $proposals->getCollection()->each(function (Proposal $proposal): void {
                $proposal->setRelation('workOrder', null);
            });
        }

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

        $customers = ($hasProposalCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('proposals.index', [
            'proposals' => $proposals,
            'sorting' => $sorting,
            'filters' => $filters,
            'statuses' => ProposalStatus::cases(),
            'summaryCards' => $summaryCards,
            'salesOwners' => $salesOwners,
            'customers' => $customers,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Proposal::class);

        $hasProposalCustomerId = Schema::hasColumn('proposals', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $salesOwners = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::SALES])
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = ($hasProposalCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('proposals.create', [
            'salesOwners' => $salesOwners,
            'customers' => $customers,
            'proposal' => new Proposal(),
        ]);
    }

    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $hasProposalCustomerId = Schema::hasColumn('proposals', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $customerId = (int) ($data['customer_id'] ?? 0);
        $customerName = trim((string) ($data['customer'] ?? ''));

        if ($hasCustomersTable && $customerId > 0) {
            $customerName = (string) (Customer::query()->find($customerId)?->name ?? $customerName);
        }

        $payload = [
            'number' => $this->generateProposalNumber(),
            'customer' => $customerName,
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
        ];

        if ($hasProposalCustomerId) {
            $payload['customer_id'] = $customerId > 0 ? $customerId : null;
        }

        $proposal = Proposal::query()->create($payload);

        $this->service->recalculateWeightedAmount($proposal, $request->user());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposta criada com sucesso.');
    }

    public function show(Proposal $proposal): View
    {
        $this->authorize('view', $proposal);

        $hasWorkOrderProposalId = Schema::hasColumn('work_orders', 'proposal_id');

        $this->service->recalculateWeightedAmount($proposal, request()->user());

        $relations = [
            'salesOwner:id,name',
            'attachments.uploadedBy:id,name',
        ];

        if ($hasWorkOrderProposalId) {
            $relations[] = 'workOrder:id,proposal_id,number,status';
        }

        $proposal->load($relations);

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

        $hasProposalCustomerId = Schema::hasColumn('proposals', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        if (in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas perdidas ou canceladas não podem ser editadas.');
        }

        $salesOwners = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::SALES])
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = ($hasProposalCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('proposals.edit', [
            'proposal' => $proposal,
            'salesOwners' => $salesOwners,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $hasProposalCustomerId = Schema::hasColumn('proposals', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        if (in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true)) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', 'Propostas perdidas ou canceladas não podem ser editadas.');
        }

        $data = $request->validated();

        $customerId = (int) ($data['customer_id'] ?? 0);
        $customerName = trim((string) ($data['customer'] ?? ''));

        if ($hasCustomersTable && $customerId > 0) {
            $customerName = (string) (Customer::query()->find($customerId)?->name ?? $customerName);
        }

        $payload = [
            'customer' => $customerName,
            'product_or_service_type' => $data['product_or_service_type'],
            'description' => $data['description'] ?? null,
            'amount' => (float) $data['amount'],
            'sent_at' => $data['sent_at'] ?? $proposal->sent_at,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'close_probability' => (int) $data['close_probability'],
            'sales_owner_id' => $data['sales_owner_id'] ?: null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if ($hasProposalCustomerId) {
            $payload['customer_id'] = $customerId > 0 ? $customerId : null;
        }

        $proposal->update($payload);

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
        $this->authorize('delete', $proposal);

        $proposal->delete();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'Proposta excluída com sucesso.');
    }

    private function generateProposalNumber(): string
    {
        $year = now()->format('Y');

        $last = Proposal::query()
            ->where('number', 'like', 'PROP-'.$year.'-%')
            ->orderByDesc('id')
            ->value('number');

        if ($last === null) {
            return 'PROP-'.$year.'-0001';
        }

        $sequence = (int) Str::of($last)->afterLast('-')->toString();

        return 'PROP-'.$year.'-'.str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
