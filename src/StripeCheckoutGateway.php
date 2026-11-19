<?php

namespace RaviRoshan\StripeBilling;

use Illuminate\Contracts\Auth\Authenticatable;
use RaviRoshan\StripeBilling\Contracts\CheckoutGateway;
use Stripe\StripeClient;

class StripeCheckoutGateway implements CheckoutGateway
{
    public function __construct(private readonly StripeClient $stripe)
    {
    }

    public function createSubscriptionCheckout(Authenticatable $user, string $priceId): string
    {
        $userId = (string) $user->getAuthIdentifier();

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'client_reference_id' => $userId,
            'customer_email' => $user->email ?? null,
            // Copied onto the subscription, so every subscription webhook knows its user.
            'subscription_data' => ['metadata' => ['user_id' => $userId]],
            'success_url' => url(config('stripe-billing.success_url')).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url(config('stripe-billing.cancel_url')),
        ], [
            // Safe to retry: a double click will not create two sessions.
            'idempotency_key' => 'checkout-'.$userId.'-'.$priceId.'-'.now()->format('YmdHi'),
        ]);

        return $session->url;
    }
}
