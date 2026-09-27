<?php

namespace Tests\Unit;

use App\Services\DuplicateContentService;
use Mockery;
use Tests\TestCase;

class DuplicateContentServiceTest extends TestCase
{
    public function test_make_submission_topic_signatures_returns_unique_focus_and_title_signatures(): void
    {
        $service = new DuplicateContentService();

        $this->assertSame(
            ['notebooklm', 'notebooklm-py'],
            $service->makeSubmissionTopicSignatures(
                'notebooklm-py: The Unofficial Python API for Google NotebookLM',
                'NotebookLM'
            )
        );

        $this->assertSame(
            ['prompt engineering'],
            $service->makeSubmissionTopicSignatures(
                'Prompt Engineering in 2026: The Complete Beginner to Pro Guide',
                'prompt engineering'
            )
        );
    }

    public function test_full_duplicate_check_blocks_same_reporter_topic_collisions(): void
    {
        $service = Mockery::mock(DuplicateContentService::class)->makePartial();

        $emptyCheck = [
            'is_duplicate' => false,
            'has_similar' => false,
            'similar_posts' => [],
            'highest_similarity' => 0,
        ];

        $service->shouldReceive('checkTitleSimilarity')->once()->andReturn($emptyCheck);
        $service->shouldReceive('checkContentSimilarity')->once()->andReturn($emptyCheck);
        $service->shouldReceive('checkDescriptionSimilarity')->once()->andReturn($emptyCheck);
        $service->shouldReceive('checkLeadSimilarity')->once()->andReturn(array_merge($emptyCheck, [
            'lead_excerpt' => 'candidate lead excerpt',
        ]));
        $service->shouldReceive('checkReporterTopicCollision')
            ->once()
            ->with(
                79,
                'notebooklm-py: The Unofficial API That Supercharges Google NotebookLM',
                'NotebookLM',
                '<p>Programmatic NotebookLM access for Python workflows.</p>',
                null
            )
            ->andReturn([
                'is_duplicate' => true,
                'has_similar' => true,
                'should_block' => true,
                'similar_posts' => [
                    ['id' => 1233, 'title' => 'notebooklm-py: The Unofficial Python API for Google NotebookLM'],
                ],
                'highest_similarity' => 88,
            ]);
        $service->shouldReceive('checkPlagiarismIndicators')->once()->andReturn([
            'has_warnings' => false,
            'warnings' => [],
            'uniqueness_score' => 100,
        ]);

        $result = $service->fullDuplicateCheck(
            'notebooklm-py: The Unofficial API That Supercharges Google NotebookLM',
            '<p>Programmatic NotebookLM access for Python workflows.</p>',
            null,
            'notebooklm-py gives you programmatic access to Google NotebookLM for Python workflows and automation.',
            'NotebookLM',
            79
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertTrue($result['should_block']);
        $this->assertTrue($result['reporter_topic_check']['has_similar']);
        $this->assertStringContainsString('already has a published article on the same topic', $result['recommendation']);
    }
}
