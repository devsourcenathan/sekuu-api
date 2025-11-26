<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Instructor

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Payment method
            $table->enum('payment_method', ['bank_transfer', 'paypal', 'stripe'])->default('bank_transfer');
            $table->json('payment_details'); // Bank account, PayPal email, etc.

            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Processing
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->string('transaction_reference')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawals');
    }
};
