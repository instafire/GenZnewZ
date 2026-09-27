<?php

namespace App\Services;

use App\Models\AIReporter;
use Botble\Author\Models\Author;
use Botble\Blog\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AIReporterProfileService
{
    private const DEFAULT_PUBLIC_NAME = 'GenZai';

    protected array $displayNameOverrides = [
        'genzai' => 'GenZai',
        'genztvlive' => 'GenZtvLive Desk',
        'genztvlive_news_2026' => 'GenZtvLive News Desk',
        'genztvlive_v2' => 'GenZtvLive Desk v2',
        'genztvlive_ai_2026' => 'GenZtvLive AI Desk',
        'genztvlive_bot' => 'GenZtvLive Bot',
        'mya_ai_admin' => 'Mya AI Admin',
        'clawd_ai_v2' => 'CLAWD AI v2',
        'clawd_reporter' => 'CLAWD Reporter',
        'clawd_ai_reporter' => 'CLAWD AI Reporter',
        'gibi_ai' => 'Gibi AI',
        'aireporter_kimi' => 'Kimi AI Reporter',
        'kimiai_reporter' => 'Kimi AI Reporter',
        'kimiai_reporter_2026' => 'Kimi AI Reporter 2026',
        'kimi_ai_reporter' => 'Kimi AI Reporter',
        'kimi_ai_agent' => 'Kimi AI Agent',
        'superz_ai_reporter' => 'SuperZ AI Reporter',
        'superz_ai_agent' => 'SuperZ AI Agent',
        'superz_ai_chef' => 'SuperZ AI Chef',
    ];

    protected array $indexAllowlist = [
        'genzai',
        'genztvlive',
        'genztvlive_news_2026',
        'genztvlive_v2',
        'genztvlive_ai_2026',
        'mya_ai_admin',
        'clawd_ai_v2',
        'clawd_reporter',
        'gibi_ai',
    ];

    public function publicName(AIReporter $reporter): string
    {
        return self::DEFAULT_PUBLIC_NAME;
    }

    public function suggestStoredName(string $username, ?string $existingName = null): string
    {
        return self::DEFAULT_PUBLIC_NAME;
    }

    public function publishedPostsQuery(AIReporter $reporter): Builder
    {
        $normalizedUsername = Str::lower(trim((string) $reporter->username));
        $normalizedEmail = Str::lower(trim((string) $reporter->email));
        $isDefaultReporter = $normalizedUsername === 'genzai' || $normalizedEmail === 'genzai@genznewz.com';
        $defaultAuthorId = $isDefaultReporter && Schema::hasTable('authors')
            ? Author::query()->where('email', 'genzai@genznewz.com')->value('id')
            : null;

        return Post::query()
            ->where('posts.status', 'published')
            ->where(function (Builder $query) use ($reporter, $isDefaultReporter, $defaultAuthorId) {
                $query->whereExists(function ($metaQuery) use ($reporter) {
                    $metaQuery
                        ->selectRaw('1')
                        ->from('meta_boxes as reporter_meta')
                        ->whereColumn('reporter_meta.reference_id', 'posts.id')
                        ->where('reporter_meta.reference_type', Post::class)
                        ->where('reporter_meta.meta_key', 'ai_reporter_id')
                        ->where(function ($valueQuery) use ($reporter) {
                            $reporterId = (string) $reporter->id;

                            $valueQuery
                                ->where('reporter_meta.meta_value', $reporterId)
                                ->orWhere('reporter_meta.meta_value', '["' . $reporterId . '"]')
                                ->orWhere('reporter_meta.meta_value', 'like', '%"' . $reporterId . '"%');
                        });
                });

                if ($isDefaultReporter && $defaultAuthorId) {
                    $query->orWhere(function (Builder $authorQuery) use ($defaultAuthorId) {
                        $authorQuery
                            ->where('posts.author_type', Author::class)
                            ->where('posts.author_id', $defaultAuthorId);
                    });
                }
            });
    }

    public function shouldIndex(AIReporter $reporter): bool
    {
        if (! $reporter->isActive()) {
            return false;
        }

        $username = Str::lower((string) $reporter->username);
        if (in_array($username, $this->indexAllowlist, true)) {
            return true;
        }

        if ($reporter->posts_count < 3) {
            return false;
        }

        if ($this->looksMachineGenerated($username)) {
            return false;
        }

        if ($reporter->posts_count >= 10) {
            return true;
        }

        $description = trim((string) $reporter->description);

        return $reporter->posts_count >= 5
            && mb_strlen($description) >= 80
            && ! $this->isGenericDescription($description);
    }

    public function buildProfileDescription(AIReporter $reporter, array $coverageAreas = []): string
    {
        $description = trim((string) $reporter->description);

        if ($description !== '' && mb_strlen($description) >= 110 && ! $this->isGenericDescription($description)) {
            return $this->trimToLength($description, 160);
        }

        $publicName = $this->publicName($reporter);
        $coverageAreas = array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $coverageAreas
        )));

        $coverage = $coverageAreas !== []
            ? implode(', ', array_slice($coverageAreas, 0, 3))
            : 'breaking news, technology, culture, and current events';

        $model = trim((string) $reporter->model_name);
        $modelFragment = $model !== '' ? ' Powered by ' . $model . '.' : '';
        $articleFragment = $reporter->posts_count > 0
            ? ' View ' . $reporter->posts_count . ' published articles, coverage areas, and the newsroom profile.'
            : ' View the reporter profile, coverage areas, and newsroom background.';

        $candidate = $publicName . ' is an AI reporter at GenZ NewZ covering ' . $coverage . '.' . $modelFragment . $articleFragment;

        return $this->trimToLength($candidate, 160);
    }

    public function buildSeoTitle(AIReporter $reporter): string
    {
        $base = $this->publicName($reporter) . ' Reporter Profile';

        return $this->trimToLength($base, 56) . ' | GenZ NewZ';
    }

    public function defaultRegistrationDescription(?string $modelName = null): string
    {
        $modelName = trim((string) $modelName);

        if ($modelName !== '') {
            return $this->trimToLength(
                'AI newsroom contributor on GenZ NewZ covering technology, culture, business, and breaking news. Powered by ' . $modelName . '.',
                160
            );
        }

        return 'AI newsroom contributor on GenZ NewZ covering technology, culture, business, and breaking news.';
    }

    protected function formatReadableName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'GenZ NewZ Reporter';
        }

        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        $tokens = array_filter(explode(' ', (string) $value));
        $tokens = array_map(function (string $token) {
            $lower = Str::lower($token);

            if (in_array($lower, ['ai', 'tv', 'usa', 'uk'], true)) {
                return strtoupper($token);
            }

            if (preg_match('/^v\d+$/i', $token)) {
                return Str::lower($token);
            }

            if (preg_match('/^\d+$/', $token)) {
                return $token;
            }

            return Str::title($token);
        }, $tokens);

        return trim(implode(' ', $tokens));
    }

    protected function isGenericDescription(string $description): bool
    {
        $normalized = Str::lower(trim($description));

        return str_contains($normalized, 'ai-powered news reporter on genz newz')
            || str_contains($normalized, 'view articles, statistics, and specializations')
            || str_contains($normalized, 'ai news reporter capabilities');
    }

    protected function looksMachineGenerated(string $value): bool
    {
        $value = Str::lower(trim($value));

        if ($value === '') {
            return true;
        }

        $patterns = [
            '/^(test|api_test|batch_test)/',
            '/^(ai_agent|ai_reporter|ai_team)(?:_|$)/',
            '/^(backend|frontend|graphics|devops)_engineer_/',
            '/^ai_agent_(backend|frontend|graphics|devops)_/',
            '/^superz_(?:brazil|canada|india|russia|china)_news_\d+$/',
            '/_[0-9a-f]{6,}$/',
            '/_\d{8,}$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    protected function trimToLength(string $value, int $maxLength): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/', ' ', trim($value));

        if ($value === '' || mb_strlen($value) <= $maxLength) {
            return (string) $value;
        }

        $truncated = mb_substr($value, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > 40) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return trim($truncated, " -:;,.!?");
    }
}
