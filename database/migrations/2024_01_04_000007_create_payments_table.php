<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // Payment info
            $table->string('transaction_id')->unique();
            $table->enum('payment_gateway', ['stripe', 'paypal'])->default('stripe');
            $table->string('gateway_transaction_id')->nullable();

            // Amount
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('instructor_amount', 10, 2);

            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();

            // Promo code
            $table->foreignId('promo_code_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0);

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('invoice_number')->unique()->nullable();

            // Refund
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('gateway_transaction_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
