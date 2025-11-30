<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * List all subscription plans
     */
    public function index(): JsonResponse
    {
        $plans = $this->subscriptionService->getAllPlansWithStats();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get a specific plan
     */
    public function show(SubscriptionPlan $plan): JsonResponse
    {
        $plan->load(['limits', 'subscriptions' => function ($query) {
            $query->active();
        }]);

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * Create a new subscription plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'priority' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'limits.*' => 'integer|min:-1',
            'metadata' => 'nullable|array',
        ]);

        try {
            $plan = $this->subscriptionService->createPlan($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully.',
                'data' => $plan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a subscription plan
     */
    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', Rule::unique('subscription_plans')->ignore($plan->id)],
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'priority' => 'sometimes|integer|min:1',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'limits.*' => 'integer|min:-1',
            'metadata' => 'nullable|array',
        ]);

        try {
            $plan = $this->subscriptionService->updatePlan($plan, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully.',
                'data' => $plan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a subscription plan
     */
    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        try {
            $this->subscriptionService->deletePlan($plan);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set limits for a plan
     */
    public function setLimits(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'limits' => 'required|array',
            'limits.*' => 'integer|min:-1',
        ]);

        try {
            $this->subscriptionService->setLimits($plan, $validated['limits']);

            return response()->json([
                'success' => true,
                'message' => 'Plan limits updated successfully.',
                'data' => $plan->fresh('limits'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan limits.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set features for a plan
     */
    public function setFeatures(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'features' => 'required|array',
            'features.*' => 'string',
        ]);

        try {
            $plan->update([
                'features' => $validated['features'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan features updated successfully.',
                'data' => $plan->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan features.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
