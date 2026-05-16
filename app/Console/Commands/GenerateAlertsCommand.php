<?php

namespace App\Console\Commands;

use App\Services\AlertGenerationService;
use Illuminate\Console\Command;

class GenerateAlertsCommand extends Command
{
    protected $signature = 'alerts:generate';

    protected $description = 'Generate and update automatic system alerts';

    public function handle(AlertGenerationService $service): int
    {
        $summary = $service->generate();

        $this->info('Alert generation finished.');
        $this->line('Created: '.$summary['created']);
        $this->line('Updated: '.$summary['updated']);
        $this->line('Resolved: '.$summary['resolved']);

        return self::SUCCESS;
    }
}
