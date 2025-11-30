<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InstructorPayoutController extends Controller
{
    /**
     * Get instructor earnings summary
     * GET /api/instructor/earnings
     */
    public function getEarnings()
    {
        // $this->authorize('view-earnings');

        $user = auth()->user();

        return response()->json([
            'success' => true,
            // 'pending_earnings' => $user->calculatePendingEarnings(),
            // 'total_earnings' => $user->payments()
            //     ->where('status', 'completed')
            //     ->sum('instructor_amount'),
            // 'can_request_payout' => $user->canRequestPayout(),
            'can_request_payout' => true,
            'payout_threshold' => $user->payout_threshold,
            'payout_method' => $user->payout_method,
            'payout_currency' => $user->payout_currency,
        ]);
    }

    /**
     * Update payout settings
     * PUT /api/instructor/payout-settings
     */
    public function updatePayoutSettings(Request $request)
    {
        $this->authorize('update-payout-settings');

        $validated = $request->validate([
            'method' => 'required|in:bank_transfer,mobile_money,paypal',
            'currency' => 'required|string|size:3',
            'schedule' => 'required|in:weekly,monthly',
            'threshold' => 'required|numeric|min:10',
            'details' => 'required|array',
            'details.account_number' => 'required_if:method,bank_transfer',
            'details.bank_name' => 'required_if:method,bank_transfer',
            'details.phone_number' => 'required_if:method,mobile_money',
            'details.provider' => 'required_if:method,mobile_money|in:mtn,orange,airtel,wave',
        ]);

        auth()->user()->updatePayoutSettings($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payout settings updated successfully',
        ]);
    }

    /**
     * Request a payout
     * POST /api/instructor/request-payout
     */
    public function requestPayout(Request $request)
    {
        $this->authorize('request-payout');

        $user = auth()->user();

        if (!$user->canRequestPayout()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance for payout',
            ], 400);
        }

        // TODO: Implement payout request logic
        // This would create a payout record and trigger the payment process

        return response()->json([
            'success' => true,
            'message' => 'Payout request submitted successfully',
        ]);
    }
}
