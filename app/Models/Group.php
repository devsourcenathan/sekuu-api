<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'instructor_id',
        'course_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function sessions()
    {
        return $this->hasManyThrough(
            Session::class,
            SessionParticipant::class,
            'user_id', // Foreign key on session_participants
            'id', // Foreign key on sessions
            'id', // Local key on groups
            'session_id' // Local key on session_participants
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Methods
    public function addMember(User $user): void
    {
        if (! $this->isMember($user)) {
            $this->members()->attach($user->id, ['added_at' => now()]);
        }
    }

    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function getMembersCount(): int
    {
        return $this->members()->count();
    }

    public function getMemberIds(): array
    {
        return $this->members()->pluck('user_id')->toArray();
    }
}
