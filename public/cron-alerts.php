<?php

$startTime = microtime(true);

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token    = $_GET['token'] ?? '';
$expected = env('CRON_SECRET', '');

if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    exit('Forbidden');
}

$lines = [];
$lines[] = '[' . now()->format('d/m/Y H:i:s') . '] VICZ Cron — Geração de Alertas';
$lines[] = '─────────────────────────────────────';

try {
    $generation = app(\App\Services\AlertGenerationService::class);
    $generation->generate();
    $lines[] = '✔ Alertas gerados com sucesso.';
} catch (\Throwable $e) {
    $lines[] = '✘ Erro ao gerar alertas: ' . $e->getMessage();
}

$elapsed  = round((microtime(true) - $startTime) * 1000);
$lines[]  = '─────────────────────────────────────';
$lines[]  = 'Concluído em ' . $elapsed . 'ms';

// Grava log para verificar histórico de execuções
$logLine = now()->format('d/m/Y H:i:s') . ' | alerts | ' . $elapsed . 'ms | ' . end($lines) . "\n";
file_put_contents(storage_path('logs/cron.log'), $logLine, FILE_APPEND | LOCK_EX);

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $lines) . "\n";
