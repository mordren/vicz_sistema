<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Resumo de alertas</title>
</head>
<body style="font-family:Arial,sans-serif;color:#191c1d;line-height:1.4;">
    <h2 style="margin:0 0 12px;">Resumo automatico de alertas</h2>
    <p style="margin:0 0 16px;">Gerado em {{ now()->format('d/m/Y H:i') }}.</p>

    <h3 style="margin:16px 0 8px;">Contas a pagar nos proximos 3 dias ({{ count($payablesNext3Days) }})</h3>
    @if(empty($payablesNext3Days))
        <p style="margin:0 0 10px;">Nenhuma conta a pagar no periodo.</p>
    @else
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;margin-bottom:14px;">
            <thead>
                <tr>
                    <th align="left">Fornecedor</th>
                    <th align="left">Descricao</th>
                    <th align="left">Vencimento</th>
                    <th align="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payablesNext3Days as $row)
                    <tr>
                        <td>{{ $row['supplier'] ?: '-' }}</td>
                        <td>{{ $row['description'] ?: '-' }}</td>
                        <td>{{ $row['due_date'] ?: '-' }}</td>
                        <td align="right">R$ {{ number_format((float) $row['amount'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3 style="margin:16px 0 8px;">Recebiveis para cobrar ({{ count($receivablesToCollect) }})</h3>
    @if(empty($receivablesToCollect))
        <p style="margin:0 0 10px;">Nenhum recebivel pendente para cobranca no periodo.</p>
    @else
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;margin-bottom:14px;">
            <thead>
                <tr>
                    <th align="left">Cliente</th>
                    <th align="left">Previsao</th>
                    <th align="left">Status</th>
                    <th align="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receivablesToCollect as $row)
                    <tr>
                        <td>{{ $row['customer'] ?: '-' }}</td>
                        <td>{{ $row['expected_date'] ?: '-' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td align="right">R$ {{ number_format((float) $row['expected_amount'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3 style="margin:16px 0 8px;">OS sem atualizacao ha {{ $staleDays }}+ dias ({{ count($staleWorkOrders) }})</h3>
    @if(empty($staleWorkOrders))
        <p style="margin:0 0 10px;">Nenhuma OS sem atualizacao acima do limite.</p>
    @else
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;margin-bottom:14px;">
            <thead>
                <tr>
                    <th align="left">OS</th>
                    <th align="left">Cliente</th>
                    <th align="left">Responsavel</th>
                    <th align="left">Ultima atualizacao</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staleWorkOrders as $row)
                    <tr>
                        <td>{{ $row['number'] }}</td>
                        <td>{{ $row['customer'] ?: '-' }}</td>
                        <td>{{ $row['responsible'] ?: '-' }}</td>
                        <td>{{ $row['updated_at'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top:18px;color:#45464e;font-size:12px;">Email automatico do sistema VICZ Implementos.</p>
</body>
</html>
