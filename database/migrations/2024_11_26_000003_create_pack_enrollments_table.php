<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pack_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pack_id')->constrained()->onDelete('cascade');

            // Enrollment info
            $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Progress tracking
            $table->integer('progress_percentage')->default(0);
            $table->integer('completed_courses')->default(0);
            $table->integer('total_courses')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            // Certificate
            $table->boolean('certificate_issued')->default(false);
            $table->timestamp('certificate_issued_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'pack_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pack_enrollments');
    }
};
