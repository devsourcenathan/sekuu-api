<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_subscription_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('user_subscriptions')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_subscription_id']);
            $table->dropColumn('current_subscription_id');
        });
    }
};
