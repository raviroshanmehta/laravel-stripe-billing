<?php

return [
    'secret' => env('STRIPE_SECRET'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    // Webhooks older than this many seconds are rejected (replay protection).
    'webhook_tolerance' => 300,

    // Plans users can subscribe to: key => Stripe price ID.
    'prices' => [
        'basic' => env('STRIPE_PRICE_BASIC'),
        'pro' => env('STRIPE_PRICE_PRO'),
    ],

    'success_url' => env('STRIPE_SUCCESS_URL', '/billing/success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', '/billing/cancel'),

    'routes' => [
        'prefix' => 'billing',
        'checkout_middleware' => ['web', 'auth'],
        'webhook_path' => 'stripe/webhook',
    ],
];
