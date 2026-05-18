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
$lines[] = '[' . now()->format('d/m/Y H:i:s') . '] VICZ Cron — E-mail Resumo Diário';
$lines[] = '─────────────────────────────────────';

try {
    $digest = app(\App\Services\AlertDigestEmailService::class);
    $result = $digest->sendDigest();

    if ($result['recipients'] === 0) {
        $lines[] = '✔ Nenhum destinatário configurado ou nada para reportar.';
    } else {
        $lines[] = '✔ E-mail enviado para ' . $result['recipients'] . ' destinatário(s).';
        $lines[] = '   · Contas a pagar (3 dias): ' . $result['payables'];
        $lines[] = '   · Recebíveis pendentes:    ' . $result['receivables'];
        $lines[] = '   · OS sem atualização:      ' . $result['workOrders'];
    }
} catch (\Throwable $e) {
    $lines[] = '✘ Erro ao enviar e-mail: ' . $e->getMessage();
}

$elapsed  = round((microtime(true) - $startTime) * 1000);
$lines[]  = '─────────────────────────────────────';
$lines[]  = 'Concluído em ' . $elapsed . 'ms';

// Grava log para verificar histórico de execuções
$status  = isset($e) ? 'ERRO: ' . $e->getMessage() : 'OK recipients=' . $result['recipients'];
$logLine = now()->format('d/m/Y H:i:s') . ' | email  | ' . $elapsed . 'ms | ' . $status . "\n";
file_put_contents(storage_path('logs/cron.log'), $logLine, FILE_APPEND | LOCK_EX);

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $lines) . "\n";
