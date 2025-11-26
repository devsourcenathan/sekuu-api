<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');

            // Question details
            $table->text('question_text');
            $table->text('explanation')->nullable(); // Shown after answer
            $table->enum('type', ['multiple_choice', 'single_choice', 'true_false', 'short_answer', 'long_answer', 'audio', 'video', 'file_upload'])->default('single_choice');

            // Media
            $table->string('image_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('video_url')->nullable();

            // Configuration
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);

            // For automatic grading
            $table->boolean('requires_manual_grading')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('questions');
    }
};
