<?php

namespace Botble\Sitemap\Events;

use Botble\Base\Events\Event;

class SitemapUpdatedEvent extends Event
{
    public function __construct(public ?string $sitemapUrl = null)
    {
    }
}
