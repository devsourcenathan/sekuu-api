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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('formation_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->dateTime('datetime_start');
            $table->dateTime('datetime_end');
            $table->string('livekit_room_name')->unique();
            $table->enum('type', ['cours', 'encadrement', 'rdv'])->default('rdv');
            $table->enum('status', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->boolean('recording_enabled')->default(true);
            $table->integer('max_participants')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('instructor_id');
            $table->index('formation_id');
            $table->index('status');
            $table->index('datetime_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
