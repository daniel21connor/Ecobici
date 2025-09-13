<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('plan_type', ['basic', 'premium', 'vip'])->default('basic');
            $table->decimal('amount', 8, 2);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'paypal', 'bank_transfer']);
            $table->string('card_last_four', 4)->nullable();
            $table->string('transaction_id')->unique();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('features')->nullable(); // Características del plan
            $table->text('payment_details')->nullable(); // Detalles adicionales del pago
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index('transaction_id');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
