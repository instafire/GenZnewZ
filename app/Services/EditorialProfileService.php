<?php

namespace App\Services;

class EditorialProfileService
{
    protected array $profiles = [
        'aman' => [
            'name' => 'Aman',
            'job_title' => 'Editor-in-Chief',
            'description' => 'Aman leads GenZ NewZ editorial strategy, reviews AI-assisted stories before publication, and covers technology, AI, and digital culture.',
            'expertise' => ['Technology', 'AI', 'Digital Culture', 'Editorial Standards'],
            'same_as' => [],
        ],
        'mya' => [
            'name' => 'Mya',
            'job_title' => 'Lifestyle and Culture Editor',
            'description' => 'Mya covers culture, wellness, identity, and lifestyle with a focus on readable reporting and real-world relevance for younger audiences.',
            'expertise' => ['Culture', 'Lifestyle', 'Wellness', 'Identity'],
            'same_as' => [],
        ],
        'abe' => [
            'name' => 'Abe',
            'job_title' => 'Politics and World Affairs Correspondent',
            'description' => 'Abe covers politics, policy, and global affairs with an emphasis on clarity, context, and accountability.',
            'expertise' => ['Politics', 'World Affairs', 'Policy', 'Explainers'],
            'same_as' => [],
        ],
        'genzai' => [
            'name' => 'GenZai',
            'job_title' => 'AI Reporter',
            'description' => 'GenZai is the AI-assisted reporting system behind rapid GenZ NewZ coverage, structured explainers, and newsroom automation workflows.',
            'expertise' => ['AI Reporting', 'News Automation', 'Structured Explainers'],
            'same_as' => [],
        ],
        'mya ai admin' => [
            'name' => 'Mya AI Admin',
            'job_title' => 'Systems Editor',
            'description' => 'Mya AI Admin supports infrastructure, automation reliability, and operating systems across the GenZ NewZ publishing stack.',
            'expertise' => ['Infrastructure', 'Automation', 'Operations'],
            'same_as' => [],
        ],
    ];

    public function profileFor(?string $name): array
    {
        $normalized = $this->normalizeName($name);

        $profile = $this->profiles[$normalized] ?? [
            'name' => $name ?: 'GenZ NewZ Staff',
            'job_title' => 'Staff Writer',
            'description' => ($name ?: 'GenZ NewZ Staff') . ' contributes reporting and explainers to GenZ NewZ.',
            'expertise' => ['News Reporting'],
            'same_as' => [],
        ];

        $profile['name'] = $profile['name'] ?: ($name ?: 'GenZ NewZ Staff');

        return $profile;
    }

    public function reviewerForPost(mixed $post = null): array
    {
        return $this->profileFor('Aman');
    }

    protected function normalizeName(?string $name): string
    {
        return strtolower(trim((string) $name));
    }
}
