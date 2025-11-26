<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function calculateTotal(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        if ($course->is_free) {
            return response()->json([
                'success' => false,
                'message' => 'This course is free',
            ], 400);
        }

        $calculation = $this->paymentService->calculateTotal($course, $request->promo_code);

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    }

    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'payment_gateway' => 'required|in:stripe,paypal',
            'promo_code' => 'nullable|string|exists:promo_codes,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $course = Course::findOrFail($request->course_id);

        // Check if already enrolled
        $existingEnrollment = $request->user()->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this course',
            ], 400);
        }

        try {
            $result = $this->paymentService->createPayment(
                $request->user(),
                $course,
                $request->payment_gateway,
                $request->promo_code
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function completePayment(Request $request, $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'gateway_transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = $this->paymentService->completePayment(
                $payment,
                $request->gateway_transaction_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'data' => $payment->load(['course', 'invoice']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function myPayments(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with(['course', 'promoCode'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function requestRefund(Request $request, $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = $this->paymentService->refundPayment($payment, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function webhookStripe(Request $request)
    {
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;

                    $payment = Payment::where('gateway_transaction_id', $paymentIntent->id)->first();

                    if ($payment && $payment->status === 'pending') {
                        $this->paymentService->completePayment($payment, $paymentIntent->id);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;

                    $payment = Payment::where('gateway_transaction_id', $paymentIntent->id)->first();

                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                        ]);
                    }
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function webhookPaypal(Request $request)
    {
        // Verify PayPal webhook signature
        // Process webhook events

        return response()->json(['success' => true]);
    }
}
