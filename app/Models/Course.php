<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, HasMedia, SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'description',
        'what_you_will_learn',
        'requirements',
        'target_audience',
        'cover_image',
        'presentation_text',
        'presentation_video_url',
        'presentation_video_type',
        'level',
        'language',
        'status',
        'is_free',
        'price',
        'currency',
        'discount_price',
        'discount_start_date',
        'discount_end_date',
        'is_public',
        'requires_approval',
        'max_students',
        'enrollment_start_date',
        'enrollment_end_date',
        'access_duration_days',
        'allow_download',
        'has_certificate',
        'has_forum',
        'total_duration_minutes',
        'total_lessons',
        'students_enrolled',
        'average_rating',
        'total_reviews',
        'published_at',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'allow_download' => 'boolean',
        'has_certificate' => 'boolean',
        'has_forum' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'discount_start_date' => 'datetime',
        'discount_end_date' => 'datetime',
        'enrollment_start_date' => 'datetime',
        'enrollment_end_date' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    // Relations
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tag')->withTimestamps();
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function resources()
    {
        return $this->morphMany(Resource::class, 'resourceable')->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function packs()
    {
        return $this->belongsToMany(Pack::class, 'pack_courses')
            ->withPivot(['order', 'is_required', 'access_config'])
            ->withTimestamps();
    }


    // Helper methods
    public function isEnrollmentOpen()
    {
        $now = now();

        if ($this->enrollment_start_date && $now->lt($this->enrollment_start_date)) {
            return false;
        }

        if ($this->enrollment_end_date && $now->gt($this->enrollment_end_date)) {
            return false;
        }

        if ($this->max_students && $this->students_enrolled >= $this->max_students) {
            return false;
        }

        return true;
    }

    public function getCurrentPrice()
    {
        if ($this->is_free) {
            return 0;
        }

        $now = now();

        if ($this->discount_price
            && $this->discount_start_date
            && $this->discount_end_date
            && $now->between($this->discount_start_date, $this->discount_end_date)) {
            return $this->discount_price;
        }

        return $this->price;
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function canUserAccess(User $user)
    {
        if (! $this->isPublished() && $this->instructor_id !== $user->id && ! $user->hasRole('admin')) {
            return false;
        }

        if ($this->is_free) {
            return true;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }
}
