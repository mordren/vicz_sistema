<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('proposal_id')->constrained('customers')->nullOnDelete();
            $table->index('customer_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('number')->constrained('customers')->nullOnDelete();
            $table->index('customer_id');
        });

        Schema::table('account_receivables', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->nullOnDelete();
            $table->index('customer_id');
        });

        $this->backfillCustomers();
    }

    public function down(): void
    {
        Schema::table('account_receivables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }

    private function backfillCustomers(): void
    {
        $names = collect()
            ->merge(DB::table('work_orders')->whereNotNull('customer')->pluck('customer'))
            ->merge(DB::table('proposals')->whereNotNull('customer')->pluck('customer'))
            ->merge(DB::table('account_receivables')->whereNotNull('customer')->pluck('customer'))
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn ($name) => trim((string) $name))
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $now = now();
        $payload = $names->map(fn (string $name) => [
            'type' => 'COMPANY',
            'name' => $name,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('customers')->insert($payload);

        $customerMap = DB::table('customers')->pluck('id', 'name');

        DB::table('work_orders')->select(['id', 'customer'])->orderBy('id')->chunk(200, function ($rows) use ($customerMap): void {
            foreach ($rows as $row) {
                if (! is_string($row->customer)) {
                    continue;
                }

                $id = $customerMap->get(trim($row->customer));
                if ($id !== null) {
                    DB::table('work_orders')->where('id', $row->id)->update(['customer_id' => $id]);
                }
            }
        });

        DB::table('proposals')->select(['id', 'customer'])->orderBy('id')->chunk(200, function ($rows) use ($customerMap): void {
            foreach ($rows as $row) {
                if (! is_string($row->customer)) {
                    continue;
                }

                $id = $customerMap->get(trim($row->customer));
                if ($id !== null) {
                    DB::table('proposals')->where('id', $row->id)->update(['customer_id' => $id]);
                }
            }
        });

        DB::table('account_receivables')->select(['id', 'customer'])->orderBy('id')->chunk(200, function ($rows) use ($customerMap): void {
            foreach ($rows as $row) {
                if (! is_string($row->customer)) {
                    continue;
                }

                $id = $customerMap->get(trim($row->customer));
                if ($id !== null) {
                    DB::table('account_receivables')->where('id', $row->id)->update(['customer_id' => $id]);
                }
            }
        });
    }
};
