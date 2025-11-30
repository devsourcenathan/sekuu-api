<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'priority',
        'is_active',
        'features',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function limits()
    {
        return $this->hasMany(SubscriptionPlanLimit::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserSubscription::class,
            'subscription_plan_id',
            'id',
            'id',
            'user_id'
        );
    }

    // Helper methods
    public function hasFeature(string $permissionSlug): bool
    {
        $features = $this->features ?? [];
        return in_array($permissionSlug, $features);
    }

    public function getLimit(string $resourceType): int
    {
        $limit = $this->limits()->where('resource_type', $resourceType)->first();
        return $limit ? $limit->limit_value : 0;
    }

    public function isHigherThan(SubscriptionPlan $plan): bool
    {
        return $this->priority > $plan->priority;
    }

    public function isUnlimited(string $resourceType): bool
    {
        return $this->getLimit($resourceType) === -1;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
