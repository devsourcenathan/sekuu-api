<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwavePaymentGateway implements PaymentGatewayInterface
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
        $this->baseUrl = 'https://api.flutterwave.com/v3';
    }

    public function createPaymentIntent(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payments", [
                'tx_ref' => 'TXN-' . uniqid() . '-' . time(),
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'redirect_url' => $data['redirect_url'] ?? config('app.url') . '/payment/callback',
                'payment_options' => 'card,mobilemoney,ussd,banktransfer',
                'customer' => [
                    'email' => $data['email'],
                    'name' => $data['name'] ?? '',
                    'phonenumber' => $data['phone'] ?? '',
                ],
                'customizations' => [
                    'title' => $data['title'] ?? 'Course Payment',
                    'description' => $data['description'] ?? 'Payment for course enrollment',
                    'logo' => config('app.url') . '/logo.png',
                ],
                'meta' => [
                    'user_id' => $data['user_id'],
                    'course_id' => $data['course_id'] ?? null,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'payment_link' => $result['data']['link'],
                    'payment_intent_id' => $result['data']['id'],
                    'tx_ref' => $result['data']['tx_ref'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Payment initialization failed',
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function capturePayment(string $transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
            ])->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

            if ($response->successful()) {
                $result = $response->json();
                $data = $result['data'];

                if ($data['status'] === 'successful') {
                    return [
                        'success' => true,
                        'transaction_id' => $data['id'],
                        'tx_ref' => $data['tx_ref'],
                        'amount' => $data['amount'],
                        'currency' => $data['currency'],
                        'payment_method' => $data['payment_type'],
                    ];
                }

                return [
                    'success' => false,
                    'message' => "Payment status: {$data['status']}",
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave payment verification failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refundPayment(string $transactionId, float $amount)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
            ])->post("{$this->baseUrl}/transactions/{$transactionId}/refund", [
                'amount' => $amount,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['data']['id'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Refund failed',
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getPaymentDetails(string $transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
            ])->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

            if ($response->successful()) {
                $data = $response->json()['data'];

                return [
                    'success' => true,
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'payment_method' => $data['payment_type'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function savePaymentMethod(User $user, array $data): array
    {
        // For Mobile Money, save locally
        return [
            'success' => true,
            'payment_method_id' => uniqid('fw_mm_'),
            'type' => 'mobile_money',
            'provider' => $data['provider'],
            'phone_number' => $data['phone_number'],
        ];
    }

    public function listPaymentMethods(User $user): array
    {
        return [
            'success' => true,
            'payment_methods' => $user->paymentMethods()
                ->where('gateway', 'flutterwave')
                ->get()
                ->toArray(),
        ];
    }

    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        return true;
    }

    public function chargePaymentMethod(string $paymentMethodId, float $amount, string $currency): array
    {
        try {
            $paymentMethod = \App\Models\PaymentMethod::findOrFail($paymentMethodId);
            $metadata = $paymentMethod->metadata;

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
            ])->post("{$this->baseUrl}/charges?type=mobile_money_" . strtolower($metadata['provider']), [
                'tx_ref' => 'CHG-' . uniqid() . '-' . time(),
                'amount' => $amount,
                'currency' => $currency,
                'phone_number' => $metadata['phone_number'],
                'email' => $paymentMethod->user->email,
                'fullname' => $paymentMethod->user->name,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'];

                return [
                    'success' => true,
                    'payment_intent_id' => $data['id'],
                    'status' => $data['status'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Charge failed',
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave charge failed', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
   
 
}
