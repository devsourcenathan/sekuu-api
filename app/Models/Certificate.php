<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_id',
        'certificate_number',
        'verification_code',
        'pdf_path',
        'student_name',
        'course_title',
        'instructor_name',
        'completion_date',
        'final_score',
        'grade',
        'qr_code_path',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'final_score' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = 'CERT-'.date('Y').'-'.Str::upper(Str::random(8));
            }
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = Str::upper(Str::random(16));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
