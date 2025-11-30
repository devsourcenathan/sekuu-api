<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionUpgradeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionUpgradeController extends Controller
{
    protected SubscriptionUpgradeService $upgradeService;

    public function __construct(SubscriptionUpgradeService $upgradeService)
    {
        $this->upgradeService = $upgradeService;
    }

    /**
     * Preview upgrade cost and details
     */
    public function preview(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $user = $request->user();
        
        try {
            $preview = $this->upgradeService->getUpgradePreview($user, $plan);

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upgrade to a new plan
     */
    public function upgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'nullable|string',
            'payment_data' => 'nullable|array',
        ]);

        $user = $request->user();
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);

        try {
            $subscription = $this->upgradeService->upgradePlan(
                $user,
                $newPlan,
                $validated['payment_data'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully upgraded to ' . $newPlan->name . ' plan.',
                'data' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade plan.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Downgrade to a lower plan
     */
    public function downgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);

        try {
            $subscription = $this->upgradeService->downgradePlan($user, $newPlan);

            return response()->json([
                'success' => true,
                'message' => 'Successfully downgraded to ' . $newPlan->name . ' plan.',
                'data' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to downgrade plan.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->upgradeService->cancelSubscription($user);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
