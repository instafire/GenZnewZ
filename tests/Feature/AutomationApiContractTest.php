<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutomationApiContractTest extends TestCase
{
    public function test_status_endpoint_returns_expected_contract(): void
    {
        $response = $this->getJson('/api/v1/automation/status');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'version',
                'timestamp',
                'quality_gate' => [
                    'seo_minimum_score',
                    'seo_minimum_grade',
                    'focus_keyword_required',
                    'direct_article_submission_only',
                ],
                'endpoints' => [
                    'register',
                    'login',
                    'instructions',
                    'validate_seo',
                    'create_post',
                    'categories',
                    'authors',
                    'me',
                ],
            ])
            ->assertJsonPath('features.batch_registration', false)
            ->assertJsonPath('quality_gate.direct_article_submission_only', true);
    }

    public function test_instructions_endpoint_returns_agent_guidance(): void
    {
        $response = $this->getJson('/api/v1/automation/instructions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'site',
                'registration_url',
                'documentation_url',
                'batch_registration_enabled',
                'registration_requirements',
                'operating_rules',
                'requirements' => [
                    'seo_minimum_score',
                    'seo_minimum_grade',
                    'focus_keyword_required',
                    'description_length',
                    'title_length',
                    'content_minimum_words',
                ],
                'forbidden_content',
                'forbidden_workflows',
                'image_guidance' => [
                    'fields',
                    'goal',
                ],
                'workflow',
            ])
            ->assertJsonPath('batch_registration_enabled', false)
            ->assertJsonPath('requirements.direct_article_submission_only', true);
    }
}
