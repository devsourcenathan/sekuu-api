<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'pack_enrollment_id',
        'status',
        'enrolled_at',
        'expires_at',
        'completed_at',
        'progress_percentage',
        'completed_lessons',
        'total_lessons',
        'last_accessed_at',
        'certificate_issued',
        'certificate_issued_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'certificate_issued' => 'boolean',
        'certificate_issued_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function packEnrollment()
    {
        return $this->belongsTo(PackEnrollment::class);
    }


    // Check if enrollment is active
    public function isActive()
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            $this->update(['status' => 'expired']);

            return false;
        }

        return true;
    }

    // Update progress
    public function updateProgress()
    {
        $totalLessons = $this->course->chapters()
            ->withCount('lessons')
            ->get()
            ->sum('lessons_count');

        $completedLessons = $this->lessonProgress()
            ->where('is_completed', true)
            ->count();

        $this->update([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percentage' => $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0,
            'last_accessed_at' => now(),
        ]);

        // Check if course is completed
        if ($completedLessons >= $totalLessons && $totalLessons > 0) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
