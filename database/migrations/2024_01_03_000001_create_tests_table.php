<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->morphs('testable'); // Can belong to course, chapter, or lesson
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();

            // Test type
            $table->enum('type', ['formative', 'summative'])->default('formative');
            $table->enum('position', ['after_lesson', 'after_chapter', 'end_of_course'])->default('after_lesson');

            // Configuration
            $table->integer('duration_minutes')->nullable(); // null = unlimited
            $table->integer('max_attempts')->default(3); // 0 = unlimited
            $table->integer('passing_score')->default(70); // Percentage
            $table->boolean('show_results_immediately')->default(true);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('one_question_per_page')->default(false);
            $table->boolean('allow_back_navigation')->default(true);
            $table->boolean('auto_save_draft')->default(true);

            // Validation
            $table->enum('validation_type', ['automatic', 'manual', 'mixed'])->default('automatic');
            $table->boolean('is_published')->default(false);

            // Anti-cheat
            $table->boolean('disable_copy_paste')->default(false);
            $table->boolean('full_screen_required')->default(false);
            $table->boolean('webcam_monitoring')->default(false);

            // Statistics
            $table->integer('total_questions')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('attempts_count')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tests');
    }
};
