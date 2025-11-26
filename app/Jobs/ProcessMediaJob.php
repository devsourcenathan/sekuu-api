<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $media;

    public $timeout = 1800; // 30 minutes

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function handle(MediaService $mediaService)
    {
        try {
            $this->media->update(['status' => 'processing']);

            // Process media based on type
            switch ($this->media->type) {
                case 'video':
                    $this->processVideo($mediaService);
                    break;
                case 'image':
                    $this->processImage($mediaService);
                    break;
            }

            $this->media->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->media->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function processVideo(MediaService $mediaService)
    {
        // Extract video metadata, generate thumbnails, etc.
        // This is handled by MediaService
    }

    protected function processImage(MediaService $mediaService)
    {
        // Generate image thumbnails and conversions
        // This is handled by MediaService
    }
}
