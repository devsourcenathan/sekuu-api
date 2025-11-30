<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageTracking extends Model
{
    use HasFactory;

    protected $table = 'usage_tracking';

    protected $fillable = [
        'user_id',
        'resource_type',
        'current_count',
        'last_reset_at',
    ];

    protected $casts = [
        'current_count' => 'integer',
        'last_reset_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Usage methods
    // Usage methods
    public function incrementUsage(int $amount = 1): void
    {
        // Use the Eloquent increment method on the column
        parent::increment('current_count', $amount);
    }

    public function decrementUsage(int $amount = 1): void
    {
        $this->current_count = max(0, $this->current_count - $amount);
        $this->save();
    }

    public function reset(): void
    {
        $this->update([
            'current_count' => 0,
            'last_reset_at' => now(),
        ]);
    }

    public function setCount(int $count): void
    {
        $this->update([
            'current_count' => max(0, $count),
        ]);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForResource($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }
}
