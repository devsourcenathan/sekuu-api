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
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['host', 'cohost', 'participant'])->default('participant');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->timestamps();

            // Unique constraint: un utilisateur ne peut être qu'une fois dans une session
            $table->unique(['session_id', 'user_id']);

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
