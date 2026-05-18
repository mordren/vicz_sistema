<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private function persistSettings(array $validated): void
    {
        foreach ($validated as $key => $value) {
            if (is_array($value)) {
                Setting::set($key, json_encode($value, JSON_UNESCAPED_UNICODE));
                continue;
            }

            if (is_bool($value)) {
                Setting::set($key, $value ? '1' : '0');
                continue;
            }

            Setting::set($key, $value);
        }
    }

    public function index(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.index', [
            'settings' => $settings,
        ]);
    }

    public function company(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.company', [
            'settings' => $settings,
        ]);
    }

    public function updateCompany(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_cnpj' => ['nullable', 'string', 'max:18'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_city' => ['nullable', 'string', 'max:255'],
            'company_state' => ['nullable', 'string', 'max:2'],
            'company_zip_code' => ['nullable', 'string', 'max:10'],
            'default_currency' => ['required', 'string', 'max:3'],
            'timezone' => ['required', 'string'],
            'date_format' => ['required', 'string'],
            'number_format' => ['required', 'string'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.company')
            ->with('success', 'Configurações da empresa atualizadas com sucesso.');
    }

    public function financial(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.financial', [
            'settings' => $settings,
        ]);
    }

    public function updateFinancial(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'cash_balance' => ['required', 'numeric', 'min:0'],
            'default_payment_methods' => ['required', 'array'],
            'overdue_days_threshold' => ['required', 'integer', 'min:1'],
            'cash_forecast_period' => ['required', 'integer', 'min:7'],
            'include_proposal_scenarios' => ['nullable', 'boolean'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.financial')
            ->with('success', 'Configurações financeiras atualizadas com sucesso.');
    }

    public function workOrder(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.work-order', [
            'settings' => $settings,
        ]);
    }

    public function updateWorkOrder(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'default_delivery_warning_days' => ['required', 'integer', 'min:1'],
            'require_down_payment_confirmation' => ['nullable', 'boolean'],
            'allow_overpayment' => ['nullable', 'boolean'],
            'auto_create_receivables' => ['required', 'boolean'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.work-order')
            ->with('success', 'Configurações de Ordem de Serviço atualizadas com sucesso.');
    }

    public function proposal(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.proposal', [
            'settings' => $settings,
        ]);
    }

    public function updateProposal(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'default_proposal_probability' => ['required', 'integer', 'min:0', 'max:100'],
            'follow_up_overdue_days' => ['required', 'integer', 'min:1'],
            'auto_close_lost_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.proposal')
            ->with('success', 'Configurações de Proposta atualizadas com sucesso.');
    }

    public function alert(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.alert', [
            'settings' => $settings,
        ]);
    }

    public function updateAlert(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'alert_overdue_enabled' => ['nullable', 'boolean'],
            'alert_proposal_follow_up_enabled' => ['nullable', 'boolean'],
            'alert_delivery_warning_enabled' => ['nullable', 'boolean'],
            'alert_days_before_due' => ['required', 'integer', 'min:0'],
            'alert_email_recipients' => ['nullable', 'string', 'max:1000'],
            'alert_work_order_stale_days' => ['required', 'integer', 'min:1'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.alert')
            ->with('success', 'Configurações de Alertas atualizadas com sucesso.');
    }

    public function attachment(): View
    {
        Gate::authorize('viewSettings', User::class);

        $settings = Setting::all()->keyBy('key');

        return view('settings.attachment', [
            'settings' => $settings,
        ]);
    }

    public function updateAttachment(Request $request)
    {
        Gate::authorize('updateSettings', User::class);

        $validated = $request->validate([
            'max_attachment_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'allowed_file_extensions' => ['required', 'string'],
        ]);

        $this->persistSettings($validated);

        return redirect()
            ->route('settings.attachment')
            ->with('success', 'Configurações de Anexos atualizadas com sucesso.');
    }

    public function cronLog(): \Illuminate\Http\Response
    {
        Gate::authorize('viewSettings', User::class);

        $path  = storage_path('logs/cron.log');
        $lines = [];

        if (file_exists($path)) {
            $lines = array_reverse(array_filter(
                array_map('rtrim', file($path)),
                static fn (string $l): bool => $l !== '',
            ));
        }

        return response()->view('settings.cron-log', ['lines' => $lines]);
    }
}
