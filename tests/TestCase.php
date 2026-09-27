<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Post fixtures must never reach the live Pexels API. With
        // QUEUE_CONNECTION=sync (set in phpunit.xml) the PostImageObserver runs
        // synchronously on every created post, so a real PEXELS_API_KEY leaking
        // through from .env made tests network-dependent and non-deterministic —
        // a fixture could come back with a real image attached, which then
        // changed which homepage rail the post landed in.
        config(['services.pexels.api_key' => '']);
    }
}
