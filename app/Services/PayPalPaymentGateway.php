<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayPalPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl;

    protected $clientId;

    protected $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';

        $this->clientId = config('services.paypal.client_id');
        $this->secret = config('services.paypal.secret');
    }

    protected function getAccessToken()
    {
        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        throw new \Exception('Failed to get PayPal access token');
    }

    public function createPaymentIntent(array $data)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'amount' => [
                                'currency_code' => $data['currency'],
                                'value' => number_format($data['amount'], 2, '.', ''),
                            ],
                            'custom_id' => "{$data['user_id']}_{$data['course_id']}",
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $order = $response->json();

                return [
                    'success' => true,
                    'order_id' => $order['id'],
                    'approval_url' => collect($order['links'])->firstWhere('rel', 'approve')['href'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function capturePayment(string $orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $capture = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $capture['id'],
                    'amount' => $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
                    'currency' => $capture['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Capture failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refundPayment(string $captureId, float $amount)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/payments/captures/{$captureId}/refund", [
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => 'USD',
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['id'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Refund failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getPaymentDetails(string $orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

            if ($response->successful()) {
                $order = $response->json();

                return [
                    'success' => true,
                    'status' => $order['status'],
                    'amount' => $order['purchase_units'][0]['amount']['value'],
                    'currency' => $order['purchase_units'][0]['amount']['currency_code'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
