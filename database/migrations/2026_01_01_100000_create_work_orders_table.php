<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('customer');
            $table->string('service_type');
            $table->text('description')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('opened_at');
            $table->date('expected_delivery_date');
            $table->date('recalculated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('status')->default('QUOTE_APPROVED');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->decimal('contracted_amount', 15, 2)->default(0);
            $table->decimal('down_payment_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->decimal('receivable_balance', 15, 2)->default(0);
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->decimal('estimated_margin', 8, 4)->default(0);
            $table->decimal('actual_margin', 8, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('status');
            $table->index('customer');
            $table->index('responsible_user_id');
            $table->index('expected_delivery_date');
            $table->index('opened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
