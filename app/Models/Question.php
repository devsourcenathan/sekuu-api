<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'test_id',
        'question_text',
        'explanation',
        'type',
        'image_url',
        'audio_url',
        'video_url',
        'points',
        'order',
        'is_required',
        'requires_manual_grading',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'requires_manual_grading' => 'boolean',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    // Check if question needs manual grading
    public function needsManualGrading()
    {
        return in_array($this->type, ['short_answer', 'long_answer', 'audio', 'video', 'file_upload'])
            || $this->requires_manual_grading;
    }

    // Get correct options
    public function getCorrectOptions()
    {
        return $this->options()->where('is_correct', true)->get();
    }
}
