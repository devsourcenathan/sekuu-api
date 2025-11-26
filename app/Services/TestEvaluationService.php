<?php

namespace App\Services;

use App\Models\SubmissionAnswer;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestEvaluationService
{
    public function startTest(Test $test, User $user)
    {
        if (! $test->canUserTake($user)) {
            throw new \Exception('You cannot take this test');
        }

        $attemptNumber = $test->getUserAttempts($user) + 1;

        $enrollment = $user->enrollments()
            ->where('course_id', $test->getCourse()->id)
            ->where('status', 'active')
            ->first();

        $submission = TestSubmission::create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'enrollment_id' => $enrollment?->id,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => $test->total_points,
        ]);

        // Set expiration if test has duration
        if ($test->duration_minutes) {
            $submission->update([
                'expires_at' => now()->addMinutes($test->duration_minutes),
            ]);
        }

        return $submission->load('test.questions.options');
    }

    public function saveDraft(TestSubmission $submission, array $answers)
    {
        if ($submission->status !== 'in_progress') {
            throw new \Exception('Cannot save draft for submitted test');
        }

        if ($submission->hasExpired()) {
            throw new \Exception('Test has expired');
        }

        $submission->update([
            'draft_answers' => $answers,
        ]);

        return $submission;
    }

    public function submitTest(TestSubmission $submission, array $answers)
    {
        if ($submission->status !== 'in_progress') {
            throw new \Exception('Test already submitted');
        }

        if ($submission->hasExpired()) {
            throw new \Exception('Test has expired');
        }

        return DB::transaction(function () use ($submission, $answers) {
            // Calculate time spent
            $timeSpent = now()->diffInSeconds($submission->started_at);

            $submission->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'time_spent_seconds' => $timeSpent,
            ]);

            // Save and grade answers
            $requiresManualGrading = false;

            foreach ($answers as $answerData) {
                $answer = SubmissionAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $answerData['question_id'],
                    'answer_text' => $answerData['answer_text'] ?? null,
                    'selected_options' => $answerData['selected_options'] ?? null,
                    'file_path' => $answerData['file_path'] ?? null,
                ]);

                // Auto-grade if possible
                if (! $answer->autoGrade()) {
                    $requiresManualGrading = true;
                }
            }

            // Calculate score if all questions are auto-graded
            if (! $requiresManualGrading) {
                $submission->calculateScore();
                $submission->update(['status' => 'graded']);
            }

            // Update test statistics
            $submission->test->updateStatistics();

            return $submission->fresh(['answers.question', 'test']);
        });
    }

    public function gradeManually(TestSubmission $submission, User $grader, array $gradings)
    {
        return DB::transaction(function () use ($submission, $grader, $gradings) {
            foreach ($gradings as $grading) {
                $answer = SubmissionAnswer::where('submission_id', $submission->id)
                    ->where('question_id', $grading['question_id'])
                    ->firstOrFail();

                $answer->update([
                    'points_earned' => $grading['points_earned'],
                    'feedback' => $grading['feedback'] ?? null,
                    'requires_manual_review' => false,
                ]);
            }

            // Calculate final score
            $submission->calculateScore();

            $submission->update([
                'status' => 'graded',
                'graded_by' => $grader->id,
                'graded_at' => now(),
                'instructor_comments' => $gradings['comments'] ?? null,
            ]);

            return $submission->fresh(['answers.question', 'test']);
        });
    }

    public function getSubmissionForReview(TestSubmission $submission)
    {
        return $submission->load([
            'test',
            'user',
            'answers.question.options',
        ]);
    }

    public function getPendingSubmissions(User $instructor)
    {
        return TestSubmission::whereHas('test', function ($query) use ($instructor) {
            $query->whereHasMorph('testable', ['App\Models\Course', 'App\Models\Chapter', 'App\Models\Lesson'], function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            });
        })
            ->where('status', 'submitted')
            ->whereHas('answers', function ($query) {
                $query->where('requires_manual_review', true);
            })
            ->with(['test', 'user'])
            ->orderBy('submitted_at', 'asc')
            ->paginate(20);
    }
}
