<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pack_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);

            // Granular access configuration stored as JSON
            // Structure: {
            //   "include_chapters": [1, 2, 3] or null (all),
            //   "include_lessons": [1, 2, 3] or null (all),
            //   "include_tests": true/false,
            //   "include_resources": true/false,
            //   "allow_download": true/false,
            //   "include_certificate": true/false
            // }
            $table->json('access_config')->nullable();

            $table->timestamps();

            $table->unique(['pack_id', 'course_id']);
            $table->index('order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pack_courses');
    }
};
