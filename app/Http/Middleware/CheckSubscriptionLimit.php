<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SubscriptionService;

class CheckSubscriptionLimit
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $plan = $user->getActivePlan();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription plan found.',
                'reason' => 'no_subscription',
            ], 402);
        }

        $limit = $plan->getLimit($resourceType);
        $currentUsage = $user->getCurrentUsage($resourceType);

        // Check if limit is reached
        if ($limit !== -1 && $currentUsage >= $limit) {
            $nextPlan = $this->subscriptionService->getNextPlan($plan);

            return response()->json([
                'success' => false,
                'message' => "You have reached the limit for {$resourceType} on your current plan.",
                'reason' => 'limit_reached',
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'current_plan' => $plan->name,
                'next_plan' => $nextPlan ? [
                    'name' => $nextPlan->name,
                    'price' => $nextPlan->price,
                    'limit' => $nextPlan->getLimit($resourceType),
                ] : null,
                'upgrade_required' => true,
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
