<?php

namespace RaviRoshan\StripeBilling\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CheckoutGateway
{
    /**
     * Create a hosted Checkout session for a subscription and return its URL.
     */
    public function createSubscriptionCheckout(Authenticatable $user, string $priceId): string;
}
