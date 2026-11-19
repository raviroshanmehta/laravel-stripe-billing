<?php

namespace RaviRoshan\StripeBilling\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RaviRoshan\StripeBilling\Contracts\CheckoutGateway;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, string $plan, CheckoutGateway $gateway): RedirectResponse|JsonResponse
    {
        $priceId = config("stripe-billing.prices.{$plan}");
        abort_unless($priceId, 404, 'Unknown plan.');

        $url = $gateway->createSubscriptionCheckout($request->user(), $priceId);

        return $request->expectsJson()
            ? response()->json(['url' => $url])
            : redirect()->away($url);
    }
}
