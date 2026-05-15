<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_payables', function (Blueprint $table) {
            $table->id();
            $table->string('supplier');
            $table->string('description');
            $table->string('category');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->unsignedSmallInteger('total_installments')->default(1);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('OPEN');
            $table->date('payment_date')->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('cost_center')->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('due_date');
            $table->index('status');
            $table->index('supplier');
            $table->index('work_order_id');
            $table->index('category');
            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payables');
    }
};
