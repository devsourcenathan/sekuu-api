<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodService
{
    protected array $gateways = [];

    public function __construct(
        StripePaymentGateway $stripe,
        PayPalPaymentGateway $paypal,
        FlutterwavePaymentGateway $flutterwave
    ) {
        $this->gateways = [
            'stripe' => $stripe,
            'paypal' => $paypal,
            'flutterwave' => $flutterwave,
        ];
    }

    protected function getGateway(string $gateway): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gateway])) {
            throw new \Exception("Payment gateway '{$gateway}' not found");
        }

        return $this->gateways[$gateway];
    }

    public function savePaymentMethod(User $user, string $gateway, array $data): PaymentMethod
    {
        $gatewayService = $this->getGateway($gateway);
        $result = $gatewayService->savePaymentMethod($user, $data);

        if (!$result['success']) {
            throw new \Exception($result['message'] ?? 'Failed to save payment method');
        }

        // Create local record
        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->id,
            'gateway' => $gateway,
            'gateway_payment_method_id' => $result['payment_method_id'],
            'type' => $result['type'],
            'is_default' => false,
            'metadata' => $result,
            'last_four' => $result['last_four'] ?? null,
            'brand' => $result['brand'] ?? null,
            'expires_at' => isset($result['exp_month'], $result['exp_year'])
                ? "{$result['exp_year']}-{$result['exp_month']}-01"
                : null,
        ]);

        // Set as default if it's the first payment method
        if ($user->paymentMethods()->count() === 1) {
            $paymentMethod->setAsDefault();
        }

        return $paymentMethod;
    }

    public function listPaymentMethods(User $user): array
    {
        return $user->paymentMethods()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function deletePaymentMethod(PaymentMethod $paymentMethod): bool
    {
        $gatewayService = $this->getGateway($paymentMethod->gateway);

        // Delete from gateway
        $gatewayService->deletePaymentMethod($paymentMethod->gateway_payment_method_id);

        // If this was the default, set another as default
        if ($paymentMethod->is_default) {
            $nextMethod = $paymentMethod->user->paymentMethods()
                ->where('id', '!=', $paymentMethod->id)
                ->first();

            if ($nextMethod) {
                $nextMethod->setAsDefault();
            }
        }

        // Delete local record
        return $paymentMethod->delete();
    }

    public function setDefaultPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->setAsDefault();
    }

    public function chargePaymentMethod(PaymentMethod $paymentMethod, float $amount, string $currency): array
    {
        $gatewayService = $this->getGateway($paymentMethod->gateway);

        return $gatewayService->chargePaymentMethod(
            $paymentMethod->gateway_payment_method_id,
            $amount,
            $currency
        );
    }
}
