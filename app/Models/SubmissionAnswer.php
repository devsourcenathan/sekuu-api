<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'answer_text',
        'selected_options',
        'file_path',
        'audio_path',
        'video_path',
        'is_correct',
        'points_earned',
        'points_possible',
        'feedback',
        'requires_manual_review',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'is_correct' => 'boolean',
        'requires_manual_review' => 'boolean',
        'points_earned' => 'decimal:2',
    ];

    public function submission()
    {
        return $this->belongsTo(TestSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    // Auto-grade if possible
    public function autoGrade()
    {
        $question = $this->question;

        if ($question->needsManualGrading()) {
            $this->requires_manual_review = true;
            $this->save();

            return false;
        }

        $this->points_possible = $question->points;

        switch ($question->type) {
            case 'single_choice':
            case 'true_false':
                $this->gradeSingleChoice();
                break;

            case 'multiple_choice':
                $this->gradeMultipleChoice();
                break;
        }

        $this->save();

        return true;
    }

    private function gradeSingleChoice()
    {
        $correctOption = $this->question->getCorrectOptions()->first();

        if ($correctOption &&
            $this->selected_options &&
            isset($this->selected_options[0]) &&
            $this->selected_options[0] == $correctOption->id) {
            $this->is_correct = true;
            $this->points_earned = $this->points_possible;
        } else {
            $this->is_correct = false;
            $this->points_earned = 0;
        }
    }

    private function gradeMultipleChoice()
    {
        $correctOptions = $this->question->getCorrectOptions()->pluck('id')->toArray();
        $selectedOptions = $this->selected_options ?? [];

        sort($correctOptions);
        sort($selectedOptions);

        if ($correctOptions === $selectedOptions) {
            $this->is_correct = true;
            $this->points_earned = $this->points_possible;
        } else {
            // Partial credit
            $correctCount = count(array_intersect($correctOptions, $selectedOptions));
            $totalCorrect = count($correctOptions);

            if ($correctCount > 0) {
                $this->points_earned = ($correctCount / $totalCorrect) * $this->points_possible;
            } else {
                $this->points_earned = 0;
            }

            $this->is_correct = false;
        }
    }
}
