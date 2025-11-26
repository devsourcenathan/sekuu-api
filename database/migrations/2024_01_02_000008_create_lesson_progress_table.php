<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');

            $table->boolean('is_completed')->default(false);
            $table->integer('progress_percentage')->default(0);
            $table->integer('watch_time_seconds')->default(0); // For videos
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['enrollment_id', 'is_completed']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_progress');
    }
};
