<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->foreignId('proposal_id')
                ->nullable()
                ->after('number')
                ->constrained('proposals')
                ->nullOnDelete();

            $table->unique('proposal_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropUnique(['proposal_id']);
            $table->dropConstrainedForeignId('proposal_id');
        });
    }
};
