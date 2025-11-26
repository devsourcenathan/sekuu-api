<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

            // Basic info
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('what_you_will_learn')->nullable();
            $table->text('requirements')->nullable();
            $table->text('target_audience')->nullable();

            // Media
            $table->string('cover_image')->nullable();
            $table->text('presentation_text')->nullable();
            $table->string('presentation_video_url')->nullable();
            $table->enum('presentation_video_type', ['youtube', 'vimeo', 'local'])->nullable();

            // Course settings
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->string('language', 10)->default('fr');
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])->default('draft');

            // Pricing
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->timestamp('discount_start_date')->nullable();
            $table->timestamp('discount_end_date')->nullable();

            // Access control
            $table->boolean('is_public')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->integer('max_students')->nullable();
            $table->timestamp('enrollment_start_date')->nullable();
            $table->timestamp('enrollment_end_date')->nullable();
            $table->integer('access_duration_days')->nullable(); // null = lifetime

            // Content settings
            $table->boolean('allow_download')->default(false);
            $table->boolean('has_certificate')->default(false);
            $table->boolean('has_forum')->default(false);

            // Statistics
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('students_enrolled')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_public']);
            $table->index('instructor_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
