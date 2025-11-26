<?php

namespace App\Services;

interface PaymentGatewayInterface
{
    public function createPaymentIntent(array $data);

    public function capturePayment(string $paymentIntentId);

    public function refundPayment(string $transactionId, float $amount);

    public function getPaymentDetails(string $transactionId);
}
