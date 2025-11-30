<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'resource_type',
        'limit_value',
    ];

    protected $casts = [
        'limit_value' => 'integer',
    ];

    // Relationships
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    // Helper methods
    public function isUnlimited(): bool
    {
        return $this->limit_value === -1;
    }

    public function hasReachedLimit(int $currentUsage): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }

        return $currentUsage >= $this->limit_value;
    }

    public function getRemainingQuota(int $currentUsage): int
    {
        if ($this->isUnlimited()) {
            return -1; // Unlimited
        }

        return max(0, $this->limit_value - $currentUsage);
    }
}
