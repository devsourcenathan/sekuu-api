<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'resourceable_id',
        'resourceable_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_free',
        'is_downloadable',
        'download_limit',
        'downloads_count',
        'order',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_downloadable' => 'boolean',
    ];

    // Polymorphic relation
    public function resourceable()
    {
        return $this->morphTo();
    }

    // Check download limit
    public function canDownload()
    {
        if ($this->download_limit === null) {
            return true;
        }

        return $this->downloads_count < $this->download_limit;
    }

    // Increment download count
    public function incrementDownload()
    {
        $this->increment('downloads_count');
    }

    // Get file size in human readable format
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
