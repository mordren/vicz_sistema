<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ——————————————————————————————————————————
        // 1. USERS
        // ——————————————————————————————————————————
        $admin = User::factory()->admin()->create([
            'name' => 'Carlos Eduardo — Admin',
            'email' => 'admin@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        $finance = User::factory()->finance()->create([
            'name' => 'Ana Lima — Financeiro',
            'email' => 'financeiro@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        $operations = User::factory()->operations()->create([
            'name' => 'Roberto Silva — Operações',
            'email' => 'operacoes@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        $sales = User::factory()->sales()->create([
            'name' => 'Fernanda Souza — Comercial',
            'email' => 'comercial@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        // ——————————————————————————————————————————
        // 2. WORK ORDERS
        // ——————————————————————————————————————————
        $woInProduction = WorkOrder::factory()->create([
            'number' => 'OS-00001',
            'customer' => 'Fazenda São Lucas Ltda',
            'service_type' => 'Carreta Graneleira 30 Ton',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(35),
            'expected_delivery_date' => Carbon::now()->addDays(25),
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'progress_percentage' => 45,
            'contracted_amount' => '185000.00',
            'down_payment_amount' => '74000.00',
            'received_amount' => '74000.00',
            'receivable_balance' => '111000.00',
            'estimated_cost' => '128000.00',
            'actual_cost' => '52000.00',
            'estimated_margin' => '0.3081',
            'actual_margin' => '0.0000',
            'created_by' => $admin->id,
        ]);

        $woWelding = WorkOrder::factory()->create([
            'number' => 'OS-00002',
            'customer' => 'Agropecuária Cerrado Sul S.A.',
            'service_type' => 'Subsolador 7 Hastes',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(20),
            'expected_delivery_date' => Carbon::now()->addDays(10),
            'status' => WorkOrderStatus::WELDING,
            'progress_percentage' => 65,
            'contracted_amount' => '42000.00',
            'down_payment_amount' => '16800.00',
            'received_amount' => '16800.00',
            'receivable_balance' => '25200.00',
            'estimated_cost' => '28000.00',
            'actual_cost' => '18000.00',
            'estimated_margin' => '0.3333',
            'actual_margin' => '0.0000',
            'created_by' => $admin->id,
        ]);

        $woPainting = WorkOrder::factory()->create([
            'number' => 'OS-00003',
            'customer' => 'Cooperativa Central do Oeste',
            'service_type' => 'Grade Aradora 20 Discos',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(55),
            'expected_delivery_date' => Carbon::now()->addDays(5),
            'status' => WorkOrderStatus::PAINTING,
            'progress_percentage' => 85,
            'contracted_amount' => '58000.00',
            'down_payment_amount' => '23200.00',
            'received_amount' => '23200.00',
            'receivable_balance' => '34800.00',
            'estimated_cost' => '39000.00',
            'actual_cost' => '34000.00',
            'estimated_margin' => '0.3276',
            'actual_margin' => '0.0000',
            'created_by' => $admin->id,
        ]);

        $woOverdue = WorkOrder::factory()->create([
            'number' => 'OS-00004',
            'customer' => 'Grãos do Triângulo',
            'service_type' => 'Reboque Agrícola Basculante',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(90),
            'expected_delivery_date' => Carbon::now()->subDays(15),
            'status' => WorkOrderStatus::ASSEMBLY,
            'progress_percentage' => 75,
            'contracted_amount' => '76000.00',
            'down_payment_amount' => '30400.00',
            'received_amount' => '30400.00',
            'receivable_balance' => '45600.00',
            'estimated_cost' => '52000.00',
            'actual_cost' => '41000.00',
            'estimated_margin' => '0.3158',
            'actual_margin' => '0.0000',
            'created_by' => $admin->id,
        ]);

        $woReady = WorkOrder::factory()->create([
            'number' => 'OS-00005',
            'customer' => 'Usina Boa Esperança',
            'service_type' => 'Implemento Pulverizador 3000L',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(75),
            'expected_delivery_date' => Carbon::now()->addDays(2),
            'status' => WorkOrderStatus::READY_FOR_DELIVERY,
            'progress_percentage' => 100,
            'contracted_amount' => '210000.00',
            'down_payment_amount' => '84000.00',
            'received_amount' => '84000.00',
            'receivable_balance' => '126000.00',
            'estimated_cost' => '148000.00',
            'actual_cost' => '152000.00',
            'estimated_margin' => '0.2952',
            'actual_margin' => '0.2762',
            'created_by' => $admin->id,
        ]);

        $woDelivered1 = WorkOrder::factory()->create([
            'number' => 'OS-00006',
            'customer' => 'Pecuária Pantanal S.A.',
            'service_type' => 'Plantadeira 11 Linhas',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(120),
            'expected_delivery_date' => Carbon::now()->subDays(10),
            'actual_delivery_date' => Carbon::now()->subDays(12),
            'status' => WorkOrderStatus::DELIVERED,
            'progress_percentage' => 100,
            'contracted_amount' => '320000.00',
            'down_payment_amount' => '128000.00',
            'received_amount' => '320000.00',
            'receivable_balance' => '0.00',
            'estimated_cost' => '235000.00',
            'actual_cost' => '228000.00',
            'estimated_margin' => '0.2656',
            'actual_margin' => '0.2875',
            'created_by' => $admin->id,
        ]);

        $woDelivered2 = WorkOrder::factory()->create([
            'number' => 'OS-00007',
            'customer' => 'JBS Implementos Agro',
            'service_type' => 'Manutenção Geral',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(45),
            'expected_delivery_date' => Carbon::now()->subDays(5),
            'actual_delivery_date' => Carbon::now()->subDays(5),
            'status' => WorkOrderStatus::DELIVERED,
            'progress_percentage' => 100,
            'contracted_amount' => '28000.00',
            'down_payment_amount' => '0.00',
            'received_amount' => '28000.00',
            'receivable_balance' => '0.00',
            'estimated_cost' => '18000.00',
            'actual_cost' => '17500.00',
            'estimated_margin' => '0.3571',
            'actual_margin' => '0.3750',
            'created_by' => $admin->id,
        ]);

        $woWaiting = WorkOrder::factory()->create([
            'number' => 'OS-00008',
            'customer' => 'Grupo Agromax',
            'service_type' => 'Carreta Graneleira 40 Ton',
            'responsible_user_id' => $operations->id,
            'opened_at' => Carbon::now()->subDays(5),
            'expected_delivery_date' => Carbon::now()->addDays(60),
            'status' => WorkOrderStatus::WAITING_DOWN_PAYMENT,
            'progress_percentage' => 0,
            'contracted_amount' => '240000.00',
            'down_payment_amount' => '96000.00',
            'received_amount' => '0.00',
            'receivable_balance' => '240000.00',
            'estimated_cost' => '165000.00',
            'actual_cost' => '0.00',
            'estimated_margin' => '0.3125',
            'actual_margin' => '0.0000',
            'created_by' => $admin->id,
        ]);

        // ——————————————————————————————————————————
        // 3. ACCOUNT PAYABLES
        // ——————————————————————————————————————————
        AccountPayable::factory()->dueToday()->create([
            'supplier' => 'Aço Minas Distribuidora Ltda',
            'description' => 'Chapas de aço carbono — Pedido #4821',
            'category' => PayableCategory::METAL_SHEET,
            'amount' => '18500.00',
            'work_order_id' => $woInProduction->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->dueTomorrow()->create([
            'supplier' => 'Hidráulica Agrícola Center',
            'description' => 'Cilindros hidráulicos e mangueiras',
            'category' => PayableCategory::HYDRAULIC,
            'amount' => '8750.00',
            'work_order_id' => $woWelding->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->overdue()->create([
            'supplier' => 'Tintas Industriais Horizonte',
            'description' => 'Tinta poliuretano 2-componentes — 50L',
            'category' => PayableCategory::PAINTING,
            'due_date' => Carbon::now()->subDays(8)->toDateString(),
            'amount' => '4200.00',
            'work_order_id' => $woPainting->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->overdue()->create([
            'supplier' => 'Indústria Metálica Paraná S.A.',
            'description' => 'Perfis estruturais U200 — 2 ton',
            'category' => PayableCategory::PROFILE,
            'due_date' => Carbon::now()->subDays(22)->toDateString(),
            'amount' => '12300.00',
            'work_order_id' => $woOverdue->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->paid()->create([
            'supplier' => 'Pneumáticos do Cerrado',
            'description' => 'Pneus agrícolas 18.4-30 — 4 unid.',
            'category' => PayableCategory::TIRES,
            'amount' => '9600.00',
            'payment_date' => Carbon::now()->subDays(10)->toDateString(),
            'paid_amount' => '9600.00',
            'work_order_id' => $woReady->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->paid()->create([
            'supplier' => 'Folha Color Pinturas Ind.',
            'description' => 'Serviço de jateamento e pintura eletrostática',
            'category' => PayableCategory::PAINTING,
            'amount' => '7800.00',
            'payment_date' => Carbon::now()->subDays(7)->toDateString(),
            'paid_amount' => '7800.00',
            'work_order_id' => $woDelivered1->id,
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->open()->count(5)->create(['created_by' => $finance->id]);

        AccountPayable::factory()->create([
            'supplier' => 'Folha de Salários — Maio/2026',
            'description' => 'Pagamento de salários e encargos — competência maio/2026',
            'category' => PayableCategory::PAYROLL,
            'issue_date' => Carbon::now()->startOfMonth()->toDateString(),
            'due_date' => Carbon::now()->endOfMonth()->toDateString(),
            'amount' => '85000.00',
            'status' => PayableStatus::OPEN,
            'cost_center' => 'ADMINISTRATIVO',
            'created_by' => $finance->id,
        ]);

        AccountPayable::factory()->cancelled()->create([
            'supplier' => 'Elétrica e Automação Oeste',
            'description' => 'Painel elétrico — pedido cancelado',
            'category' => PayableCategory::ELECTRICAL,
            'amount' => '5200.00',
            'created_by' => $finance->id,
        ]);

        // ——————————————————————————————————————————
        // 4. ACCOUNT RECEIVABLES
        // ——————————————————————————————————————————
        AccountReceivable::factory()->expected()->create([
            'customer' => 'Grupo Agromax',
            'work_order_id' => $woWaiting->id,
            'work_order_number' => $woWaiting->number,
            'description' => 'Entrada 40% — Carreta Graneleira 40 Ton',
            'receivable_type' => ReceivableType::DOWN_PAYMENT,
            'expected_date' => Carbon::now()->addDays(3)->toDateString(),
            'expected_amount' => '96000.00',
            'status' => ReceivableStatus::EXPECTED,
            'created_by' => $admin->id,
        ]);

        AccountReceivable::factory()->dueToday()->create([
            'customer' => 'Usina Boa Esperança',
            'work_order_id' => $woReady->id,
            'work_order_number' => $woReady->number,
            'description' => 'Saldo final na entrega — Pulverizador 3000L',
            'receivable_type' => ReceivableType::FINAL_BALANCE,
            'expected_date' => Carbon::now()->toDateString(),
            'expected_amount' => '126000.00',
            'status' => ReceivableStatus::EXPECTED,
            'created_by' => $admin->id,
        ]);

        AccountReceivable::factory()->overdue()->create([
            'customer' => 'Grãos do Triângulo',
            'work_order_id' => $woOverdue->id,
            'work_order_number' => $woOverdue->number,
            'description' => 'Parcela 2/3 — Reboque Agrícola',
            'receivable_type' => ReceivableType::INSTALLMENT,
            'expected_date' => Carbon::now()->subDays(12)->toDateString(),
            'expected_amount' => '22800.00',
            'status' => ReceivableStatus::OVERDUE,
            'created_by' => $admin->id,
        ]);

        AccountReceivable::factory()->received()->create([
            'customer' => 'Pecuária Pantanal S.A.',
            'work_order_id' => $woDelivered1->id,
            'work_order_number' => $woDelivered1->number,
            'description' => 'Saldo final — Plantadeira 11 Linhas',
            'receivable_type' => ReceivableType::FINAL_BALANCE,
            'expected_date' => Carbon::now()->subDays(12)->toDateString(),
            'expected_amount' => '192000.00',
            'received_date' => Carbon::now()->subDays(12)->toDateString(),
            'received_amount' => '192000.00',
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => ReceivableStatus::RECEIVED,
            'created_by' => $admin->id,
        ]);

        AccountReceivable::factory()->received()->create([
            'customer' => 'JBS Implementos Agro',
            'work_order_id' => $woDelivered2->id,
            'work_order_number' => $woDelivered2->number,
            'description' => 'Pagamento total — Manutenção Geral',
            'receivable_type' => ReceivableType::COMPLETION_PAYMENT,
            'expected_date' => Carbon::now()->subDays(5)->toDateString(),
            'expected_amount' => '28000.00',
            'received_date' => Carbon::now()->subDays(5)->toDateString(),
            'received_amount' => '28000.00',
            'payment_method' => PaymentMethod::PIX,
            'status' => ReceivableStatus::RECEIVED,
            'created_by' => $admin->id,
        ]);

        AccountReceivable::factory()->expected()->count(4)->create(['created_by' => $admin->id]);

        AccountReceivable::factory()->cancelled()->create([
            'customer' => 'Cooperativa Central do Oeste',
            'description' => 'Parcela cancelada por renegociação',
            'receivable_type' => ReceivableType::INSTALLMENT,
            'expected_amount' => '15000.00',
            'created_by' => $admin->id,
        ]);

        // ——————————————————————————————————————————
        // 5. PROPOSALS
        // ——————————————————————————————————————————
        Proposal::factory()->negotiating()->create([
            'number' => 'PROP-0001',
            'customer' => 'Fazenda Nova Esperança',
            'product_or_service_type' => 'Carreta Graneleira 35 Ton',
            'amount' => '195000.00',
            'close_probability' => 75,
            'weighted_amount' => '146250.00',
            'sales_owner_id' => $sales->id,
            'sent_at' => Carbon::now()->subDays(18)->toDateString(),
            'expected_close_date' => Carbon::now()->addDays(12)->toDateString(),
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->sent()->create([
            'number' => 'PROP-0002',
            'customer' => 'Irrigação Norte Ltda',
            'product_or_service_type' => 'Implemento Pulverizador 2500L',
            'amount' => '178000.00',
            'close_probability' => 50,
            'weighted_amount' => '89000.00',
            'sales_owner_id' => $sales->id,
            'sent_at' => Carbon::now()->subDays(7)->toDateString(),
            'expected_close_date' => Carbon::now()->addDays(30)->toDateString(),
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->approved()->create([
            'number' => 'PROP-0003',
            'customer' => 'Grupo Agromax',
            'product_or_service_type' => 'Carreta Graneleira 40 Ton',
            'amount' => '240000.00',
            'close_probability' => 100,
            'weighted_amount' => '240000.00',
            'sales_owner_id' => $sales->id,
            'sent_at' => Carbon::now()->subDays(30)->toDateString(),
            'expected_close_date' => Carbon::now()->subDays(5)->toDateString(),
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->lost()->create([
            'number' => 'PROP-0004',
            'customer' => 'Cooperativa Sul Grãos',
            'product_or_service_type' => 'Plantadeira 13 Linhas',
            'amount' => '285000.00',
            'close_probability' => 0,
            'weighted_amount' => '0.00',
            'sales_owner_id' => $sales->id,
            'sent_at' => Carbon::now()->subDays(45)->toDateString(),
            'expected_close_date' => Carbon::now()->subDays(15)->toDateString(),
            'loss_reason' => 'Preço acima do mercado',
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->negotiating()->count(3)->create([
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->sent()->count(2)->create([
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
        ]);

        Proposal::factory()->cancelled()->create([
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
        ]);

        // ——————————————————————————————————————————
        // 6. ALERTS
        // ——————————————————————————————————————————
        Alert::create([
            'type' => 'OVERDUE_PAYABLE',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Conta a pagar vencida há 22 dias',
            'message' => 'Fornecedor "Indústria Metálica Paraná S.A." — R$ 12.300,00 — vencida em ' . Carbon::now()->subDays(22)->format('d/m/Y'),
            'entity_type' => AccountPayable::class,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'OVERDUE_RECEIVABLE',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'Cobrança em atraso — Grãos do Triângulo',
            'message' => 'Parcela 2/3 de R$ 22.800,00 esperada em ' . Carbon::now()->subDays(12)->format('d/m/Y') . ' não foi recebida.',
            'entity_type' => AccountReceivable::class,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'OVERDUE_WORK_ORDER',
            'severity' => AlertSeverity::CRITICAL,
            'title' => 'OS-00004 em atraso de entrega',
            'message' => 'Ordem de serviço para "Grãos do Triângulo" ultrapassou a data de entrega prevista em 15 dias.',
            'entity_type' => WorkOrder::class,
            'entity_id' => $woOverdue->id,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'DUE_TODAY',
            'severity' => AlertSeverity::WARNING,
            'title' => 'Conta a pagar vence hoje',
            'message' => 'Aço Minas Distribuidora — Chapas de aço carbono — R$ 18.500,00 vence hoje.',
            'entity_type' => AccountPayable::class,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'READY_FOR_DELIVERY',
            'severity' => AlertSeverity::INFO,
            'title' => 'OS-00005 pronta para entrega',
            'message' => 'Implemento Pulverizador 3000L para "Usina Boa Esperança" está pronto para entrega. Saldo a receber: R$ 126.000,00.',
            'entity_type' => WorkOrder::class,
            'entity_id' => $woReady->id,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'DOWN_PAYMENT_PENDING',
            'severity' => AlertSeverity::WARNING,
            'title' => 'Aguardando entrada — OS-00008',
            'message' => 'Ordem de serviço para "Grupo Agromax" aguarda pagamento de entrada de R$ 96.000,00.',
            'entity_type' => WorkOrder::class,
            'entity_id' => $woWaiting->id,
            'status' => AlertStatus::OPEN,
        ]);

        Alert::create([
            'type' => 'OVERDUE_PAYABLE',
            'severity' => AlertSeverity::WARNING,
            'title' => 'Conta vencida resolvida',
            'message' => 'Pneumáticos do Cerrado — pagamento efetuado.',
            'entity_type' => AccountPayable::class,
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => Carbon::now()->subDays(10),
        ]);

        // ——————————————————————————————————————————
        // 7. AUDIT LOGS
        // ——————————————————————————————————————————
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => WorkOrder::class,
            'auditable_id' => $woInProduction->id,
            'before' => null,
            'after' => ['status' => 'QUOTE_APPROVED'],
        ]);

        AuditLog::create([
            'user_id' => $operations->id,
            'action' => 'updated',
            'auditable_type' => WorkOrder::class,
            'auditable_id' => $woInProduction->id,
            'before' => ['status' => 'WAITING_MATERIAL_PURCHASE', 'progress_percentage' => 0],
            'after' => ['status' => 'IN_PRODUCTION', 'progress_percentage' => 45],
        ]);

        AuditLog::create([
            'user_id' => $finance->id,
            'action' => 'paid',
            'auditable_type' => AccountPayable::class,
            'auditable_id' => 1,
            'before' => ['status' => 'OPEN', 'paid_amount' => null],
            'after' => ['status' => 'PAID', 'paid_amount' => '9600.00'],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'delivered',
            'auditable_type' => WorkOrder::class,
            'auditable_id' => $woDelivered1->id,
            'before' => ['status' => 'READY_FOR_DELIVERY'],
            'after' => ['status' => 'DELIVERED', 'actual_delivery_date' => Carbon::now()->subDays(12)->toDateString()],
        ]);

        // ——————————————————————————————————————————
        // 8. SETTINGS
        // ——————————————————————————————————————————
        Setting::set('company_name', 'VICZ Implementos');
        Setting::set('currency', 'BRL');
        Setting::set('alert_overdue_days', '0');
        Setting::set('alert_due_warning_days', '3');
        Setting::set('work_order_number_prefix', 'OS-');
        Setting::set('proposal_number_prefix', 'PROP-');
    }
}
