<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');

            $table->string('certificate_number')->unique();
            $table->string('verification_code')->unique();
            $table->string('pdf_path')->nullable();

            // Certificate data
            $table->string('student_name');
            $table->string('course_title');
            $table->string('instructor_name');
            $table->date('completion_date');
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('grade')->nullable();

            // Verification
            $table->string('qr_code_path')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index('verification_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificates');
    }
};
