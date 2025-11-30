<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanLimit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Create a new subscription plan
     */
    public function createPlan(array $data): SubscriptionPlan
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $plan = SubscriptionPlan::create($data);

        // Set limits if provided
        if (isset($data['limits']) && is_array($data['limits'])) {
            $this->setLimits($plan, $data['limits']);
        }

        return $plan->load('limits');
    }

    /**
     * Update an existing subscription plan
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $plan->name && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $plan->update($data);

        // Update limits if provided
        if (isset($data['limits']) && is_array($data['limits'])) {
            $this->setLimits($plan, $data['limits']);
        }

        return $plan->fresh('limits');
    }

    /**
     * Delete a subscription plan
     */
    public function deletePlan(SubscriptionPlan $plan): bool
    {
        // Check if any active subscriptions exist
        $activeSubscriptions = $plan->subscriptions()->active()->count();
        
        if ($activeSubscriptions > 0) {
            throw new \Exception("Cannot delete plan with active subscriptions. Please migrate users first.");
        }

        return $plan->delete();
    }

    /**
     * Set limits for a subscription plan
     */
    public function setLimits(SubscriptionPlan $plan, array $limits): void
    {
        foreach ($limits as $resourceType => $limitValue) {
            SubscriptionPlanLimit::updateOrCreate(
                [
                    'subscription_plan_id' => $plan->id,
                    'resource_type' => $resourceType,
                ],
                [
                    'limit_value' => $limitValue,
                ]
            );
        }
    }

    /**
     * Get all available plans ordered by priority
     */
    public function getAvailablePlans(): Collection
    {
        return SubscriptionPlan::active()
            ->with('limits')
            ->orderedByPriority()
            ->get();
    }

    /**
     * Get the next higher plan
     */
    public function getNextPlan(SubscriptionPlan $currentPlan): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()
            ->where('priority', '>', $currentPlan->priority)
            ->orderedByPriority()
            ->first();
    }

    /**
     * Get plan by slug
     */
    public function getPlanBySlug(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $slug)
            ->with('limits')
            ->first();
    }

    /**
     * Get all plans with statistics
     */
    public function getAllPlansWithStats(): Collection
    {
        return SubscriptionPlan::withCount([
            'subscriptions',
            'subscriptions as active_subscriptions_count' => function ($query) {
                $query->active();
            }
        ])
        ->with('limits')
        ->orderedByPriority()
        ->get();
    }
}
