<?php

namespace RaviRoshan\StripeBilling\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use RaviRoshan\StripeBilling\Contracts\CheckoutGateway;

class CheckoutTest extends TestCase
{
    public function test_it_redirects_to_stripe_checkout_for_a_known_plan(): void
    {
        $fake = new class implements CheckoutGateway {
            public array $calls = [];

            public function createSubscriptionCheckout(Authenticatable $user, string $priceId): string
            {
                $this->calls[] = [$user->getAuthIdentifier(), $priceId];

                return 'https://checkout.stripe.com/c/pay/cs_test_123';
            }
        };
        $this->app->instance(CheckoutGateway::class, $fake);

        $user = (new User())->forceFill(['id' => 7, 'email' => 'jane@example.com']);

        $this->actingAs($user)
            ->post('/billing/checkout/pro')
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

        $this->assertSame([[7, 'price_pro_123']], $fake->calls);
    }

    public function test_it_returns_404_for_an_unknown_plan(): void
    {
        $user = (new User())->forceFill(['id' => 7]);

        $this->actingAs($user)->post('/billing/checkout/enterprise')->assertNotFound();
    }
}
