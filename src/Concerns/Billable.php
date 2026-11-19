<?php

namespace RaviRoshan\StripeBilling\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use RaviRoshan\StripeBilling\Models\Subscription;

/**
 * Add to your User model: `use Billable;`
 */
trait Billable
{
    public function stripeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function subscribed(?string $plan = null): bool
    {
        $query = $this->stripeSubscriptions()->active();

        if ($plan !== null) {
            $query->where('stripe_price_id', config("stripe-billing.prices.{$plan}"));
        }

        return $query->exists();
    }
}
