<?php

namespace RaviRoshan\StripeBilling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RaviRoshan\StripeBilling\Models\Subscription;

/**
 * Fired after a subscription is created, updated or canceled from a webhook.
 * Listen to it to send emails, grant access or update usage limits.
 */
class SubscriptionChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $stripeEventType,
    ) {
    }
}
