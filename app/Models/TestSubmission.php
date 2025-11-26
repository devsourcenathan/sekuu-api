<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'user_id',
        'enrollment_id',
        'attempt_number',
        'status',
        'score',
        'points_earned',
        'total_points',
        'passed',
        'grade',
        'instructor_comments',
        'graded_by',
        'graded_at',
        'started_at',
        'submitted_at',
        'expires_at',
        'time_spent_seconds',
        'draft_answers',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'graded_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'draft_answers' => 'array',
        'score' => 'decimal:2',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class, 'submission_id');
    }

    // Check if submission has expired
    public function hasExpired()
    {
        if ($this->expires_at && now()->gt($this->expires_at)) {
            if ($this->status === 'in_progress') {
                $this->update(['status' => 'expired']);
            }

            return true;
        }

        return false;
    }

    // Calculate final score
    public function calculateScore()
    {
        $totalPoints = $this->total_points;

        if ($totalPoints == 0) {
            return 0;
        }

        $earnedPoints = $this->answers()->sum('points_earned');
        $this->points_earned = $earnedPoints;

        $score = ($earnedPoints / $totalPoints) * 100;
        $this->score = round($score, 2);

        // Determine pass/fail
        $this->passed = $score >= $this->test->passing_score;

        // Assign grade
        $this->grade = $this->assignGrade($score);

        $this->save();

        return $score;
    }

    private function assignGrade($score)
    {
        if ($score >= 90) {
            return 'excellent';
        }
        if ($score >= 80) {
            return 'very_good';
        }
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= $this->test->passing_score) {
            return 'pass';
        }

        return 'fail';
    }
}
