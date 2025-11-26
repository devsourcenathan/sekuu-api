<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);

            // Content type
            $table->enum('content_type', ['video', 'text', 'pdf', 'audio', 'quiz', 'slides'])->default('video');
            $table->longText('content')->nullable(); // For text content

            // Video settings
            $table->string('video_url')->nullable();
            $table->enum('video_provider', ['youtube', 'vimeo', 'local'])->nullable();
            $table->string('video_id')->nullable(); // External video ID
            $table->integer('video_duration_seconds')->nullable();

            // File settings
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable(); // in bytes

            // Access settings
            $table->boolean('is_free')->default(false);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_published')->default(true);

            // Settings
            $table->integer('duration_minutes')->default(0);
            $table->boolean('auto_complete')->default(false); // Complete automatically after viewing
            $table->integer('completion_threshold')->default(80); // % of video to watch

            $table->timestamps();
            $table->softDeletes();

            $table->index(['chapter_id', 'order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
};
