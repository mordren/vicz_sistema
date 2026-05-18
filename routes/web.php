<?php

use App\Http\Controllers\AccountPayableController;
use App\Http\Controllers\AccountReceivableController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashForecastController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

// ─── Guest ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'active'])->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard – all roles
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Finance & Admin ──────────────────────────────────────────────────────
    Route::middleware('role:ADMIN,FINANCE')->group(function () {
        Route::patch('payables/{payable}/mark-paid', [AccountPayableController::class, 'markPaid'])
            ->name('payables.mark-paid');
        Route::patch('payables/{payable}/cancel', [AccountPayableController::class, 'cancel'])
            ->name('payables.cancel');
        Route::patch('receivables/{receivable}/mark-received', [AccountReceivableController::class, 'markReceived'])
            ->name('receivables.mark-received');
        Route::patch('receivables/{receivable}/cancel', [AccountReceivableController::class, 'cancel'])
            ->name('receivables.cancel');
        Route::resource('payables', AccountPayableController::class);
        Route::resource('receivables', AccountReceivableController::class);
        Route::get('reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
        Route::get('reports/financial/export', [ReportController::class, 'exportFinancial'])->name('reports.financial.export');
        Route::get('reports/cash-flow', [CashForecastController::class, 'index'])->name('reports.cash-flow');
        Route::patch('reports/cash-flow/current-balance', [CashForecastController::class, 'updateBalance'])->name('reports.cash-flow.update-balance');
    });

    // ── Operations & Admin ───────────────────────────────────────────────────
    Route::middleware('role:ADMIN,OPERATIONS')->group(function () {
        Route::patch('work-orders/{workOrder}/operational-update', [WorkOrderController::class, 'operationalUpdate'])
            ->name('work-orders.operational-update');
        Route::patch('work-orders/{workOrder}/deliver', [WorkOrderController::class, 'markDelivered'])
            ->name('work-orders.deliver');
        Route::patch('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])
            ->name('work-orders.cancel');
        Route::resource('work-orders', WorkOrderController::class)
            ->except(['index', 'show']);
        Route::get('reports/operational', [ReportController::class, 'operational'])->name('reports.operational');
        Route::get('reports/operational/export', [ReportController::class, 'exportOperational'])->name('reports.operational.export');
    });

    // ── Sales & Admin ────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN,SALES')->group(function () {
        Route::patch('proposals/{proposal}/approve', [ProposalController::class, 'approve'])
            ->name('proposals.approve');
        Route::patch('proposals/{proposal}/mark-lost', [ProposalController::class, 'markAsLost'])
            ->name('proposals.mark-lost');
        Route::patch('proposals/{proposal}/cancel', [ProposalController::class, 'cancel'])
            ->name('proposals.cancel');
        Route::post('proposals/{proposal}/convert-to-work-order', [ProposalController::class, 'convertToWorkOrder'])
            ->name('proposals.convert-to-work-order');
        Route::resource('proposals', ProposalController::class)
            ->except(['index', 'show']);
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');
    });

    // ── Admin only ───────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN')->group(function () {
        // Settings sections
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('settings/company', [SettingsController::class, 'company'])->name('settings.company');
        Route::patch('settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::get('settings/financial', [SettingsController::class, 'financial'])->name('settings.financial');
        Route::patch('settings/financial', [SettingsController::class, 'updateFinancial'])->name('settings.financial.update');
        Route::get('settings/work-order', [SettingsController::class, 'workOrder'])->name('settings.work-order');
        Route::patch('settings/work-order', [SettingsController::class, 'updateWorkOrder'])->name('settings.work-order.update');
        Route::get('settings/proposal', [SettingsController::class, 'proposal'])->name('settings.proposal');
        Route::patch('settings/proposal', [SettingsController::class, 'updateProposal'])->name('settings.proposal.update');
        Route::get('settings/alert', [SettingsController::class, 'alert'])->name('settings.alert');
        Route::patch('settings/alert', [SettingsController::class, 'updateAlert'])->name('settings.alert.update');
        Route::get('settings/attachment', [SettingsController::class, 'attachment'])->name('settings.attachment');
        Route::patch('settings/attachment', [SettingsController::class, 'updateAttachment'])->name('settings.attachment.update');
        Route::get('settings/permissions', [UserController::class, 'permissions'])->name('settings.permissions');

        // User management
        Route::get('settings/users', [UserController::class, 'index'])->name('settings.users.index');
        Route::get('settings/users/create', [UserController::class, 'create'])->name('settings.users.create');
        Route::post('settings/users', [UserController::class, 'store'])->name('settings.users.store');
        Route::get('settings/users/{user}/edit', [UserController::class, 'edit'])->name('settings.users.edit');
        Route::patch('settings/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
        Route::get('settings/users/{user}', [UserController::class, 'show'])->name('settings.users.show');
        Route::patch('settings/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('settings.users.toggle-active');

        // Cron execution log (last 100 lines)
        Route::get('settings/cron-log', [SettingsController::class, 'cronLog'])->name('settings.cron-log');
    });

    // ── All roles (read-only for work orders, proposals, alerts) ─────────────
    Route::get('work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::resource('customers', CustomerController::class);
    Route::get('proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
    Route::post('alerts/generate', [AlertController::class, 'generate'])->name('alerts.generate');
    Route::patch('alerts/{alert}/read', [AlertController::class, 'markRead'])->name('alerts.read');
    Route::patch('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::resource('alerts', AlertController::class)
        ->only(['index', 'show']);
});
