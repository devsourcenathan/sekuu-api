<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meeting_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('formation_id')->constrained('courses')->onDelete('cascade');
            $table->text('message');
            $table->enum('status', ['en_attente', 'acceptee', 'refusee', 'annulee'])->default('en_attente');
            $table->dateTime('datetime_proposed')->nullable();
            $table->dateTime('datetime_final')->nullable();
            $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('student_id');
            $table->index('instructor_id');
            $table->index('formation_id');
            $table->index('status');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_requests');
    }
};
