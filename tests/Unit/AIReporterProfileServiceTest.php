<?php

namespace Tests\Unit;

use App\Models\AIReporter;
use App\Services\AIReporterProfileService;
use Tests\TestCase;

class AIReporterProfileServiceTest extends TestCase
{
    public function test_public_name_is_locked_to_genzai(): void
    {
        $service = new AIReporterProfileService();
        $reporter = new AIReporter([
            'name' => 'Some Other Bot',
            'username' => 'superz_ai_agent',
            'email' => 'bot@example.com',
        ]);

        $this->assertSame('GenZai', $service->publicName($reporter));
        $this->assertSame('GenZai', $service->suggestStoredName('another_agent'));
    }
}
