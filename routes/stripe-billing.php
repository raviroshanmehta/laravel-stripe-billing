<?php

use Illuminate\Support\Facades\Route;
use RaviRoshan\StripeBilling\Http\Controllers\CheckoutController;
use RaviRoshan\StripeBilling\Http\Controllers\WebhookController;

Route::middleware(config('stripe-billing.routes.checkout_middleware'))
    ->prefix(config('stripe-billing.routes.prefix'))
    ->group(function () {
        Route::post('checkout/{plan}', CheckoutController::class)->name('stripe-billing.checkout');
    });

// No session or CSRF middleware: Stripe calls this directly and it is verified by signature.
Route::post(config('stripe-billing.routes.webhook_path'), WebhookController::class)
    ->name('stripe-billing.webhook');
