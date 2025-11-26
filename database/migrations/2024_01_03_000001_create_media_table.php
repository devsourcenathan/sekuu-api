<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable'); // Can belong to course, lesson, etc.

            // Basic info
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'video', 'audio', 'document', 'archive'])->default('image');
            $table->string('collection')->default('default'); // cover, lesson_video, resource, etc.

            // File information
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('public'); // public, s3, etc.
            $table->string('mime_type');
            $table->bigInteger('size'); // in bytes

            // Video specific (YouTube, Vimeo)
            $table->enum('provider', ['local', 'youtube', 'vimeo', 's3'])->nullable();
            $table->string('provider_id')->nullable(); // YouTube video ID or Vimeo video ID
            $table->text('provider_data')->nullable(); // JSON data from provider
            $table->integer('duration_seconds')->nullable();

            // Thumbnails
            $table->json('thumbnails')->nullable(); // Array of thumbnail URLs
            $table->string('thumbnail')->nullable(); // Main thumbnail

            // Processing status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('processing_error')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Width, height, codec, etc.
            $table->json('conversions')->nullable(); // Different quality versions

            // Security
            $table->boolean('is_public')->default(false);
            $table->timestamp('url_expires_at')->nullable();

            // Statistics
            $table->integer('views_count')->default(0);
            $table->integer('downloads_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // $table->index(['mediable_type', 'mediable_id']);
            $table->index(['type', 'collection']);
            $table->index('provider');
        });
    }

    public function down()
    {
        Schema::dropIfExists('media');
    }
};
