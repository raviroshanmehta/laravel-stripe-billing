<?php

namespace RaviRoshan\StripeBilling\Tests;

use Illuminate\Support\Facades\Event;
use RaviRoshan\StripeBilling\Events\SubscriptionChanged;
use RaviRoshan\StripeBilling\Models\Subscription;

class WebhookTest extends TestCase
{
    private function subscriptionEvent(string $type, string $status = 'active', string $eventId = 'evt_1'): array
    {
        return [
            'id' => $eventId,
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => 'sub_123',
                'object' => 'subscription',
                'customer' => 'cus_123',
                'status' => $status,
                'cancel_at_period_end' => false,
                'metadata' => ['user_id' => '42'],
                'items' => ['object' => 'list', 'data' => [[
                    'id' => 'si_1',
                    'object' => 'subscription_item',
                    'current_period_end' => 1893456000,
                    'price' => ['id' => 'price_pro_123', 'object' => 'price'],
                ]]],
            ]],
        ];
    }

    public function test_it_rejects_an_invalid_signature(): void
    {
        $this->postWebhook($this->subscriptionEvent('customer.subscription.created'), 't=1,v1=bad')
            ->assertStatus(400);

        $this->assertDatabaseCount('stripe_subscriptions', 0);
    }

    public function test_it_rejects_a_stale_signature(): void
    {
        $event = $this->subscriptionEvent('customer.subscription.created');
        $stale = $this->stripeSignature(json_encode($event), time() - 3600);

        $this->postWebhook($event, $stale)->assertStatus(400);
    }

    public function test_it_creates_a_subscription_from_a_signed_event(): void
    {
        Event::fake([SubscriptionChanged::class]);

        $this->postWebhook($this->subscriptionEvent('customer.subscription.created'))->assertOk();

        $subscription = Subscription::firstOrFail();
        $this->assertSame(42, (int) $subscription->user_id);
        $this->assertSame('cus_123', $subscription->stripe_customer_id);
        $this->assertSame('price_pro_123', $subscription->stripe_price_id);
        $this->assertTrue($subscription->isActive());
        $this->assertSame(1893456000, $subscription->current_period_end->getTimestamp());
        Event::assertDispatched(SubscriptionChanged::class);
    }

    public function test_a_retried_event_is_applied_only_once(): void
    {
        $event = $this->subscriptionEvent('customer.subscription.created');

        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk()->assertSee('Already processed');

        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_it_marks_a_deleted_subscription_as_canceled(): void
    {
        $this->postWebhook($this->subscriptionEvent('customer.subscription.created', 'active', 'evt_1'));
        $this->postWebhook($this->subscriptionEvent('customer.subscription.deleted', 'canceled', 'evt_2'))->assertOk();

        $this->assertFalse(Subscription::firstOrFail()->isActive());
        $this->assertDatabaseCount('stripe_subscriptions', 1);
    }

    public function test_it_ignores_unrelated_events(): void
    {
        $this->postWebhook(['id' => 'evt_x', 'object' => 'event', 'type' => 'invoice.paid', 'data' => ['object' => []]])
            ->assertOk()
            ->assertSee('Ignored');
    }
}
