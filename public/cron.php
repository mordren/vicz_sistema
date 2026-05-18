<?php

$startTime = microtime(true);

// Bootstrap do Laravel para ter acesso ao .env e aos serviços
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Segurança: só permite chamadas com o token correto
$token = $_GET['token'] ?? '';
$expected = env('CRON_SECRET', '');

if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    exit('Forbidden');
}

$lines = [];
$lines[] = '✅ VICZ Cron — ' . now()->format('d/m/Y H:i:s');
$lines[] = '─────────────────────────────────────';

// 1. Gera alertas
try {
    $generation = app(\App\Services\AlertGenerationService::class);
    $generation->generate();
    $lines[] = '✔ Alertas gerados com sucesso.';
} catch (\Throwable $e) {
    $lines[] = '✘ Erro ao gerar alertas: ' . $e->getMessage();
}

// 2. Envia e-mail de resumo
try {
    $digest = app(\App\Services\AlertDigestEmailService::class);
    $result  = $digest->sendDigest();

    if ($result['recipients'] === 0) {
        $lines[] = '✔ E-mail: nenhum destinatário configurado ou nada para reportar.';
    } else {
        $lines[] = '✔ E-mail enviado para ' . $result['recipients'] . ' destinatário(s).';
        $lines[] = '   · Contas a pagar (3 dias): ' . $result['payables'];
        $lines[] = '   · Recebíveis pendentes:    ' . $result['receivables'];
        $lines[] = '   · OS sem atualização:      ' . $result['workOrders'];
    }
} catch (\Throwable $e) {
    $lines[] = '✘ Erro ao enviar e-mail: ' . $e->getMessage();
}

$elapsed = round((microtime(true) - $startTime) * 1000);
$lines[] = '─────────────────────────────────────';
$lines[] = 'Concluído em ' . $elapsed . 'ms';

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $lines) . "\n";
