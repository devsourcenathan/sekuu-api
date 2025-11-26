<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->onDelete('set null')->after('cover_image');
            $table->foreignId('presentation_media_id')->nullable()->constrained('media')->onDelete('set null')->after('presentation_video_url');
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['cover_media_id']);
            $table->dropForeign(['presentation_media_id']);
            $table->dropColumn(['cover_media_id', 'presentation_media_id']);
        });
    }
};
