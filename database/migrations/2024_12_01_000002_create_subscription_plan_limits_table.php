<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->string('resource_type'); // courses, sessions, groups, packs, participants_per_session
            $table->integer('limit_value')->default(0); // -1 = unlimited
            $table->timestamps();

            // Ensure one limit per resource type per plan
            $table->unique(['subscription_plan_id', 'resource_type'], 'plan_limits_plan_resource_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_plan_limits');
    }
};
