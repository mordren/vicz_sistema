<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('customer');
            $table->string('product_or_service_type');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('sent_at')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->unsignedTinyInteger('close_probability')->default(50);
            $table->decimal('weighted_amount', 15, 2)->default(0);
            $table->foreignId('sales_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('SENT');
            $table->text('loss_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('status');
            $table->index('customer');
            $table->index('expected_close_date');
            $table->index('sales_owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
