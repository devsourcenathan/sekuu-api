<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'duration_minutes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    // Relations
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public function markAsJoined(): void
    {
        $this->update(['joined_at' => now()]);
    }

    public function markAsLeft(): void
    {
        $this->update([
            'left_at' => now(),
            'duration_minutes' => $this->calculateDuration(),
        ]);
    }

    public function calculateDuration(): ?int
    {
        if (! $this->joined_at) {
            return null;
        }

        $leftAt = $this->left_at ?? now();

        return (int) $this->joined_at->diffInMinutes($leftAt);
    }
}
