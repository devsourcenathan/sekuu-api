<?php

namespace App\Services;

use App\Models\User;

interface PaymentGatewayInterface
{
    // Existing payment methods
    public function createPaymentIntent(array $data);

    public function capturePayment(string $paymentIntentId);

    public function refundPayment(string $transactionId, float $amount);

    public function getPaymentDetails(string $transactionId);

    // New methods for payment method management
    public function savePaymentMethod(User $user, array $data): array;

    public function listPaymentMethods(User $user): array;

    public function deletePaymentMethod(string $paymentMethodId): bool;

    public function chargePaymentMethod(string $paymentMethodId, float $amount, string $currency): array;
}
