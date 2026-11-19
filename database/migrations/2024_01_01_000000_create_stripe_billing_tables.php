<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('stripe_id')->unique();
            $table->string('stripe_customer_id')->index();
            $table->string('stripe_price_id')->nullable();
            $table->string('status');
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });

        // Stores processed event IDs so a retried webhook is never applied twice.
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->timestamp('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('stripe_subscriptions');
    }
};
