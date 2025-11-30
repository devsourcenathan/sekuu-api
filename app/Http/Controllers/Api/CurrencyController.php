<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get all exchange rates for a base currency
     * GET /api/currency/rates/{base}
     */
    public function getRates(string $base)
    {
        $rates = $this->currencyService->getAllRates($base);

        return response()->json([
            'success' => true,
            'base_currency' => $base,
            'rates' => $rates,
            'updated_at' => now(),
        ]);
    }

    /**
     * Convert an amount between currencies
     * GET /api/currency/convert?amount=100&from=USD&to=EUR
     */
    public function convert(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $converted = $this->currencyService->convert(
            $validated['amount'],
            $validated['from'],
            $validated['to']
        );

        $rate = $validated['amount'] > 0 ? $converted / $validated['amount'] : 0;

        return response()->json([
            'success' => true,
            'original_amount' => $validated['amount'],
            'original_currency' => $validated['from'],
            'converted_amount' => $converted,
            'target_currency' => $validated['to'],
            'rate' => round($rate, 6),
        ]);
    }

    /**
     * Update user's preferred currency
     * PUT /api/user/settings/currency
     */
    public function updatePreferredCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3|in:USD,EUR,GBP,CAD,XAF,XOF,MAD,TND,DZD,EGP,NGN,GHS,KES,ZAR',
        ]);

        auth()->user()->update([
            'preferred_currency' => strtoupper($validated['currency']),
        ]);

        return response()->json([
            'success' => true,
            'currency' => auth()->user()->preferred_currency,
            'message' => 'Preferred currency updated successfully',
        ]);
    }
}
