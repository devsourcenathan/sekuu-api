<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->enum('type', ['multiple_choice', 'single_choice', 'true_false', 'short_answer', 'long_answer'])->default('single_choice');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');

            $table->string('image_url')->nullable();
            $table->json('options')->nullable(); // Store options as JSON
            $table->json('correct_answers')->nullable();

            $table->json('tags')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_banks');
    }
};
