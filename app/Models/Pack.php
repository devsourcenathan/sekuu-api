<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Pack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'price',
        'currency',
        'discount_percentage',
        'is_active',
        'is_public',
        'max_enrollments',
        'access_duration_days',
        'enrollment_start_date',
        'enrollment_end_date',
        'has_certificate',
        'require_sequential_completion',
        'recommended_order',
        'total_courses',
        'total_duration_minutes',
        'students_enrolled',
        'average_rating',
        'total_reviews',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'has_certificate' => 'boolean',
        'require_sequential_completion' => 'boolean',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'recommended_order' => 'array',
        'enrollment_start_date' => 'datetime',
        'enrollment_end_date' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pack) {
            if (empty($pack->slug)) {
                $pack->slug = Str::slug($pack->title);
            }
        });
    }

    // Relations
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'pack_courses')
            ->withPivot(['order', 'is_required', 'access_config'])
            ->withTimestamps()
            ->orderBy('pack_courses.order');
    }

    public function packEnrollments()
    {
        return $this->hasMany(PackEnrollment::class);
    }

    // Helper methods
    public function isEnrollmentOpen()
    {
        $now = now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->enrollment_start_date && $now->lt($this->enrollment_start_date)) {
            return false;
        }

        if ($this->enrollment_end_date && $now->gt($this->enrollment_end_date)) {
            return false;
        }

        if ($this->max_enrollments && $this->students_enrolled >= $this->max_enrollments) {
            return false;
        }

        return true;
    }

    public function getCurrentPrice()
    {
        return $this->price;
    }

    public function calculateDiscount()
    {
        // Calculate discount percentage based on individual course prices
        $totalIndividualPrice = $this->courses->sum('price');

        if ($totalIndividualPrice > 0) {
            $discount = (($totalIndividualPrice - $this->price) / $totalIndividualPrice) * 100;

            return max(0, round($discount, 2));
        }

        return 0;
    }

    public function isPublished()
    {
        return $this->is_active && $this->published_at !== null;
    }

    public function canUserAccess(User $user)
    {
        if (! $this->isPublished() && $this->instructor_id !== $user->id && ! $user->hasRole('admin')) {
            return false;
        }

        return $this->packEnrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    public function updateStatistics()
    {
        $this->total_courses = $this->courses()->count();
        $this->total_duration_minutes = $this->courses()->sum('total_duration_minutes');

        // Update discount percentage
        $this->discount_percentage = $this->calculateDiscount();

        $this->save();
    }
}
