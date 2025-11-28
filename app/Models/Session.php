<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'instructor_id',
        'course_id',
        'datetime_start',
        'datetime_end',
        'livekit_room_name',
        'type',
        'status',
        'recording_enabled',
        'max_participants',
        'recording_url',
        'cancellation_reason',
    ];

    protected $casts = [
        'datetime_start' => 'datetime',
        'datetime_end' => 'datetime',
        'recording_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->livekit_room_name)) {
                $session->livekit_room_name = $session->generateRoomName();
            }
        });
    }

    // Relations
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'session_participants')
            ->withPivot(['role', 'joined_at', 'left_at', 'duration_minutes'])
            ->withTimestamps();
    }

    public function sessionParticipants()
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function meetingRequest()
    {
        return $this->hasOne(MeetingRequest::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('datetime_start', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('datetime_start');
    }

    public function scopePast($query)
    {
        return $query->where('datetime_end', '<', now())
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('datetime_start', 'desc');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress')
            ->where('datetime_start', '<=', now())
            ->where('datetime_end', '>=', now());
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
    public function generateRoomName(): string
    {
        return 'session_'.Str::random(16).'_'.time();
    }

    public function isActive(): bool
    {
        return $this->status === 'in_progress'
            && now()->between($this->datetime_start, $this->datetime_end);
    }

    public function canUserJoin(User $user): bool
    {
        // L'instructeur peut toujours rejoindre
        if ($this->instructor_id === $user->id) {
            return true;
        }

        // Vérifier si l'utilisateur est participant
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function addParticipant(User $user, string $role = 'participant'): void
    {
        if (! $this->participants()->where('user_id', $user->id)->exists()) {
            $this->participants()->attach($user->id, ['role' => $role]);
        }
    }

    public function removeParticipant(User $user): void
    {
        $this->participants()->detach($user->id);
    }

    public function getParticipantCount(): int
    {
        return $this->participants()->count();
    }

    public function canAddParticipant(): bool
    {
        if (! $this->max_participants) {
            return true;
        }

        return $this->getParticipantCount() < $this->max_participants;
    }

    public function start(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function end(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }
}
