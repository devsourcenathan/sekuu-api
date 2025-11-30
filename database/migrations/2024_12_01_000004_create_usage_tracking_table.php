<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource_type'); // courses, sessions, groups, packs
            $table->integer('current_count')->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            // Ensure one tracking record per resource type per user
            $table->unique(['user_id', 'resource_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usage_tracking');
    }
};
