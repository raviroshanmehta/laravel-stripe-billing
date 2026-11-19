<?php

namespace RaviRoshan\StripeBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'stripe_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'current_period_end' => 'datetime',
    ];

    public const ACTIVE_STATUSES = ['active', 'trialing'];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }
}
