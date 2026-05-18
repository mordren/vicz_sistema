<?php

namespace App\Console\Commands;

use App\Services\AlertDigestEmailService;
use App\Services\AlertGenerationService;
use Illuminate\Console\Command;

class GenerateAlertsCommand extends Command
{
    protected $signature = 'alerts:generate {--notify-email : Envia email resumo apos gerar alertas}';

    protected $description = 'Generate and update automatic system alerts';

    public function handle(AlertGenerationService $service, AlertDigestEmailService $digestEmailService): int
    {
        $summary = $service->generate();

        $this->info('Alert generation finished.');
        $this->line('Created: '.$summary['created']);
        $this->line('Updated: '.$summary['updated']);
        $this->line('Resolved: '.$summary['resolved']);

        if ($this->option('notify-email')) {
            $emailSummary = $digestEmailService->sendDigest();
            $this->line('Email recipients: '.$emailSummary['recipients']);
            $this->line('Email payables: '.$emailSummary['payables']);
            $this->line('Email receivables: '.$emailSummary['receivables']);
            $this->line('Email stale work orders: '.$emailSummary['workOrders']);
        }

        return self::SUCCESS;
    }
}