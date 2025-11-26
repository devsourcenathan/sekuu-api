<?php

return [
    'platform_fee_percentage' => env('PLATFORM_FEE_PERCENTAGE', 10),
    'currency' => env('DEFAULT_CURRENCY', 'USD'),
    'min_withdrawal_amount' => env('MIN_WITHDRAWAL_AMOUNT', 50),
];
