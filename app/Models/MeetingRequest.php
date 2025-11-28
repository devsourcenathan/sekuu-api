<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'instructor_id',
        'course_id',
        'message',
        'status',
        'datetime_proposed',
        'datetime_final',
        'session_id',
        'rejection_reason',
    ];

    protected $casts = [
        'datetime_proposed' => 'datetime',
        'datetime_final' => 'datetime',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    // Methods
    public function accept(Session $session): void
    {
        $this->update([
            'status' => 'accepted',
            'session_id' => $session->id,
            'datetime_final' => $session->datetime_start,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function canBeAccepted(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }
}
