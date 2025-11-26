<?php

namespace App\Traits;

use App\Models\Media;

trait HasMedia
{
    /**
     * Get all media for the model
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get media by collection
     */
    public function getMedia($collection = 'default')
    {
        return $this->media()->where('collection', $collection)->get();
    }

    /**
     * Get first media from collection
     */
    public function getFirstMedia($collection = 'default')
    {
        return $this->media()->where('collection', $collection)->first();
    }

    /**
     * Add media to collection
     */
    public function addMedia($file, $collection = 'default', array $options = [])
    {
        $mediaService = app(\App\Services\MediaService::class);

        return $mediaService->uploadMedia($file, $this, $collection, $options);
    }

    /**
     * Clear media collection
     */
    public function clearMediaCollection($collection = 'default')
    {
        $this->media()->where('collection', $collection)->each(function ($media) {
            $media->delete();
        });
    }

    /**
     * Check if model has media in collection
     */
    public function hasMedia($collection = 'default')
    {
        return $this->media()->where('collection', $collection)->exists();
    }
}
