# Laravel Stripe Billing

Drop-in Stripe subscriptions for Laravel. It sends users to Stripe Checkout and keeps a local `stripe_subscriptions` table in sync through webhooks that are **signature-verified, replay-protected and idempotent**, so a retried webhook is never applied twice.

It's built for SaaS apps that want Stripe-hosted checkout and a reliable local source of truth for "is this user subscribed?", without the full weight of Cashier.

## Features

- `POST /billing/checkout/{plan}` creates a Stripe Checkout session for a configured plan and redirects to it (or returns JSON for SPAs)
- `POST /stripe/webhook` verifies the `Stripe-Signature` header, rejects stale events and stores each event ID so retries are ignored
- Syncs `customer.subscription.created`, `.updated` and `.deleted`: status, price, cancel-at-period-end and current period end
- `Billable` trait adds `$user->subscribed()` and `$user->subscribed('pro')`
- `SubscriptionChanged` event so you can grant access or send emails
- Idempotency key on checkout creation, so double clicks don't create duplicate sessions
- Works with Laravel 11, 12 and 13 and stripe-php 16+

## Installation

This repository isn't on Packagist yet. Add it as a VCS repository in your app's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/raviroshanmehta/laravel-stripe-billing" }
]
```

```bash
composer require raviroshanmehta/laravel-stripe-billing:dev-main
php artisan migrate
php artisan vendor:publish --tag=stripe-billing-config   # optional
```

Add to `.env`:

```dotenv
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_BASIC=price_...
STRIPE_PRICE_PRO=price_...
```

Add the trait to your `User` model:

```php
use RaviRoshan\StripeBilling\Concerns\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

## Usage

Start a checkout from a form or button:

```blade
<form method="POST" action="{{ route('stripe-billing.checkout', 'pro') }}">
    @csrf
    <button>Upgrade to Pro</button>
</form>
```

Gate features:

```php
if ($request->user()->subscribed('pro')) {
    // ...
}
```

React to changes:

```php
use RaviRoshan\StripeBilling\Events\SubscriptionChanged;

Event::listen(function (SubscriptionChanged $event) {
    // $event->subscription->status, $event->stripeEventType
});
```

## Webhook setup

In the Stripe Dashboard, go to **Developers → Webhooks** and add an endpoint at `https://your-app.com/stripe/webhook` with these events:

- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`

Copy the signing secret into `STRIPE_WEBHOOK_SECRET`. For local development:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## How the webhook stays safe

1. **Signature check**: `Stripe\Webhook::constructEvent` validates the HMAC signature and rejects events older than `webhook_tolerance` seconds (5 minutes by default).
2. **Idempotency**: each event ID is inserted into `stripe_webhook_events` inside the same transaction as the subscription update. If Stripe retries, the duplicate insert fails and the event is acknowledged without being applied again.
3. **User mapping**: checkout copies `user_id` into the subscription's metadata, so every subscription event carries its owner and no extra API calls are needed.

## Testing

```bash
composer install
vendor/bin/phpunit
```

The tests cover invalid and stale signatures, subscription creation, retried events, cancellation, unrelated events and the checkout redirect. CI runs them on PHP 8.3 with Laravel 12 and PHP 8.4 with Laravel 13.

## Author

Built by [Ravi Roshan](https://github.com/raviroshanmehta), Lead Backend Engineer. I build payment integrations and SaaS backends with Stripe, Razorpay and PayPal. [Get in touch on LinkedIn](https://www.linkedin.com/in/raviroshanmehta).
