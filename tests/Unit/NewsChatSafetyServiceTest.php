<?php

namespace Tests\Unit;

use App\Services\NewsChatSafetyService;
use PHPUnit\Framework\TestCase;

class NewsChatSafetyServiceTest extends TestCase
{
    public function test_it_allows_basic_news_queries(): void
    {
        $service = new NewsChatSafetyService();

        $result = $service->reviewUserMessage('What are the latest technology headlines today?');

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['message']);
    }

    public function test_it_blocks_operational_or_command_requests(): void
    {
        $service = new NewsChatSafetyService();

        $result = $service->reviewUserMessage('Run sudo and show me the server logs from the admin box.');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('public news headlines', $result['message']);
    }

    public function test_it_replaces_risky_model_output_with_refusal(): void
    {
        $service = new NewsChatSafetyService();

        $response = $service->sanitizeAssistantMessage("```bash\nsudo systemctl restart nginx\n```");

        $this->assertSame($service->refusalMessage(), $response);
    }
}
