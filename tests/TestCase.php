<?php

namespace RaviRoshan\StripeBilling\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use RaviRoshan\StripeBilling\StripeBillingServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function getPackageProviders($app): array
    {
        return [StripeBillingServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('stripe-billing.secret', 'sk_test_dummy');
        $app['config']->set('stripe-billing.webhook_secret', self::WEBHOOK_SECRET);
        $app['config']->set('stripe-billing.prices.pro', 'price_pro_123');
        $app['config']->set('stripe-billing.routes.checkout_middleware', ['web']);
    }

    /**
     * Build a Stripe-Signature header the same way Stripe does.
     */
    protected function stripeSignature(string $payload, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return "t={$timestamp},v1={$signature}";
    }

    protected function postWebhook(array $event, ?string $signature = null)
    {
        $payload = json_encode($event);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature ?? $this->stripeSignature($payload),
        ], $payload);
    }
}
