<?php

namespace App\Console\Commands;

use App\Services\AutomationContentGuardService;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Page\Models\Page;
use Botble\Slug\Models\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CleanSeoContent extends Command
{
    protected $signature = 'seo:clean-content {--apply : Persist changes instead of reporting only} {--report= : Custom report output path}';

    protected $description = 'Merge duplicate posts, delete disposable test content, and repair missing SEO descriptions';

    public function handle(AutomationContentGuardService $guard): int
    {
        $apply = (bool) $this->option('apply');
        $reportPath = $this->option('report') ?: storage_path('app/reports/seo-cleanup-' . now()->format('Ymd-His') . '.json');

        $posts = Post::with(['slugable', 'categories'])
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'deleted_test_posts' => [],
            'merged_title_duplicates' => [],
            'merged_content_duplicates' => [],
            'post_description_repairs' => [],
            'page_description_repairs' => [],
            'category_description_repairs' => [],
        ];

        $this->info('Scanning published posts: ' . $posts->count());

        $testPosts = $posts->filter(fn (Post $post) => $guard->isDisposableTestContent($post->name, $post->description, $post->content));
        foreach ($testPosts as $post) {
            $report['deleted_test_posts'][] = $this->formatPostSummary($post, $guard->getDisposableReasons($post->name, $post->description, $post->content));
            if ($apply) {
                $this->deletePost($post);
            }
        }

        $survivors = $apply
            ? Post::with(['slugable', 'categories'])->where('status', 'published')->orderBy('id')->get()
            : $posts->reject(fn (Post $post) => $testPosts->contains('id', $post->id))->values();

        $titleGroups = $survivors->groupBy(fn (Post $post) => $this->normalizeText($post->name))
            ->filter(fn (Collection $group) => $group->count() > 1);

        foreach ($titleGroups as $group) {
            $freshGroup = $apply
                ? Post::with(['slugable', 'categories'])->whereIn('id', $group->pluck('id')->all())->where('status', 'published')->get()
                : $group->values();

            if ($freshGroup->count() < 2) {
                continue;
            }

            $canonical = $this->selectCanonicalPost($freshGroup, $guard);
            $merged = [];

            foreach ($freshGroup as $post) {
                if ($post->id === $canonical->id) {
                    continue;
                }

                $merged[] = $this->formatPostSummary($post);
                if ($apply) {
                    $this->mergePostIntoCanonical($canonical, $post);
                    $canonical->refresh()->load(['slugable', 'categories']);
                }
            }

            if ($merged !== []) {
                $report['merged_title_duplicates'][] = [
                    'title' => $canonical->name,
                    'canonical' => $this->formatPostSummary($canonical),
                    'merged' => $merged,
                ];
            }
        }

        $survivors = $apply
            ? Post::with(['slugable', 'categories'])->where('status', 'published')->orderBy('id')->get()
            : $this->simulateRemainingPosts($survivors, $report['merged_title_duplicates']);

        $contentGroups = $survivors->groupBy(fn (Post $post) => sha1($this->normalizeText(strip_tags((string) $post->content))))
            ->filter(fn (Collection $group) => $group->count() > 1 && $this->normalizeText(strip_tags((string) $group->first()->content)) !== '');

        foreach ($contentGroups as $group) {
            $freshGroup = $apply
                ? Post::with(['slugable', 'categories'])->whereIn('id', $group->pluck('id')->all())->where('status', 'published')->get()
                : $group->values();

            if ($freshGroup->count() < 2) {
                continue;
            }

            $canonical = $this->selectCanonicalPost($freshGroup, $guard);
            $merged = [];

            foreach ($freshGroup as $post) {
                if ($post->id === $canonical->id) {
                    continue;
                }

                $merged[] = $this->formatPostSummary($post);
                if ($apply) {
                    $this->mergePostIntoCanonical($canonical, $post);
                    $canonical->refresh()->load(['slugable', 'categories']);
                }
            }

            if ($merged !== []) {
                $report['merged_content_duplicates'][] = [
                    'canonical' => $this->formatPostSummary($canonical),
                    'merged' => $merged,
                ];
            }
        }

        $remainingPosts = $apply
            ? Post::with(['slugable'])->where('status', 'published')->orderBy('id')->get()
            : $this->simulateRemainingPosts($survivors, $report['merged_content_duplicates']);

        foreach ($remainingPosts as $post) {
            $description = trim((string) $post->description);
            if ($description !== '' && mb_strlen($description) >= 120) {
                continue;
            }

            $newDescription = $guard->buildMetaDescriptionFromContent($post->name, (string) $post->content, 160);
            if ($newDescription === '' || mb_strlen($newDescription) < 120) {
                $newDescription = Str::limit(
                    $post->name . '. Read the full GenZ NewZ report for background, key developments, and what the story means right now.',
                    160,
                    ''
                );
            }

            $report['post_description_repairs'][] = [
                'id' => $post->id,
                'slug' => $post->slugable?->key,
                'title' => $post->name,
                'old_description' => $post->description,
                'new_description' => $newDescription,
            ];

            if ($apply) {
                $post->description = $newDescription;
                $post->saveQuietly();
            }
        }

        $pages = Page::query()->where('status', 'published')->get();
        foreach ($pages as $page) {
            if (trim((string) $page->description) !== '') {
                continue;
            }

            $newDescription = $this->defaultPageDescription($page->name ?: 'Page');
            $report['page_description_repairs'][] = [
                'id' => $page->id,
                'title' => $page->name,
                'new_description' => $newDescription,
            ];

            if ($apply) {
                $page->description = $newDescription;
                $page->saveQuietly();
            }
        }

        $categories = Category::query()->where('status', 'published')->get();
        foreach ($categories as $category) {
            if (trim((string) $category->description) !== '') {
                continue;
            }

            $newDescription = 'Latest ' . $category->name . ' news, analysis, and updates from GenZ NewZ.';
            $report['category_description_repairs'][] = [
                'id' => $category->id,
                'name' => $category->name,
                'new_description' => $newDescription,
            ];

            if ($apply) {
                $category->description = $newDescription;
                $category->saveQuietly();
            }
        }

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Report written to ' . $reportPath);
        $this->table(['Action', 'Count'], [
            ['Deleted test posts', count($report['deleted_test_posts'])],
            ['Merged title duplicates', count($report['merged_title_duplicates'])],
            ['Merged content duplicates', count($report['merged_content_duplicates'])],
            ['Post description repairs', count($report['post_description_repairs'])],
            ['Page description repairs', count($report['page_description_repairs'])],
            ['Category description repairs', count($report['category_description_repairs'])],
        ]);

        return self::SUCCESS;
    }

    protected function selectCanonicalPost(Collection $group, AutomationContentGuardService $guard): Post
    {
        return $group->sort(function (Post $a, Post $b) use ($guard) {
            $scoreComparison = $this->postQualityScore($b, $guard) <=> $this->postQualityScore($a, $guard);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return optional($a->created_at)->timestamp <=> optional($b->created_at)->timestamp;
        })->first();
    }

    protected function postQualityScore(Post $post, AutomationContentGuardService $guard): int
    {
        $wordCount = str_word_count(strip_tags((string) $post->content));
        $descriptionLength = mb_strlen(trim((string) $post->description));
        $slug = (string) ($post->slugable?->key ?? '');

        $score = min($wordCount, 1800);
        $score += min($descriptionLength, 180);
        $score += !empty($post->image) ? 400 : 0;
        $score += $post->is_featured ? 150 : 0;
        $score += (int) min((int) $post->views, 5000) / 10;
        $score += preg_match('/-\d+$/', $slug) ? -250 : 125;
        $score += $guard->isDisposableTestContent($post->name, $post->description, $post->content) ? -5000 : 0;

        return $score;
    }

    protected function mergePostIntoCanonical(Post $canonical, Post $duplicate): void
    {
        DB::transaction(function () use ($canonical, $duplicate) {
            $canonicalWordCount = str_word_count(strip_tags((string) $canonical->content));
            $duplicateWordCount = str_word_count(strip_tags((string) $duplicate->content));

            if ((trim((string) $canonical->description) === '' || mb_strlen((string) $canonical->description) < 120)
                && mb_strlen(trim((string) $duplicate->description)) >= 120) {
                $canonical->description = $duplicate->description;
            }

            if (($canonicalWordCount + 100) < $duplicateWordCount) {
                $canonical->content = $duplicate->content;
            }

            if (empty($canonical->image) && !empty($duplicate->image)) {
                $canonical->image = $duplicate->image;
                foreach (['pexels_photographer', 'pexels_photographer_url', 'pexels_photo_url', 'pexels_photo_id'] as $key) {
                    $value = MetaBox::getMetaData($duplicate, $key, true);
                    if (!empty($value)) {
                        MetaBox::saveMetaBoxData($canonical, $key, $value);
                    }
                }
            }

            $canonical->is_featured = (bool) $canonical->is_featured || (bool) $duplicate->is_featured;
            $canonical->views = (int) $canonical->views + (int) $duplicate->views;
            $canonical->saveQuietly();

            $categoryIds = $canonical->categories->pluck('id')
                ->merge($duplicate->categories->pluck('id'))
                ->unique()
                ->values()
                ->all();
            $canonical->categories()->sync($categoryIds);

            foreach (['focus_keyword', 'image_search_query', 'image_description', 'content_fingerprint'] as $key) {
                $canonicalValue = MetaBox::getMetaData($canonical, $key, true);
                $duplicateValue = MetaBox::getMetaData($duplicate, $key, true);
                if (empty($canonicalValue) && !empty($duplicateValue)) {
                    MetaBox::saveMetaBoxData($canonical, $key, $duplicateValue);
                }
            }

            if (DB::getSchemaBuilder()->hasTable('fob_comments')) {
                DB::table('fob_comments')
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->whereNull('reference_url')
                    ->update(['reference_url' => $canonical->url]);

                DB::table('fob_comments')
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $duplicate->id)
                    ->update(['reference_id' => $canonical->id]);
            }

            Slug::query()
                ->where('reference_type', Post::class)
                ->where('reference_id', $duplicate->id)
                ->update(['reference_id' => $canonical->id]);

            DB::table('meta_boxes')
                ->where('reference_type', Post::class)
                ->where('reference_id', $duplicate->id)
                ->delete();

            $duplicate->delete();
        });
    }

    protected function deletePost(Post $post): void
    {
        DB::transaction(function () use ($post) {
            if (DB::getSchemaBuilder()->hasTable('fob_comments')) {
                DB::table('fob_comments')
                    ->where('reference_type', Post::class)
                    ->where('reference_id', $post->id)
                    ->delete();
            }

            DB::table('meta_boxes')
                ->where('reference_type', Post::class)
                ->where('reference_id', $post->id)
                ->delete();

            $post->delete();
        });
    }

    protected function simulateRemainingPosts(Collection $posts, array $mergeReports): Collection
    {
        $removedIds = collect($mergeReports)
            ->flatMap(fn (array $item) => collect($item['merged'] ?? [])->pluck('id'))
            ->values()
            ->all();

        return $posts->reject(fn (Post $post) => in_array($post->id, $removedIds, true))->values();
    }

    protected function formatPostSummary(Post $post, array $reasons = []): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slugable?->key,
            'title' => $post->name,
            'url' => $post->url,
            'reasons' => $reasons,
        ];
    }

    protected function normalizeText(string $text): string
    {
        return (string) preg_replace('/\s+/', ' ', Str::lower(trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }

    protected function defaultPageDescription(string $title): string
    {
        return Str::limit($title . ' on GenZ NewZ. Find the latest newsroom information, account access details, and site resources.', 160, '');
    }
}
