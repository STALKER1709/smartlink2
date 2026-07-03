<?php

return [
    'username' => env('CAMPAY_USERNAME', ''),
    'password' => env('CAMPAY_PASSWORD', ''),
    'base_url' => env('CAMPAY_BASE_URL', 'https://demo.campay.net/api'),
    'callback_url' => env('CAMPAY_CALLBACK_URL', ''),
    'webhook_secret' => env('CAMPAY_WEBHOOK_SECRET', ''),
    'sandbox' => env('CAMPAY_SANDBOX', true),
    'currency' => 'XAF',
];
