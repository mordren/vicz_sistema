<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_receivables', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->string('work_order_number')->nullable();
            $table->string('description');
            $table->string('receivable_type');
            $table->date('expected_date');
            $table->decimal('expected_amount', 15, 2);
            $table->date('received_date')->nullable();
            $table->decimal('received_amount', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('EXPECTED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expected_date');
            $table->index('status');
            $table->index('customer');
            $table->index('work_order_id');
            $table->index('receivable_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_receivables');
    }
};
