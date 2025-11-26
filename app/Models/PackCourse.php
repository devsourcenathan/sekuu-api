<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PackCourse extends Pivot
{
    protected $table = 'pack_courses';

    protected $fillable = [
        'pack_id',
        'course_id',
        'order',
        'is_required',
        'access_config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'access_config' => 'array',
    ];

    // Relations
    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Access configuration helpers
    public function getIncludedChapters()
    {
        return $this->access_config['include_chapters'] ?? null;
    }

    public function getIncludedLessons()
    {
        return $this->access_config['include_lessons'] ?? null;
    }

    public function canAccessChapter($chapterId)
    {
        $includedChapters = $this->getIncludedChapters();

        // If null, all chapters are included
        if ($includedChapters === null) {
            return true;
        }

        return in_array($chapterId, $includedChapters);
    }

    public function canAccessLesson($lessonId)
    {
        $includedLessons = $this->getIncludedLessons();

        // If null, all lessons are included
        if ($includedLessons === null) {
            return true;
        }

        return in_array($lessonId, $includedLessons);
    }

    public function canAccessTests()
    {
        return $this->access_config['include_tests'] ?? true;
    }

    public function canAccessResources()
    {
        return $this->access_config['include_resources'] ?? true;
    }

    public function canDownload()
    {
        return $this->access_config['allow_download'] ?? false;
    }

    public function canAccessCertificate()
    {
        return $this->access_config['include_certificate'] ?? true;
    }
}
