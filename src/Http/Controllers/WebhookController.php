<?php

namespace RaviRoshan\StripeBilling\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RaviRoshan\StripeBilling\Events\SubscriptionChanged;
use RaviRoshan\StripeBilling\Models\Subscription;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class WebhookController extends Controller
{
    private const SUBSCRIPTION_EVENTS = [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ];

    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('stripe-billing.webhook_secret'),
                (int) config('stripe-billing.webhook_tolerance'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            Log::warning('Rejected Stripe webhook', ['reason' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        if (! in_array($event->type, self::SUBSCRIPTION_EVENTS, true)) {
            return response('Ignored', 200);
        }

        try {
            DB::transaction(function () use ($event) {
                // Primary-key insert fails if this event was already processed.
                DB::table('stripe_webhook_events')->insert([
                    'id' => $event->id,
                    'type' => $event->type,
                    'processed_at' => now(),
                ]);

                $this->syncSubscription($event);
            });
        } catch (QueryException $e) {
            if (DB::table('stripe_webhook_events')->where('id', $event->id)->exists()) {
                return response('Already processed', 200);
            }
            throw $e;
        }

        return response('OK', 200);
    }

    private function syncSubscription(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        $userId = $stripeSubscription->metadata->user_id ?? null;

        if (! $userId) {
            Log::warning('Stripe subscription has no user_id metadata', ['subscription' => $stripeSubscription->id]);

            return;
        }

        // Newer Stripe API versions report the period end on the subscription item.
        $periodEnd = $stripeSubscription->current_period_end
            ?? $stripeSubscription->items->data[0]->current_period_end
            ?? null;

        $subscription = Subscription::updateOrCreate(
            ['stripe_id' => $stripeSubscription->id],
            [
                'user_id' => $userId,
                'stripe_customer_id' => $stripeSubscription->customer,
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id ?? null,
                'status' => $event->type === 'customer.subscription.deleted' ? 'canceled' : $stripeSubscription->status,
                'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
                'current_period_end' => $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null,
            ],
        );

        SubscriptionChanged::dispatch($subscription, $event->type);
    }
}
