<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class ExampleTest extends TestCase
{
    // The homepage is served by Botble's installer middleware, which redirects
    // to /install/welcome unless the CMS counts as installed — that needs the
    // settings table, which only exists after migrations run. Without a
    // refreshed database this stock smoke test sees a 302 instead of a 200.
    use RefreshDatabaseWithPlugins;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
