<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, HasMedia, SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'title',
        'description',
        'order',
        'content_type',
        'content',
        'video_url',
        'video_provider',
        'video_id',
        'video_duration_seconds',
        'file_path',
        'file_type',
        'file_size',
        'is_free',
        'is_preview',
        'is_downloadable',
        'is_published',
        'duration_minutes',
        'auto_complete',
        'completion_threshold',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_preview' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_published' => 'boolean',
        'auto_complete' => 'boolean',
    ];

    // Relations
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function resources()
    {
        return $this->morphMany(Resource::class, 'resourceable')->orderBy('order');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Check if user has access
    public function canUserAccess(User $user)
    {
        if ($this->is_free || $this->is_preview) {
            return true;
        }

        $course = $this->chapter->course;

        return $course->canUserAccess($user);
    }
}
