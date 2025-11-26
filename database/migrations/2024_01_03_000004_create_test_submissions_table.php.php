<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('test_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('cascade');

            // Submission info
            $table->integer('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'expired'])->default('in_progress');

            // Scores
            $table->decimal('score', 5, 2)->nullable(); // Out of 100
            $table->integer('points_earned')->default(0);
            $table->integer('total_points')->default(0);
            $table->boolean('passed')->default(false);

            // Grading
            $table->enum('grade', ['excellent', 'very_good', 'good', 'pass', 'fail'])->nullable();
            $table->text('instructor_comments')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('time_spent_seconds')->default(0);

            // Draft data
            $table->json('draft_answers')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'test_id', 'attempt_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_submissions');
    }
};
