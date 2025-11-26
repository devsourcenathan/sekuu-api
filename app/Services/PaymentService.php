<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected $stripeGateway;

    protected $paypalGateway;

    public function __construct(
        StripePaymentGateway $stripeGateway,
        PayPalPaymentGateway $paypalGateway
    ) {
        $this->stripeGateway = $stripeGateway;
        $this->paypalGateway = $paypalGateway;
    }

    public function calculateTotal(Course $course, ?string $promoCode = null)
    {
        $amount = $course->getCurrentPrice();
        $discount = 0;
        $promoCodeModel = null;

        if ($promoCode) {
            $promoCodeModel = PromoCode::where('code', $promoCode)->first();

            if ($promoCodeModel && $promoCodeModel->isValid($course->id, $amount)) {
                $discount = $promoCodeModel->calculateDiscount($amount);
            }
        }

        $total = max(0, $amount - $discount);
        $platformFee = $total * (config('payment.platform_fee_percentage', 10) / 100);
        $instructorAmount = $total - $platformFee;

        return [
            'subtotal' => $amount,
            'discount' => $discount,
            'total' => $total,
            'platform_fee' => round($platformFee, 2),
            'instructor_amount' => round($instructorAmount, 2),
            'promo_code' => $promoCodeModel,
        ];
    }

    public function createPayment(User $user, Course $course, string $gateway, ?string $promoCode = null)
    {
        $calculation = $this->calculateTotal($course, $promoCode);

        return DB::transaction(function () use ($user, $course, $gateway, $calculation) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'payment_gateway' => $gateway,
                'amount' => $calculation['total'],
                'currency' => $course->currency,
                'platform_fee' => $calculation['platform_fee'],
                'instructor_amount' => $calculation['instructor_amount'],
                'promo_code_id' => $calculation['promo_code']?->id,
                'discount_amount' => $calculation['discount'],
                'status' => 'pending',
            ]);

            // Create payment intent
            $gatewayService = $gateway === 'stripe' ? $this->stripeGateway : $this->paypalGateway;

            $result = $gatewayService->createPaymentIntent([
                'amount' => $calculation['total'],
                'currency' => $course->currency,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'email' => $user->email,
            ]);

            if ($result['success']) {
                $payment->update([
                    'gateway_transaction_id' => $result['payment_intent_id'] ?? $result['order_id'],
                    'metadata' => $result,
                ]);
            }

            return [
                'payment' => $payment,
                'gateway_response' => $result,
            ];
        });
    }

    public function completePayment(Payment $payment, string $gatewayTransactionId)
    {
        $gatewayService = $payment->payment_gateway === 'stripe'
            ? $this->stripeGateway
            : $this->paypalGateway;

        $result = $gatewayService->capturePayment($gatewayTransactionId);

        if ($result['success']) {
            return DB::transaction(function () use ($payment, $result) {
                $payment->update([
                    'status' => 'completed',
                    'gateway_transaction_id' => $result['transaction_id'],
                ]);

                // Update promo code usage
                if ($payment->promo_code_id) {
                    $payment->promoCode->increment('usage_count');
                }

                // Enroll user in course
                $courseService = app(CourseService::class);
                $courseService->enrollStudent($payment->course, $payment->user, $payment->id);

                return $payment->fresh();
            });
        }

        $payment->update([
            'status' => 'failed',
            'failure_reason' => $result['message'],
        ]);

        throw new \Exception($result['message']);
    }

    public function refundPayment(Payment $payment, string $reason)
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Only completed payments can be refunded');
        }

        $gatewayService = $payment->payment_gateway === 'stripe'
            ? $this->stripeGateway
            : $this->paypalGateway;

        $result = $gatewayService->refundPayment($payment->gateway_transaction_id, $payment->amount);

        if ($result['success']) {
            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            // Cancel enrollment
            $enrollment = $payment->user->enrollments()
                ->where('course_id', $payment->course_id)
                ->first();

            if ($enrollment) {
                $enrollment->update(['status' => 'cancelled']);
            }

            return $payment->fresh();
        }

        throw new \Exception($result['message']);
    }
}
