<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');

            // Basic info
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('cover_image')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount_percentage', 5, 2)->default(0); // Auto-calculated vs individual prices

            // Access control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->integer('max_enrollments')->nullable();
            $table->integer('access_duration_days')->nullable(); // null = lifetime access
            $table->timestamp('enrollment_start_date')->nullable();
            $table->timestamp('enrollment_end_date')->nullable();

            // Features
            $table->boolean('has_certificate')->default(false);
            $table->boolean('require_sequential_completion')->default(false);
            $table->json('recommended_order')->nullable(); // Array of course IDs in recommended order

            // Statistics
            $table->integer('total_courses')->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->integer('students_enrolled')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['instructor_id', 'is_active']);
            $table->index('is_public');
        });
    }

    public function down()
    {
        Schema::dropIfExists('packs');
    }
};
