<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('pack_enrollment_id')
                ->nullable()
                ->after('course_id')
                ->constrained('pack_enrollments')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['pack_enrollment_id']);
            $table->dropColumn('pack_enrollment_id');
        });
    }
};
