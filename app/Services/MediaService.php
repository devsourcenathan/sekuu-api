<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaService
{
    protected $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    protected $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

    protected $allowedDocumentTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    /**
     * Upload and process media file
     */
    public function uploadMedia($file, $mediable, $collection = 'default', array $options = [])
    {
        $mimeType = $file->getMimeType();
        $type = $this->determineMediaType($mimeType);

        // Generate unique filename
        $filename = $this->generateFilename($file);

        // Determine storage disk
        $disk = $options['disk'] ?? config('media.default_disk', 'public');

        // Store file
        $path = $file->storeAs(
            $this->getStoragePath($mediable, $collection),
            $filename,
            $disk
        );

        // Create media record
        $media = Media::create([
            'mediable_id' => $mediable->id,
            'mediable_type' => get_class($mediable),
            'title' => $options['title'] ?? $file->getClientOriginalName(),
            'description' => $options['description'] ?? null,
            'type' => $type,
            'collection' => $collection,
            'file_name' => $filename,
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
            'provider' => 'local',
            'is_public' => $options['is_public'] ?? false,
            'status' => 'pending',
        ]);

        // Process media asynchronously
        $this->processMedia($media, $file);

        return $media;
    }

    /**
     * Upload video to Vimeo
     */
    public function uploadToVimeo($file, $mediable, array $options = [])
    {
        $accessToken = config('services.vimeo.token');

        if (! $accessToken) {
            throw new \Exception('Vimeo access token not configured');
        }

        // Step 1: Create video entry
        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.vimeo.*+json;version=3.4'])
            ->post('https://api.vimeo.com/me/videos', [
                'upload' => [
                    'approach' => 'tus',
                    'size' => $file->getSize(),
                ],
                'name' => $options['title'] ?? $file->getClientOriginalName(),
                'description' => $options['description'] ?? '',
                'privacy' => [
                    'view' => $options['privacy'] ?? 'disable',
                    'embed' => 'whitelist',
                    'download' => false,
                ],
                'embed' => [
                    'buttons' => [
                        'like' => false,
                        'watchlater' => false,
                        'share' => false,
                    ],
                    'logos' => [
                        'vimeo' => false,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to create Vimeo video: '.$response->body());
        }

        $data = $response->json();
        $videoId = str_replace('/videos/', '', $data['uri']);
        $uploadLink = $data['upload']['upload_link'];

        // Step 2: Upload video file via TUS
        $this->uploadFileToVimeo($file, $uploadLink);

        // Step 3: Create media record
        $media = Media::create([
            'mediable_id' => $mediable->id,
            'mediable_type' => get_class($mediable),
            'title' => $options['title'] ?? $file->getClientOriginalName(),
            'description' => $options['description'] ?? null,
            'type' => 'video',
            'collection' => $options['collection'] ?? 'default',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $data['uri'],
            'disk' => 'vimeo',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'provider' => 'vimeo',
            'provider_id' => $videoId,
            'provider_data' => $data,
            'status' => 'processing',
        ]);

        // Poll Vimeo for video processing status
        dispatch(function () use ($media, $accessToken) {
            $this->pollVimeoProcessing($media, $accessToken);
        })->afterResponse();

        return $media;
    }

    /**
     * Link YouTube video
     */
    public function linkYoutubeVideo($videoId, $mediable, array $options = [])
    {
        $apiKey = config('services.youtube.key');

        if (! $apiKey) {
            throw new \Exception('YouTube API key not configured');
        }

        // Get video information
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,contentDetails,status',
            'id' => $videoId,
            'key' => $apiKey,
        ]);

        if (! $response->successful() || empty($response->json()['items'])) {
            throw new \Exception('YouTube video not found');
        }

        $videoData = $response->json()['items'][0];

        // Check if video is embeddable
        if (! $videoData['status']['embeddable']) {
            throw new \Exception('This YouTube video cannot be embedded');
        }

        $duration = $this->parseYoutubeDuration($videoData['contentDetails']['duration']);
        $thumbnails = $videoData['snippet']['thumbnails'];

        // Create media record
        $media = Media::create([
            'mediable_id' => $mediable->id,
            'mediable_type' => get_class($mediable),
            'title' => $options['title'] ?? $videoData['snippet']['title'],
            'description' => $options['description'] ?? $videoData['snippet']['description'],
            'type' => 'video',
            'collection' => $options['collection'] ?? 'default',
            'file_name' => $videoId,
            'file_path' => "https://www.youtube.com/watch?v={$videoId}",
            'disk' => 'youtube',
            'mime_type' => 'video/youtube',
            'size' => 0,
            'provider' => 'youtube',
            'provider_id' => $videoId,
            'provider_data' => $videoData,
            'duration_seconds' => $duration,
            'thumbnails' => [
                'default' => $thumbnails['default']['url'],
                'medium' => $thumbnails['medium']['url'],
                'high' => $thumbnails['high']['url'],
                'standard' => $thumbnails['standard']['url'] ?? null,
                'maxres' => $thumbnails['maxres']['url'] ?? null,
            ],
            'thumbnail' => $thumbnails['high']['url'],
            'status' => 'completed',
            'is_public' => true,
        ]);

        return $media;
    }

    /**
     * Process uploaded media (generate thumbnails, conversions, etc.)
     */
    protected function processMedia(Media $media, $file = null)
    {
        try {
            $media->update(['status' => 'processing']);

            switch ($media->type) {
                case 'image':
                    $this->processImage($media, $file);
                    break;
                case 'video':
                    $this->processVideo($media);
                    break;
            }

            $media->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $media->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process image (generate thumbnails and conversions)
     */
    protected function processImage(Media $media, $file)
    {
        $disk = Storage::disk($media->disk);
        $thumbnails = [];
        $conversions = [];

        $sizes = [
            'thumb' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];

        foreach ($sizes as $name => $size) {
            $img = Image::make($file);

            // Get original dimensions
            $width = $img->width();
            $height = $img->height();

            // Resize maintaining aspect ratio
            $img->resize($size['width'], $size['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Save thumbnail
            $thumbnailPath = str_replace(
                basename($media->file_path),
                "{$name}_".basename($media->file_path),
                $media->file_path
            );

            $disk->put($thumbnailPath, $img->encode());
            $thumbnails[$name] = $thumbnailPath;

            // Store conversion info
            $conversions[$name] = [
                'path' => $thumbnailPath,
                'width' => $img->width(),
                'height' => $img->height(),
                'size' => $disk->size($thumbnailPath),
            ];
        }

        // Update media with thumbnails and conversions
        $media->update([
            'thumbnails' => $thumbnails,
            'thumbnail' => $thumbnails['medium'] ?? $thumbnails['small'],
            'conversions' => $conversions,
            'metadata' => [
                'width' => $width,
                'height' => $height,
                'exif' => $img->exif() ?? [],
            ],
        ]);
    }

    /**
     * Process video (extract metadata, generate thumbnail)
     */
    protected function processVideo(Media $media)
    {
        // This requires FFmpeg to be installed
        // For production, consider using a queue job

        $videoPath = Storage::disk($media->disk)->path($media->file_path);

        // Extract video duration using FFmpeg
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$videoPath}";
        $duration = (int) shell_exec($command);

        // Extract thumbnail at 2 seconds
        $thumbnailFilename = pathinfo($media->file_name, PATHINFO_FILENAME).'_thumb.jpg';
        $thumbnailPath = dirname($media->file_path).'/'.$thumbnailFilename;
        $thumbnailFullPath = Storage::disk($media->disk)->path($thumbnailPath);

        $command = "ffmpeg -i {$videoPath} -ss 00:00:02.000 -vframes 1 {$thumbnailFullPath}";
        shell_exec($command);

        $media->update([
            'duration_seconds' => $duration,
            'thumbnail' => file_exists($thumbnailFullPath) ? $thumbnailPath : null,
        ]);
    }

    /**
     * Helper methods
     */
    protected function determineMediaType($mimeType)
    {
        if (in_array($mimeType, $this->allowedImageTypes)) {
            return 'image';
        }
        if (in_array($mimeType, $this->allowedVideoTypes)) {
            return 'video';
        }
        if (Str::startsWith($mimeType, 'audio/')) {
            return 'audio';
        }
        if (in_array($mimeType, $this->allowedDocumentTypes)) {
            return 'document';
        }
        if (in_array($mimeType, ['application/zip', 'application/x-rar-compressed'])) {
            return 'archive';
        }

        return 'document';
    }

    protected function generateFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $hash = Str::random(40);

        return date('Y/m/d').'/'.$hash.'.'.$extension;
    }

    protected function getStoragePath($mediable, $collection)
    {
        $modelName = Str::plural(Str::kebab(class_basename($mediable)));

        return "{$modelName}/{$mediable->id}/{$collection}";
    }

    protected function parseYoutubeDuration($duration)
    {
        preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches);

        $hours = isset($matches[1]) ? (int) rtrim($matches[1], 'H') : 0;
        $minutes = isset($matches[2]) ? (int) rtrim($matches[2], 'M') : 0;
        $seconds = isset($matches[3]) ? (int) rtrim($matches[3], 'S') : 0;

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    protected function uploadFileToVimeo($file, $uploadLink)
    {
        // Simplified TUS upload - in production use a proper TUS client
        $handle = fopen($file->getRealPath(), 'rb');
        $fileSize = $file->getSize();

        Http::withHeaders([
            'Tus-Resumable' => '1.0.0',
            'Upload-Offset' => '0',
            'Content-Type' => 'application/offset+octet-stream',
        ])->attach('file', $handle, $file->getClientOriginalName())
            ->patch($uploadLink);

        fclose($handle);
    }

    protected function pollVimeoProcessing(Media $media, $accessToken)
    {
        $maxAttempts = 60; // 10 minutes (60 * 10 seconds)
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(10); // Wait 10 seconds between checks

            $response = Http::withToken($accessToken)
                ->get("https://api.vimeo.com/videos/{$media->provider_id}");

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'available') {
                    // Video is ready
                    $media->update([
                        'status' => 'completed',
                        'duration_seconds' => $data['duration'],
                        'thumbnails' => [
                            'small' => $data['pictures']['sizes'][0]['link'],
                            'medium' => $data['pictures']['sizes'][2]['link'],
                            'large' => $data['pictures']['sizes'][4]['link'],
                        ],
                        'thumbnail' => $data['pictures']['sizes'][2]['link'],
                        'provider_data' => $data,
                    ]);
                    break;
                }
            }

            $attempt++;
        }

        if ($attempt >= $maxAttempts) {
            $media->update([
                'status' => 'failed',
                'processing_error' => 'Vimeo processing timeout',
            ]);
        }
    }

    /**
     * Delete media
     */
    public function deleteMedia(Media $media)
    {
        // For external providers, optionally delete from provider
        if ($media->provider === 'vimeo') {
            $this->deleteFromVimeo($media);
        }

        $media->delete();
    }

    protected function deleteFromVimeo(Media $media)
    {
        $accessToken = config('services.vimeo.token');

        if ($accessToken) {
            Http::withToken($accessToken)
                ->delete("https://api.vimeo.com/videos/{$media->provider_id}");
        }
    }
}
