<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
        'is_free',
        'is_published',
        'duration_minutes',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
    ];

    // Relations
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function resources()
    {
        return $this->morphMany(Resource::class, 'resourceable')->orderBy('order');
    }

    // Calculate total duration from lessons
    public function updateDuration()
    {
        $this->duration_minutes = $this->lessons()->sum('duration_minutes');
        $this->save();
    }
}
