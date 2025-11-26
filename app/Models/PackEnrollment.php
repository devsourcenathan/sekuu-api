<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pack_id',
        'status',
        'enrolled_at',
        'expires_at',
        'completed_at',
        'progress_percentage',
        'completed_courses',
        'total_courses',
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

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function courseEnrollments()
    {
        return $this->hasMany(Enrollment::class, 'pack_enrollment_id');
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

    // Update progress based on course enrollments
    public function updateProgress()
    {
        $totalCourses = $this->pack->courses()->count();
        $completedCourses = $this->courseEnrollments()
            ->where('status', 'completed')
            ->count();

        $this->update([
            'total_courses' => $totalCourses,
            'completed_courses' => $completedCourses,
            'progress_percentage' => $totalCourses > 0
                ? round(($completedCourses / $totalCourses) * 100)
                : 0,
            'last_accessed_at' => now(),
        ]);

        // Check if pack is completed
        $this->checkCompletion();
    }

    // Check if all required courses are completed
    public function checkCompletion()
    {
        $requiredCourses = $this->pack->courses()
            ->wherePivot('is_required', true)
            ->pluck('courses.id');

        $completedRequiredCourses = $this->courseEnrollments()
            ->whereIn('course_id', $requiredCourses)
            ->where('status', 'completed')
            ->count();

        if ($completedRequiredCourses >= $requiredCourses->count() && $requiredCourses->count() > 0) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Issue certificate if pack has certificate enabled
            if ($this->pack->has_certificate && ! $this->certificate_issued) {
                $this->issueCertificate();
            }
        }
    }

    // Issue pack certificate
    public function issueCertificate()
    {
        $this->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
        ]);

        // TODO: Create actual certificate record in certificates table
        // Certificate::create([
        //     'user_id' => $this->user_id,
        //     'certifiable_type' => Pack::class,
        //     'certifiable_id' => $this->pack_id,
        //     'issued_at' => now(),
        // ]);
    }
}
