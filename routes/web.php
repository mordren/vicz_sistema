<?php

use App\Http\Controllers\Auth\LoginController;
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
    Route::get('/', fn () => view('dashboard'))->name('dashboard');

    // ── Finance & Admin ──────────────────────────────────────────────────────
    Route::middleware('role:ADMIN,FINANCE')->group(function () {
        Route::patch('receivables/{receivable}/mark-received', [\App\Http\Controllers\AccountReceivableController::class, 'markReceived'])
            ->name('receivables.mark-received');
        Route::patch('receivables/{receivable}/cancel', [\App\Http\Controllers\AccountReceivableController::class, 'cancel'])
            ->name('receivables.cancel');
        Route::resource('payables', \App\Http\Controllers\AccountPayableController::class);
        Route::resource('receivables', \App\Http\Controllers\AccountReceivableController::class);
        Route::get('audit-logs', fn () => view('audit-logs.index'))->name('audit-logs.index');
        Route::get('reports/financial', fn () => view('reports.financial'))->name('reports.financial');
        Route::get('reports/cash-flow', [\App\Http\Controllers\CashForecastController::class, 'index'])->name('reports.cash-flow');
        Route::patch('reports/cash-flow/current-balance', [\App\Http\Controllers\CashForecastController::class, 'updateBalance'])->name('reports.cash-flow.update-balance');
    });

    // ── Operations & Admin ───────────────────────────────────────────────────
    Route::middleware('role:ADMIN,OPERATIONS')->group(function () {
        Route::patch('work-orders/{workOrder}/operational-update', [\App\Http\Controllers\WorkOrderController::class, 'operationalUpdate'])
            ->name('work-orders.operational-update');
        Route::patch('work-orders/{workOrder}/deliver', [\App\Http\Controllers\WorkOrderController::class, 'markDelivered'])
            ->name('work-orders.deliver');
        Route::patch('work-orders/{workOrder}/cancel', [\App\Http\Controllers\WorkOrderController::class, 'cancel'])
            ->name('work-orders.cancel');
        Route::resource('work-orders', \App\Http\Controllers\WorkOrderController::class)
            ->except(['index', 'show']);
        Route::get('reports/operational', fn () => view('reports.operational'))->name('reports.operational');
    });

    // ── Sales & Admin ────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN,SALES')->group(function () {
        Route::patch('proposals/{proposal}/approve', [\App\Http\Controllers\ProposalController::class, 'approve'])
            ->name('proposals.approve');
        Route::patch('proposals/{proposal}/mark-lost', [\App\Http\Controllers\ProposalController::class, 'markAsLost'])
            ->name('proposals.mark-lost');
        Route::patch('proposals/{proposal}/cancel', [\App\Http\Controllers\ProposalController::class, 'cancel'])
            ->name('proposals.cancel');
        Route::post('proposals/{proposal}/convert-to-work-order', [\App\Http\Controllers\ProposalController::class, 'convertToWorkOrder'])
            ->name('proposals.convert-to-work-order');
        Route::resource('proposals', \App\Http\Controllers\ProposalController::class)
            ->except(['index', 'show']);
        Route::get('reports/sales', fn () => view('reports.sales'))->name('reports.sales');
    });

    // ── Admin only ───────────────────────────────────────────────────────────
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('settings', fn () => view('settings.index'))->name('settings.index');
    });

    // ── All roles (read-only for work orders, proposals, alerts) ─────────────
    Route::get('work-orders', [\App\Http\Controllers\WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('work-orders/{workOrder}', [\App\Http\Controllers\WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::get('proposals', [\App\Http\Controllers\ProposalController::class, 'index'])->name('proposals.index');
    Route::get('proposals/{proposal}', [\App\Http\Controllers\ProposalController::class, 'show'])->name('proposals.show');
    Route::resource('alerts', \App\Http\Controllers\AlertController::class)
        ->only(['index', 'show']);
});

