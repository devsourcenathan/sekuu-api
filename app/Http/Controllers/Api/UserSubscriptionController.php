<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserSubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;
    protected UsageTrackingService $usageTrackingService;

    public function __construct(
        SubscriptionService $subscriptionService,
        UsageTrackingService $usageTrackingService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->usageTrackingService = $usageTrackingService;
    }

    /**
     * Get current user's subscription
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Admins and super_admins bypass subscription system
        if ($user->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => null,
                    'plan' => [
                        'id' => 0,
                        'name' => 'Admin',
                        'slug' => 'admin',
                        'priority' => 999,
                        'features' => [],
                        'limits' => [],
                    ],
                    'limits' => [],
                    'usage' => [],
                    'is_admin' => true,
                ],
            ]);
        }

        $subscription = $user->currentSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 404);
        }

        $subscription->load('plan.limits');

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'plan' => $subscription->plan,
                'limits' => $subscription->plan->limits,
                'usage' => $this->usageTrackingService->getAllUsageStats($user),
                'is_admin' => false,
            ],
        ]);
    }

    /**
     * Get available plans for upgrade
     */
    public function availablePlans(Request $request): JsonResponse
    {
        $plans = $this->subscriptionService->getAvailablePlans();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get detailed usage breakdown
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();
        $usage = $this->usageTrackingService->getAllUsageStats($user);

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    /**
     * Get subscription history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $history = $user->subscriptionHistory()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
