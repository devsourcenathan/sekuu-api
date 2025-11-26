<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('test_submissions')->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');

            // Answer data
            $table->text('answer_text')->nullable();
            $table->json('selected_options')->nullable(); // For multiple choice
            $table->string('file_path')->nullable(); // For file uploads
            $table->string('audio_path')->nullable(); // For audio answers
            $table->string('video_path')->nullable(); // For video answers

            // Grading
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->default(0);
            $table->integer('points_possible')->default(0);
            $table->text('feedback')->nullable(); // Instructor feedback
            $table->boolean('requires_manual_review')->default(false);

            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('submission_answers');
    }
};
