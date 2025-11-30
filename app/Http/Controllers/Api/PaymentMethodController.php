<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    protected PaymentMethodService $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * List all payment methods for the authenticated user
     * GET /api/payment-methods
     */
    public function index()
    { 
        $paymentMethods = $this->paymentMethodService->listPaymentMethods(auth()->user());

        return response()->json([
            'success' => true,
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * Add a new payment method
     * POST /api/payment-methods
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gateway' => 'required|string|in:stripe,paypal,flutterwave',
            'type' => 'required|string|in:card,mobile_money,bank_account',
            'data' => 'required|array',
        ]);

        try {
            $paymentMethod = $this->paymentMethodService->savePaymentMethod(
                auth()->user(),
                $validated['gateway'],
                $validated['data']
            );

            return response()->json([
                'success' => true,
                'payment_method' => $paymentMethod,
                'message' => 'Payment method added successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a payment method
     * DELETE /api/payment-methods/{id}
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->paymentMethodService->deletePaymentMethod($paymentMethod);

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully',
        ]);
    }

    /**
     * Set a payment method as default
     * PUT /api/payment-methods/{id}/default
     */
    public function setDefault($id)
    {
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->paymentMethodService->setDefaultPaymentMethod($paymentMethod);

        return response()->json([
            'success' => true,
            'message' => 'Default payment method updated',
        ]);
    }
}
