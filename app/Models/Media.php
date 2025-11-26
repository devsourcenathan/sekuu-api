<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'mediable_id',
        'mediable_type',
        'title',
        'description',
        'type',
        'collection',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'size',
        'provider',
        'provider_id',
        'provider_data',
        'duration_seconds',
        'thumbnails',
        'thumbnail',
        'status',
        'processing_error',
        'metadata',
        'conversions',
        'is_public',
        'url_expires_at',
        'views_count',
        'downloads_count',
    ];

    protected $casts = [
        'size' => 'integer',
        'duration_seconds' => 'integer',
        'thumbnails' => 'array',
        'metadata' => 'array',
        'conversions' => 'array',
        'provider_data' => 'array',
        'is_public' => 'boolean',
        'url_expires_at' => 'datetime',
        'views_count' => 'integer',
        'downloads_count' => 'integer',
    ];

    // Polymorphic relation
    public function mediable()
    {
        return $this->morphTo();
    }

    // Get URL for media
    public function getUrl($conversion = null)
    {
        // External providers
        if ($this->provider === 'youtube') {
            return "https://www.youtube.com/watch?v={$this->provider_id}";
        }

        if ($this->provider === 'vimeo') {
            return "https://vimeo.com/{$this->provider_id}";
        }

        // Local/S3 storage
        $path = $conversion && isset($this->conversions[$conversion])
            ? $this->conversions[$conversion]['path']
            : $this->file_path;

        // Public files
        if ($this->is_public) {
            return Storage::disk($this->disk)->url($path);
        }

        // Generate signed URL for private files
        return $this->getTemporaryUrl();
    }

    // Get embed URL for videos
    public function getEmbedUrl()
    {
        if ($this->provider === 'youtube') {
            return "https://www.youtube.com/embed/{$this->provider_id}";
        }

        if ($this->provider === 'vimeo') {
            return "https://player.vimeo.com/video/{$this->provider_id}";
        }

        return $this->getUrl();
    }

    // Generate temporary signed URL
    public function getTemporaryUrl($expirationMinutes = 60)
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expirationMinutes)
            );
        }

        // For local files, use Laravel signed URLs
        return URL::temporarySignedRoute(
            'media.serve',
            now()->addMinutes($expirationMinutes),
            ['media' => $this->id]
        );
    }

    // Get human readable file size
    public function getHumanSizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size;

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    // Get thumbnail URL
    public function getThumbnailUrl($size = 'medium')
    {
        if ($this->thumbnails && isset($this->thumbnails[$size])) {
            return Storage::disk($this->disk)->url($this->thumbnails[$size]);
        }

        if ($this->thumbnail) {
            return Storage::disk($this->disk)->url($this->thumbnail);
        }

        return null;
    }

    // Increment views
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    // Increment downloads
    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }

    // Check if processing is complete
    public function isProcessed()
    {
        return $this->status === 'completed';
    }

    // Delete media files from storage
    public function deleteFiles()
    {
        // Delete main file
        if (Storage::disk($this->disk)->exists($this->file_path)) {
            Storage::disk($this->disk)->delete($this->file_path);
        }

        // Delete thumbnails
        if ($this->thumbnails) {
            foreach ($this->thumbnails as $thumbnail) {
                if (Storage::disk($this->disk)->exists($thumbnail)) {
                    Storage::disk($this->disk)->delete($thumbnail);
                }
            }
        }

        // Delete conversions
        if ($this->conversions) {
            foreach ($this->conversions as $conversion) {
                if (isset($conversion['path']) && Storage::disk($this->disk)->exists($conversion['path'])) {
                    Storage::disk($this->disk)->delete($conversion['path']);
                }
            }
        }
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            $media->deleteFiles();
        });
    }
}
