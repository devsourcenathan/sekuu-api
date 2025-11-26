<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'testable_id',
        'testable_type',
        'title',
        'description',
        'instructions',
        'type',
        'position',
        'duration_minutes',
        'max_attempts',
        'passing_score',
        'show_results_immediately',
        'show_correct_answers',
        'randomize_questions',
        'randomize_options',
        'one_question_per_page',
        'allow_back_navigation',
        'auto_save_draft',
        'validation_type',
        'is_published',
        'disable_copy_paste',
        'full_screen_required',
        'webcam_monitoring',
        'total_questions',
        'total_points',
        'attempts_count',
        'average_score',
    ];

    protected $casts = [
        'show_results_immediately' => 'boolean',
        'show_correct_answers' => 'boolean',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'one_question_per_page' => 'boolean',
        'allow_back_navigation' => 'boolean',
        'auto_save_draft' => 'boolean',
        'is_published' => 'boolean',
        'disable_copy_paste' => 'boolean',
        'full_screen_required' => 'boolean',
        'webcam_monitoring' => 'boolean',
        'average_score' => 'decimal:2',
    ];

    // Polymorphic relation
    public function testable()
    {
        return $this->morphTo();
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(TestSubmission::class);
    }

    // Get course from testable
    public function getCourse()
    {
        return match (get_class($this->testable)) {
            'App\Models\Course' => $this->testable,
            'App\Models\Chapter' => $this->testable->course,
            'App\Models\Lesson' => $this->testable->chapter->course,
        };
    }

    // Check if user can take test
    public function canUserTake(User $user)
    {
        if (! $this->is_published) {
            return false;
        }

        $course = $this->getCourse();

        if (! $course->canUserAccess($user)) {
            return false;
        }

        // Check max attempts
        if ($this->max_attempts > 0) {
            $attemptCount = $this->submissions()
                ->where('user_id', $user->id)
                ->whereIn('status', ['submitted', 'graded'])
                ->count();

            if ($attemptCount >= $this->max_attempts) {
                return false;
            }
        }

        return true;
    }

    // Get user's attempts
    public function getUserAttempts(User $user)
    {
        return $this->submissions()
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();
    }

    // Update statistics
    public function updateStatistics()
    {
        $this->total_questions = $this->questions()->count();
        $this->total_points = $this->questions()->sum('points');

        $submissions = $this->submissions()
            ->whereIn('status', ['submitted', 'graded'])
            ->get();

        $this->attempts_count = $submissions->count();
        $this->average_score = $submissions->avg('score') ?? 0;

        $this->save();
    }
}
