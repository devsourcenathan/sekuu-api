<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename formation_id to course_id in sessions table
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropIndex(['formation_id']);
        });
        
        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('formation_id', 'course_id');
        });
        
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
            $table->index('course_id');
        });

        // 2. Update session type enum values
        DB::statement("ALTER TABLE sessions MODIFY COLUMN type ENUM('course', 'mentoring', 'meeting') DEFAULT 'meeting'");
        
        // Update existing data
        DB::table('sessions')->where('type', 'cours')->update(['type' => 'course']);
        DB::table('sessions')->where('type', 'encadrement')->update(['type' => 'mentoring']);
        DB::table('sessions')->where('type', 'rdv')->update(['type' => 'meeting']);

        // 3. Update session status enum values
        DB::statement("ALTER TABLE sessions MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled'");
        
        // Update existing data
        DB::table('sessions')->where('status', 'planifiee')->update(['status' => 'scheduled']);
        DB::table('sessions')->where('status', 'en_cours')->update(['status' => 'in_progress']);
        DB::table('sessions')->where('status', 'terminee')->update(['status' => 'completed']);
        DB::table('sessions')->where('status', 'annulee')->update(['status' => 'cancelled']);

        // 4. Rename formation_id to course_id in groups table
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropIndex(['formation_id']);
        });
        
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('formation_id', 'course_id');
        });
        
        Schema::table('groups', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
            $table->index('course_id');
        });

        // 5. Rename formation_id to course_id in meeting_requests table
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropIndex(['formation_id']);
        });
        
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->renameColumn('formation_id', 'course_id');
        });
        
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->index('course_id');
        });

        // 6. Update meeting_requests status enum values
        DB::statement("ALTER TABLE meeting_requests MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending'");
        
        // Update existing data
        DB::table('meeting_requests')->where('status', 'en_attente')->update(['status' => 'pending']);
        DB::table('meeting_requests')->where('status', 'acceptee')->update(['status' => 'accepted']);
        DB::table('meeting_requests')->where('status', 'refusee')->update(['status' => 'rejected']);
        DB::table('meeting_requests')->where('status', 'annulee')->update(['status' => 'cancelled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes (formation_id, French enums)
        
        // 1. Revert meeting_requests status
        DB::table('meeting_requests')->where('status', 'pending')->update(['status' => 'en_attente']);
        DB::table('meeting_requests')->where('status', 'accepted')->update(['status' => 'acceptee']);
        DB::table('meeting_requests')->where('status', 'rejected')->update(['status' => 'refusee']);
        DB::table('meeting_requests')->where('status', 'cancelled')->update(['status' => 'annulee']);
        
        DB::statement("ALTER TABLE meeting_requests MODIFY COLUMN status ENUM('en_attente', 'acceptee', 'refusee', 'annulee') DEFAULT 'en_attente'");

        // 2. Rename course_id back to formation_id in meeting_requests
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['course_id']);
        });
        
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->renameColumn('course_id', 'formation_id');
        });
        
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->foreign('formation_id')->references('id')->on('courses')->onDelete('cascade');
            $table->index('formation_id');
        });

        // 3. Rename course_id back to formation_id in groups
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['course_id']);
        });
        
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('course_id', 'formation_id');
        });
        
        Schema::table('groups', function (Blueprint $table) {
            $table->foreign('formation_id')->references('id')->on('courses')->onDelete('set null');
            $table->index('formation_id');
        });

        // 4. Revert session status
        DB::table('sessions')->where('status', 'scheduled')->update(['status' => 'planifiee']);
        DB::table('sessions')->where('status', 'in_progress')->update(['status' => 'en_cours']);
        DB::table('sessions')->where('status', 'completed')->update(['status' => 'terminee']);
        DB::table('sessions')->where('status', 'cancelled')->update(['status' => 'annulee']);
        
        DB::statement("ALTER TABLE sessions MODIFY COLUMN status ENUM('planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'planifiee'");

        // 5. Revert session type
        DB::table('sessions')->where('type', 'course')->update(['type' => 'cours']);
        DB::table('sessions')->where('type', 'mentoring')->update(['type' => 'encadrement']);
        DB::table('sessions')->where('type', 'meeting')->update(['type' => 'rdv']);
        
        DB::statement("ALTER TABLE sessions MODIFY COLUMN type ENUM('cours', 'encadrement', 'rdv') DEFAULT 'rdv'");

        // 6. Rename course_id back to formation_id in sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['course_id']);
        });
        
        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('course_id', 'formation_id');
        });
        
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('formation_id')->references('id')->on('courses')->onDelete('set null');
            $table->index('formation_id');
        });
    }
};
