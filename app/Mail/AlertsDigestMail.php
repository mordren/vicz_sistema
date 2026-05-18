<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertsDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array<string, mixed>> $payablesNext3Days
     * @param array<int, array<string, mixed>> $receivablesToCollect
     * @param array<int, array<string, mixed>> $staleWorkOrders
     */
    public function __construct(
        public readonly array $payablesNext3Days,
        public readonly array $receivablesToCollect,
        public readonly array $staleWorkOrders,
        public readonly int $staleDays,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Resumo de alertas: pagar, cobrar e OS sem atualizacao')
            ->view('emails.alerts-digest');
    }
}
