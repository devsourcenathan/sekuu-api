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
        Schema::table('users', function (Blueprint $table) {
            $table->string('payout_method')->nullable()->after('stripe_customer_id'); // 'bank_transfer', 'mobile_money', 'paypal'
            $table->string('payout_currency', 3)->default('USD')->after('payout_method');
            $table->string('payout_schedule')->default('monthly')->after('payout_currency'); // 'weekly', 'monthly'
            $table->decimal('payout_threshold', 10, 2)->default(50.00)->after('payout_schedule');
            $table->json('payout_details')->nullable()->after('payout_threshold'); // Bank details or Mobile Money info
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'payout_method',
                'payout_currency',
                'payout_schedule',
                'payout_threshold',
                'payout_details',
            ]);
        });
    }
};
