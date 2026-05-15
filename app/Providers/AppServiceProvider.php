<?php

namespace App\Providers;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Models\WorkOrder;
use App\Policies\AccountPayablePolicy;
use App\Policies\AccountReceivablePolicy;
use App\Policies\AlertPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\ReportPolicy;
use App\Policies\WorkOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Model → Policy bindings
        Gate::policy(AccountPayable::class, AccountPayablePolicy::class);
        Gate::policy(AccountReceivable::class, AccountReceivablePolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
        Gate::policy(Proposal::class, ProposalPolicy::class);
        Gate::policy(Alert::class, AlertPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        // Report gates (no model, role-based)
        Gate::define('report.viewFinancial', fn ($user) => (new ReportPolicy)->viewFinancial($user));
        Gate::define('report.viewOperational', fn ($user) => (new ReportPolicy)->viewOperational($user));
        Gate::define('report.viewSales', fn ($user) => (new ReportPolicy)->viewSales($user));
        Gate::define('report.viewCashFlow', fn ($user) => (new ReportPolicy)->viewCashFlow($user));
    }
}

