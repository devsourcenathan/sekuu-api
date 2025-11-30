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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gateway', 50); // 'stripe', 'paypal', 'flutterwave', etc.
            $table->string('gateway_payment_method_id');
            $table->string('type', 50); // 'card', 'bank_account', 'mobile_money', etc.
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->string('brand', 50)->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('gateway');
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
