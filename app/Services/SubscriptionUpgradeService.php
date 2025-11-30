<?php

namespace App\Services;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Carbon\Carbon;

class SubscriptionUpgradeService
{
    protected UsageTrackingService $usageTrackingService;

    public function __construct(UsageTrackingService $usageTrackingService)
    {
        $this->usageTrackingService = $usageTrackingService;
    }

    /**
     * Upgrade user to a new plan
     */
    public function upgradePlan(User $user, SubscriptionPlan $newPlan, array $paymentData = []): UserSubscription
    {
        $currentSubscription = $user->currentSubscription;
        $currentPlan = $user->getActivePlan();

        // Validate upgrade
        if ($currentPlan && !$newPlan->isHigherThan($currentPlan)) {
            throw new \Exception("New plan must be higher than current plan. Use downgradePlan() instead.");
        }

        // Cancel current subscription if exists
        if ($currentSubscription && $currentSubscription->isActive()) {
            $currentSubscription->cancel();
        }

        // Create new subscription
        $newSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $newPlan->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $this->calculateExpirationDate($newPlan),
            'metadata' => array_merge($paymentData, [
                'upgraded_from' => $currentPlan?->name,
                'upgraded_at' => now()->toIso8601String(),
            ]),
        ]);

        // Update user's current subscription
        $user->update([
            'current_subscription_id' => $newSubscription->id,
        ]);

        return $newSubscription->load('plan');
    }

    /**
     * Downgrade user to a lower plan
     */
    public function downgradePlan(User $user, SubscriptionPlan $newPlan): UserSubscription
    {
        $currentSubscription = $user->currentSubscription;
        $currentPlan = $user->getActivePlan();

        // Validate downgrade
        if ($currentPlan && $newPlan->isHigherThan($currentPlan)) {
            throw new \Exception("New plan must be lower than current plan. Use upgradePlan() instead.");
        }

        // Check if user's current usage exceeds new plan limits
        $this->validateDowngradeUsage($user, $newPlan);

        // Cancel current subscription
        if ($currentSubscription && $currentSubscription->isActive()) {
            $currentSubscription->cancel();
        }

        // Create new subscription
        $newSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $newPlan->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $this->calculateExpirationDate($newPlan),
            'metadata' => [
                'downgraded_from' => $currentPlan?->name,
                'downgraded_at' => now()->toIso8601String(),
            ],
        ]);

        // Update user's current subscription
        $user->update([
            'current_subscription_id' => $newSubscription->id,
        ]);

        return $newSubscription->load('plan');
    }

    /**
     * Cancel user's subscription
     */
    public function cancelSubscription(User $user): void
    {
        $currentSubscription = $user->currentSubscription;

        if ($currentSubscription && $currentSubscription->isActive()) {
            $currentSubscription->cancel();
            
            // Optionally assign to free plan
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();
            
            if ($freePlan) {
                $this->downgradePlan($user, $freePlan);
            }
        }
    }

    /**
     * Calculate proration amount for upgrade
     */
    public function calculateProration(User $user, SubscriptionPlan $newPlan): float
    {
        $currentSubscription = $user->currentSubscription;
        
        if (!$currentSubscription || !$currentSubscription->isActive()) {
            return $newPlan->price;
        }

        $currentPlan = $currentSubscription->plan;
        $daysRemaining = now()->diffInDays($currentSubscription->expires_at ?? now()->addMonth());
        $daysInMonth = 30;

        // Calculate unused portion of current plan
        $unusedAmount = ($currentPlan->price / $daysInMonth) * $daysRemaining;

        // Calculate prorated amount for new plan
        $proratedAmount = max(0, $newPlan->price - $unusedAmount);

        return round($proratedAmount, 2);
    }

    /**
     * Validate that user's usage doesn't exceed new plan limits
     */
    protected function validateDowngradeUsage(User $user, SubscriptionPlan $newPlan): void
    {
        $resourceTypes = ['courses', 'sessions', 'groups', 'packs'];
        $violations = [];

        foreach ($resourceTypes as $resourceType) {
            $newLimit = $newPlan->getLimit($resourceType);
            
            // Skip unlimited limits
            if ($newLimit === -1) {
                continue;
            }

            $currentUsage = $this->usageTrackingService->getCurrentUsage($user, $resourceType);

            if ($currentUsage > $newLimit) {
                $violations[] = [
                    'resource' => $resourceType,
                    'current' => $currentUsage,
                    'new_limit' => $newLimit,
                ];
            }
        }

        if (!empty($violations)) {
            $message = "Cannot downgrade: Current usage exceeds new plan limits. ";
            foreach ($violations as $violation) {
                $message .= "{$violation['resource']}: {$violation['current']}/{$violation['new_limit']}. ";
            }
            throw new \Exception($message);
        }
    }

    /**
     * Calculate expiration date for subscription
     */
    protected function calculateExpirationDate(SubscriptionPlan $plan): ?Carbon
    {
        // For now, all plans are monthly
        // You can customize this based on plan metadata
        return now()->addMonth();
    }

    /**
     * Get upgrade preview
     */
    public function getUpgradePreview(User $user, SubscriptionPlan $newPlan): array
    {
        $currentPlan = $user->getActivePlan();
        $proratedAmount = $this->calculateProration($user, $newPlan);

        return [
            'current_plan' => $currentPlan?->name,
            'new_plan' => $newPlan->name,
            'current_price' => $currentPlan?->price ?? 0,
            'new_price' => $newPlan->price,
            'prorated_amount' => $proratedAmount,
            'new_features' => array_diff($newPlan->features ?? [], $currentPlan?->features ?? []),
            'new_limits' => $newPlan->limits->mapWithKeys(function ($limit) {
                return [$limit->resource_type => $limit->limit_value];
            })->toArray(),
        ];
    }
}
