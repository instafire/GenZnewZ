<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    protected $fillable = [
        'url',
        'events',
        'secret',
        'is_active',
        'consecutive_failures',
        'last_delivery_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_delivery_at' => 'datetime',
    ];

    /**
     * The signing secret is shown once at creation and never serialized.
     */
    protected $hidden = [
        'secret',
    ];

    public const EVENT_ARTICLE_PUBLISHED = 'article.published';

    public const ALLOWED_EVENTS = [
        self::EVENT_ARTICLE_PUBLISHED,
    ];

    public function subscribedTo(string $event): bool
    {
        $events = $this->events ?: [];

        return in_array($event, $events, true) || in_array('*', $events, true);
    }
}
