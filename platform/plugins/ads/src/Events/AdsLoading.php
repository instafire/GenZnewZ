<?php

namespace Botble\Ads\Events;

use Botble\Base\Events\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdsLoading extends Event
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Collection $ads)
    {
    }
}
