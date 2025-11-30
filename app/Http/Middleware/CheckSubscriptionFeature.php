<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check if user has the permission via roles
        if (!$user->hasPermission($feature)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'reason' => 'missing_permission',
            ], 403);
        }

        // Check if user's subscription includes this feature
        if (!$user->hasSubscriptionFeature($feature)) {
            $currentPlan = $user->getActivePlan();
            
            return response()->json([
                'success' => false,
                'message' => 'Your current subscription plan does not include this feature. Please upgrade.',
                'reason' => 'subscription_feature_required',
                'current_plan' => $currentPlan?->name,
                'upgrade_required' => true,
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
