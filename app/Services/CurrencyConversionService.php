<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.exchangerate-api.com/v4/latest/';

    public function __construct()
    {
        $this->apiKey = config('services.currency.api_key', '');
    }

    /**
     * Convert an amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getExchangeRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Get exchange rate between two currencies (with 1 hour cache)
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}{$from}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rates'][$to] ?? 1.0;
                }

                // Fallback: return 1.0 on error
                Log::warning("Currency conversion failed: {$from} to {$to}");
                return 1.0;
            } catch (\Exception $e) {
                Log::error("Currency API error: " . $e->getMessage());
                return 1.0;
            }
        });
    }

    /**
     * Get all exchange rates for a base currency
     */
    public function getAllRates(string $baseCurrency = 'USD'): array
    {
        $cacheKey = "exchange_rates_{$baseCurrency}";

        return Cache::remember($cacheKey, 3600, function () use ($baseCurrency) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}{$baseCurrency}");

                if ($response->successful()) {
                    return $response->json()['rates'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error("Currency API error: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Convert a course price for a specific user
     */
    public function convertCoursePrice(Course $course, User $user): float
    {
        return $this->convert(
            $course->price,
            $course->currency,
            $user->getPreferredCurrency()
        );
    }

    /**
     * Format a price with currency symbol
     */
    public function formatPrice(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'XAF' => 'FCFA',
            'XOF' => 'FCFA',
            'MAD' => 'DH',
            'TND' => 'DT',
            'DZD' => 'DA',
            'EGP' => 'E£',
            'NGN' => '₦',
            'GHS' => 'GH₵',
            'KES' => 'KSh',
            'ZAR' => 'R',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        $formattedAmount = number_format($amount, 2, '.', ',');

        // For FCFA, put symbol after amount
        if (in_array($currency, ['XAF', 'XOF'])) {
            return "{$formattedAmount} {$symbol}";
        }

        return "{$symbol}{$formattedAmount}";
    }
}
