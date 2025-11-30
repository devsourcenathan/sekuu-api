<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsageTracking;
use App\Models\SubscriptionPlan;

class UsageTrackingService
{
    /**
     * Check if user can create a resource (within limits)
     */
    public function checkLimit(User $user, string $resourceType): bool
    {
        $plan = $user->getActivePlan();
        
        if (!$plan) {
            return false;
        }

        $limit = $plan->getLimit($resourceType);
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($user, $resourceType);
        
        return $currentUsage < $limit;
    }

    /**
     * Increment usage for a resource
     */
    public function incrementUsage(User $user, string $resourceType, int $amount = 1): void
    {
        $tracking = UsageTracking::firstOrCreate(
            [
                'user_id' => $user->id,
                'resource_type' => $resourceType,
            ],
            [
                'current_count' => 0,
            ]
        );

        $tracking->incrementUsage($amount);
    }

    /**
     * Decrement usage for a resource
     */
    public function decrementUsage(User $user, string $resourceType, int $amount = 1): void
    {
        $tracking = UsageTracking::where('user_id', $user->id)
            ->where('resource_type', $resourceType)
            ->first();

        if ($tracking) {
            $tracking->decrementUsage($amount);
        }
    }

    /**
     * Get current usage for a resource
     */
    public function getCurrentUsage(User $user, string $resourceType): int
    {
        return $user->getCurrentUsage($resourceType);
    }

    /**
     * Get usage percentage for a resource
     */
    public function getUsagePercentage(User $user, string $resourceType): float
    {
        $plan = $user->getActivePlan();
        
        if (!$plan) {
            return 0;
        }

        $limit = $plan->getLimit($resourceType);
        
        if ($limit === -1) {
            return 0; // Unlimited
        }

        if ($limit === 0) {
            return 100; // No quota
        }

        $currentUsage = $this->getCurrentUsage($user, $resourceType);
        
        return min(100, ($currentUsage / $limit) * 100);
    }

    /**
     * Reset usage for a resource
     */
    public function resetUsage(User $user, string $resourceType): void
    {
        $tracking = UsageTracking::where('user_id', $user->id)
            ->where('resource_type', $resourceType)
            ->first();

        if ($tracking) {
            $tracking->reset();
        }
    }

    /**
     * Sync usage with actual count (for data integrity)
     */
    public function syncUsage(User $user, string $resourceType): void
    {
        $actualCount = $this->getActualCount($user, $resourceType);
        
        $tracking = UsageTracking::firstOrCreate(
            [
                'user_id' => $user->id,
                'resource_type' => $resourceType,
            ],
            [
                'current_count' => 0,
            ]
        );

        $tracking->setCount($actualCount);
    }

    /**
     * Get actual count from database
     */
    protected function getActualCount(User $user, string $resourceType): int
    {
        switch ($resourceType) {
            case 'courses':
                return $user->instructedCourses()->count();
            case 'sessions':
                return \App\Models\Session::where('instructor_id', $user->id)->count();
            case 'groups':
                return \App\Models\Group::where('instructor_id', $user->id)->count();
            case 'packs':
                return \App\Models\Pack::where('instructor_id', $user->id)->count();
            default:
                return 0;
        }
    }

    /**
     * Get all usage stats for a user
     */
    public function getAllUsageStats(User $user): array
    {
        $plan = $user->getActivePlan();
        
        if (!$plan) {
            return [];
        }

        $resourceTypes = ['courses', 'sessions', 'groups', 'packs'];
        $stats = [];

        foreach ($resourceTypes as $resourceType) {
            $limit = $plan->getLimit($resourceType);
            $usage = $this->getCurrentUsage($user, $resourceType);
            $percentage = $this->getUsagePercentage($user, $resourceType);

            $stats[$resourceType] = [
                'current' => $usage,
                'limit' => $limit,
                'percentage' => round($percentage, 2),
                'remaining' => $limit === -1 ? -1 : max(0, $limit - $usage),
                'is_unlimited' => $limit === -1,
            ];
        }

        return $stats;
    }
}
