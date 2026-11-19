<?php

namespace RaviRoshan\StripeBilling;

use Illuminate\Support\ServiceProvider;
use RaviRoshan\StripeBilling\Contracts\CheckoutGateway;
use Stripe\StripeClient;

class StripeBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-billing.php', 'stripe-billing');

        $this->app->singleton(CheckoutGateway::class, function ($app) {
            return new StripeCheckoutGateway(
                new StripeClient((string) $app['config']->get('stripe-billing.secret')),
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/stripe-billing.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/stripe-billing.php' => config_path('stripe-billing.php'),
        ], 'stripe-billing-config');
    }
}
