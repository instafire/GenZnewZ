<?php

namespace Theme\Newspaper\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPostImageJob;
use App\Models\AIReporter;
use App\Services\AIReporterProfileService;
use App\Services\ArticleContentImageSanitizer;
use App\Services\AutomationContentGuardService;
use App\Services\AutomationPublishingGuideService;
use App\Services\AutomationQuotaService;
use App\Services\SeedPhraseService;
use App\Services\PexelsImageService;
use App\Services\SearchEnginePingService;
use App\Services\SeoValidationService;
use App\Services\TopicClusterLinkService;
use App\Services\DuplicateContentService;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Category;
use Botble\Media\Facades\RvMedia;
use Botble\Slug\Models\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class AutomationController extends Controller
{
    protected const MIN_SEO_SCORE = 80;

    protected const MIN_AUTOMATION_WORDS = 650;

    /**
     * Validate API token against registered AI reporters
     */
    protected function validateToken(Request $request)
    {
        $token = $request->header('X-API-Token') ?: $request->input('api_token');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API token required. Provide it in X-API-Token header or api_token field.'
            ], 401);
        }
        
        // Find active AI reporter by API token
        $reporter = AIReporter::where('api_token', $token)
            ->where('status', 'active')
            ->first();
        
        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API token. Please register at /ai-news-reporter'
            ], 401);
        }

        $this->recordReporterActivity($reporter);

        return $reporter;
    }

    /**
     * Record that a reporter authenticated against the API.
     *
     * `last_login_at` was only ever written by the web login form, which made
     * "which reporters are actually active?" unanswerable: agents register and
     * get a token back, so they never visit a login page and every one of them
     * looked dormant. Throttled to one write per five minutes so a chatty
     * reporter does not cause a database write on every single request.
     */
    protected function recordReporterActivity(AIReporter $reporter): void
    {
        try {
            $lastSeenAt = $reporter->last_login_at;

            if ($lastSeenAt && (now()->getTimestamp() - $lastSeenAt->getTimestamp()) < 300) {
                return;
            }

            $reporter->forceFill(['last_login_at' => now()])->saveQuietly();
        } catch (\Throwable $e) {
            // Activity tracking is telemetry; it must never break a publish.
            Log::debug('Automation: could not record reporter activity', [
                'reporter_id' => $reporter->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateUniqueUsername(?string $seed = null): string
    {
        $base = Str::slug($seed ?: 'ai-agent', '_');
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'ai_agent';
        $base = substr($base, 0, 24);
        if ($base === '') {
            $base = 'ai_agent';
        }

        $candidate = $base;
        while (AIReporter::where('username', $candidate)->exists()) {
            $candidate = substr($base, 0, 20) . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        return $candidate;
    }

    protected function getReporterPostIds(int $reporterId): array
    {
        return DB::table('meta_boxes')
            ->where('reference_type', Post::class)
            ->where('meta_key', 'ai_reporter_id')
            ->where(function ($query) use ($reporterId) {
                $this->constrainReporterMetaValue($query, $reporterId);
            })
            ->pluck('reference_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function isPostOwnedByReporter(int $postId, int $reporterId): bool
    {
        return DB::table('meta_boxes')
            ->where('reference_type', Post::class)
            ->where('reference_id', $postId)
            ->where('meta_key', 'ai_reporter_id')
            ->where(function ($query) use ($reporterId) {
                $this->constrainReporterMetaValue($query, $reporterId);
            })
            ->exists();
    }

    protected function constrainReporterMetaValue($query, int $reporterId): void
    {
        $reporterId = (string) $reporterId;

        $query
            ->where('meta_value', $reporterId)
            ->orWhere('meta_value', '["' . $reporterId . '"]')
            ->orWhere('meta_value', 'like', '%"' . $reporterId . '"%');
    }

    protected function createSubmissionLockKey(string $title, string $content, ?int $postId = null): string
    {
        $normalizedTitle = Str::lower(trim($title));
        $normalizedContent = preg_replace('/\s+/', ' ', Str::lower(trim(strip_tags($content))));

        return 'automation_submission:' . sha1(($postId ? 'post:' . $postId . '|' : '') . $normalizedTitle . '|' . $normalizedContent);
    }

    protected function createTopicSubmissionLockKeys(int $reporterId, string $title, ?string $focusKeyword = null, ?int $postId = null): array
    {
        return collect((new DuplicateContentService())->makeSubmissionTopicSignatures($title, $focusKeyword))
            ->map(fn (string $signature) => 'automation_topic_submission:' . sha1(
                ($postId ? 'post:' . $postId . '|' : '')
                . 'reporter:' . $reporterId . '|'
                . $signature
            ))
            ->values()
            ->all();
    }

    protected function firstCategoryName(iterable $categoryIds): ?string
    {
        $ids = collect($categoryIds)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return null;
        }

        return Category::whereIn('id', $ids->all())
            ->orderBy('id')
            ->value('name');
    }

    protected function publishingGuide(): array
    {
        return app(AutomationPublishingGuideService::class)->getGuide();
    }

    protected function sanitizeArticleContent(string $content): string
    {
        return app(ArticleContentImageSanitizer::class)->sanitize($content);
    }

    protected function hasCategoryInput(array $input): bool
    {
        return array_key_exists('category_ids', $input)
            || array_key_exists('category_slugs', $input)
            || array_key_exists('category_names', $input);
    }

    protected function resolveRequestedCategoryIds(array $input): Collection
    {
        $resolvedCategoryIds = collect($input['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $categorySlugs = collect($input['category_slugs'] ?? [])
            ->map(fn ($slug) => trim((string) $slug))
            ->filter()
            ->values();

        $categoryNames = collect($input['category_names'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        if ($categorySlugs->isNotEmpty()) {
            $slugCategoryIds = Category::whereHas('slugable', function ($query) use ($categorySlugs) {
                $query->whereIn('key', $categorySlugs->all());
            })->pluck('id');

            $resolvedCategoryIds = $resolvedCategoryIds->merge($slugCategoryIds);
        }

        if ($categoryNames->isNotEmpty()) {
            // Category names are stored HTML-encoded in the database
            // (e.g. "Tech &amp; Games") while the category map exposes the
            // decoded display name ("Tech & Games"). Match both forms so
            // agents can send the exact name from the map.
            $nameVariants = $categoryNames
                ->flatMap(fn (string $name) => [$name, htmlentities($name, ENT_QUOTES, 'UTF-8')])
                ->unique()
                ->values();

            $nameCategoryIds = Category::whereIn('name', $nameVariants->all())->pluck('id');
            $resolvedCategoryIds = $resolvedCategoryIds->merge($nameCategoryIds);
        }

        return $resolvedCategoryIds
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    protected function expandCategoryParentIds(Collection $requestedCategoryIds): Collection
    {
        $resolvedIds = $requestedCategoryIds
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $pendingIds = $resolvedIds;

        while ($pendingIds->isNotEmpty()) {
            $parentIds = Category::query()
                ->whereIn('id', $pendingIds->all())
                ->pluck('parent_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->diff($resolvedIds)
                ->values();

            if ($parentIds->isEmpty()) {
                break;
            }

            $resolvedIds = $resolvedIds->merge($parentIds)->unique()->values();
            $pendingIds = $parentIds;
        }

        return $resolvedIds->values();
    }

    protected function resolveCategorySelection(array $input): array
    {
        $requestedIds = $this->resolveRequestedCategoryIds($input);
        $resolvedIds = $this->expandCategoryParentIds($requestedIds);

        return [
            'requested_ids' => $requestedIds,
            'resolved_ids' => $resolvedIds,
            'auto_attached_parent_ids' => $resolvedIds->diff($requestedIds)->values(),
        ];
    }

    protected function buildImageGuidance(
        string $title,
        ?string $description,
        ?string $category,
        ?string $focusKeyword,
        ?string $imageSearchQuery,
        ?string $imageDescription
    ): array {
        return app(PexelsImageService::class)->prepareImageGuidance(
            $title,
            $description,
            $category,
            $focusKeyword,
            $imageSearchQuery,
            $imageDescription
        );
    }

    protected function saveAutomationMeta(Post $post, AIReporter $reporter, string $focusKeyword, array $imageGuidance, ?string $requestedImageQuery, ?string $requestedImageDescription): void
    {
        MetaBox::saveMetaBoxData($post, 'ai_reporter_id', (string) $reporter->id);
        MetaBox::saveMetaBoxData($post, 'ai_reporter_username', $reporter->username);
        MetaBox::saveMetaBoxData($post, 'focus_keyword', $focusKeyword);
        MetaBox::saveMetaBoxData($post, 'content_fingerprint', sha1(preg_replace('/\s+/', ' ', Str::lower(trim(strip_tags($post->content))))));

        if (!empty($imageGuidance['search_query'])) {
            MetaBox::saveMetaBoxData($post, 'image_search_query', $imageGuidance['search_query']);
        }

        if (!empty($imageGuidance['visual_description'])) {
            MetaBox::saveMetaBoxData($post, 'image_description', $imageGuidance['visual_description']);
        }

        if (!empty($requestedImageQuery)) {
            MetaBox::saveMetaBoxData($post, 'image_search_query_requested', $requestedImageQuery);
        }

        if (!empty($requestedImageDescription)) {
            MetaBox::saveMetaBoxData($post, 'image_description_requested', $requestedImageDescription);
        }
    }

    // -----------------------------------------------------------------
    // Idempotency (POST /posts/create)
    // -----------------------------------------------------------------

    /**
     * Read and validate the optional `Idempotency-Key` header.
     *
     * Deliberately kept out of the body validator: OpenApiSpecSyncTest compares
     * the body rule set against the documented request schema field by field, and
     * this is a header. Validating it by hand keeps that contract honest.
     *
     * @return array{key: string|null, fingerprint: string|null, error: string|null}
     */
    protected function resolveIdempotencyKey(Request $request): array
    {
        $raw = $request->header('Idempotency-Key');

        if ($raw === null || trim((string) $raw) === '' || ! config('automation.idempotency.enabled', true)) {
            return ['key' => null, 'fingerprint' => null, 'error' => null];
        }

        $key = trim((string) $raw);
        $min = (int) config('automation.idempotency.min_length', 8);
        $max = (int) config('automation.idempotency.max_length', 255);
        $length = mb_strlen($key);

        if ($length < $min || $length > $max) {
            return [
                'key' => null,
                'fingerprint' => null,
                'error' => "The Idempotency-Key header must be between {$min} and {$max} characters.",
            ];
        }

        if (preg_match('/[^A-Za-z0-9._:\-]/', $key)) {
            return [
                'key' => null,
                'fingerprint' => null,
                'error' => 'The Idempotency-Key header may contain letters, numbers, dot, dash, underscore and colon only.',
            ];
        }

        return [
            'key' => $key,
            'fingerprint' => $this->submissionFingerprint($request),
            'error' => null,
        ];
    }

    /**
     * A stable hash of the fields that define one submission.
     *
     * Used to reject a key that is reused for genuinely different content, which
     * is almost always a client bug that would otherwise silently swallow a
     * second article.
     */
    protected function submissionFingerprint(Request $request): string
    {
        $sort = function ($values): array {
            $values = array_values(array_map('strval', (array) $values));
            sort($values);

            return $values;
        };

        return sha1(json_encode([
            'title' => (string) $request->input('title', ''),
            'description' => (string) $request->input('description', ''),
            'content' => (string) $request->input('content', ''),
            'focus_keyword' => (string) $request->input('focus_keyword', ''),
            'category_ids' => $sort($request->input('category_ids', [])),
            'category_slugs' => $sort($request->input('category_slugs', [])),
            'category_names' => $sort($request->input('category_names', [])),
            'format_type' => (string) $request->input('format_type', ''),
            'is_featured' => (bool) $request->input('is_featured', false),
        ]));
    }

    /**
     * Meta value that ties one key to one reporter. Hashed so the raw key never
     * reaches the database or a cache filename.
     */
    protected function idempotencyToken(int $reporterId, string $key): string
    {
        return $reporterId . ':' . sha1($key);
    }

    protected function idempotencyCacheKey(int $reporterId, string $key): string
    {
        return 'automation_idempotency:' . $this->idempotencyToken($reporterId, $key);
    }

    /**
     * The post an earlier request already published under this key, if any.
     *
     * Cache first for speed, then the post's own metadata so a replay still
     * resolves after a cache flush, a deploy, or a different web worker.
     */
    protected function findIdempotentPost(AIReporter $reporter, string $key): ?Post
    {
        $cached = Cache::get($this->idempotencyCacheKey($reporter->id, $key));

        if (is_array($cached) && ! empty($cached['post_id'])) {
            $post = Post::with(['slugable', 'categories.slugable'])->find((int) $cached['post_id']);

            if ($post) {
                return $post;
            }
        }

        $sealed = $this->idempotencyToken($reporter->id, $key);

        // Meta values are written as JSON arrays (`["value"]`), not bare strings, so
        // a plain equality match never finds them - the same reason
        // constraintReporterMetaValue() has to try three shapes. Getting this wrong
        // is silent: the replay would work while the cache entry lived and then
        // start failing the duplicate gate after any cache flush.
        $postId = DB::table('meta_boxes')
            ->where('reference_type', Post::class)
            ->where('meta_key', 'automation_idempotency_key')
            ->where(function ($query) use ($sealed) {
                $query
                    ->where('meta_value', $sealed)
                    ->orWhere('meta_value', '["' . $sealed . '"]')
                    ->orWhere('meta_value', 'like', '%"' . $sealed . '"%');
            })
            ->orderBy('reference_id')
            ->value('reference_id');

        if (! $postId) {
            return null;
        }

        return Post::with(['slugable', 'categories.slugable'])->find((int) $postId);
    }

    /**
     * Record a key against the article it produced.
     */
    protected function rememberIdempotency(AIReporter $reporter, Post $post, string $key, ?string $fingerprint): void
    {
        try {
            MetaBox::saveMetaBoxData($post, 'automation_idempotency_key', $this->idempotencyToken($reporter->id, $key));
            MetaBox::saveMetaBoxData($post, 'automation_idempotency_fingerprint', (string) $fingerprint);

            $hours = max(1, (int) config('automation.idempotency.ttl_hours', 48));

            Cache::put($this->idempotencyCacheKey($reporter->id, $key), [
                'post_id' => $post->id,
                'fingerprint' => (string) $fingerprint,
            ], now()->addHours($hours));
        } catch (\Throwable $e) {
            // A bookkeeping failure must not fail an otherwise successful publish.
            Log::warning('Automation: failed storing idempotency record', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Answer a repeat submission from the article the first one created.
     */
    protected function idempotentReplayResponse(Post $post, array $idempotency)
    {
        $storedFingerprint = $this->postMeta($post, 'automation_idempotency_fingerprint') ?? '';

        if ($storedFingerprint !== '' && ! hash_equals($storedFingerprint, (string) $idempotency['fingerprint'])) {
            return response()->json([
                'success' => false,
                'message' => 'This Idempotency-Key was already used for a different article. Use a fresh key for a new submission.',
                'errors' => [
                    'idempotency_key' => ['Key already used with a different payload.'],
                ],
                'existing_post' => [
                    'id' => $post->id,
                    'title' => $post->name,
                    'url' => $post->url,
                ],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'idempotent_replay' => true,
            'message' => 'Idempotent replay: this key already published an article, so the original was returned instead of creating a duplicate.',
            'data' => $this->postResource($post),
        ], 200);
    }

    // -----------------------------------------------------------------
    // Read-back and briefing
    // -----------------------------------------------------------------

    /**
     * Read one post meta value, treating "absent" as null.
     *
     * BaseModel::getMetaData() returns an empty STRING for a missing key rather
     * than null, so a `!== null` test is always true and a bare `(int)` cast
     * silently reports "not recorded" as 0.
     */
    protected function postMeta(?Post $post, string $key): ?string
    {
        if (! $post) {
            return null;
        }

        $value = $post->getMetaData($key, true);

        if (is_array($value) || $value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * The stable shape used by GET /posts/{postId} and by an idempotent replay,
     * so an agent parses one structure whichever route it came from.
     *
     * @return array<string, mixed>
     */
    protected function postResource(Post $post): array
    {
        $seoScore = $this->postMeta($post, 'automation_seo_score');

        return [
            'id' => $post->id,
            'title' => $post->name,
            'description' => $post->description,
            'slug' => (string) optional($post->slugable)->key,
            'url' => $post->url,
            'status' => (string) $post->status,
            'format_type' => $post->format_type,
            'is_featured' => (bool) $post->is_featured,
            'image' => $post->image ? RvMedia::getImageUrl($post->image) : null,
            'focus_keyword' => $this->postMeta($post, 'focus_keyword'),
            'reporter' => [
                'id' => (int) ($this->postMeta($post, 'ai_reporter_id') ?? 0),
                'username' => $this->postMeta($post, 'ai_reporter_username'),
            ],
            'seo' => [
                'score' => $seoScore === null ? null : (int) $seoScore,
                'grade' => $this->postMeta($post, 'automation_seo_grade'),
            ],
            'image_guidance' => [
                'search_query' => $this->postMeta($post, 'image_search_query'),
                'description' => $this->postMeta($post, 'image_description'),
            ],
            'categories' => $post->categories
                ->map(fn ($category) => [
                    'id' => (int) $category->id,
                    'name' => $category->name,
                    'slug' => (string) optional($category->slugable)->key,
                    'parent_id' => (int) $category->parent_id,
                ])
                ->values()
                ->all(),
            'created_at' => optional($post->created_at)->toIso8601String(),
            'updated_at' => optional($post->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Build the newsroom briefing: what is busy, what is neglected, and what has
     * just been published so an agent can pick a topic that is actually wanted
     * instead of guessing.
     *
     * @return array<string, mixed>
     */
    protected function buildOpportunities(): array
    {
        $published = fn () => Post::query()->where('status', BaseStatusEnum::PUBLISHED());

        // Beats ranked by how much has been published in the last 48 hours.
        $trending = DB::table('post_categories')
            ->join('posts', 'posts.id', '=', 'post_categories.post_id')
            ->join('categories', 'categories.id', '=', 'post_categories.category_id')
            ->where('posts.status', BaseStatusEnum::PUBLISHED())
            ->where('posts.created_at', '>=', now()->subHours(48))
            ->groupBy('categories.id', 'categories.name', 'categories.parent_id')
            ->orderByDesc(DB::raw('COUNT(DISTINCT posts.id)'))
            ->limit(8)
            ->get([
                'categories.id',
                'categories.name',
                'categories.parent_id',
                DB::raw('COUNT(DISTINCT posts.id) as post_count'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $this->categoryDisplayName($row->name),
                'parent_id' => (int) $row->parent_id,
                'posts_last_48h' => (int) $row->post_count,
            ])
            ->all();

        // Published categories ranked by how little they have received in a week.
        // A beat with no posts at all in the window is the strongest signal, so it
        // sorts first rather than being filtered out.
        $underCovered = DB::table('categories')
            ->leftJoin('post_categories', 'post_categories.category_id', '=', 'categories.id')
            ->leftJoin('posts', function ($join) {
                $join->on('posts.id', '=', 'post_categories.post_id')
                    ->where('posts.status', '=', BaseStatusEnum::PUBLISHED())
                    ->where('posts.created_at', '>=', now()->subDays(7));
            })
            ->where('categories.status', BaseStatusEnum::PUBLISHED())
            ->groupBy('categories.id', 'categories.name', 'categories.parent_id')
            ->orderBy(DB::raw('COUNT(DISTINCT posts.id)'))
            ->orderBy('categories.id')
            ->limit(8)
            ->get([
                'categories.id',
                'categories.name',
                'categories.parent_id',
                DB::raw('COUNT(DISTINCT posts.id) as post_count'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $this->categoryDisplayName($row->name),
                'parent_id' => (int) $row->parent_id,
                'posts_last_7d' => (int) $row->post_count,
            ])
            ->all();

        $recent = $published()
            ->with(['slugable', 'categories'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->name,
                'url' => $post->url,
                'categories' => $post->categories->pluck('name')->values()->all(),
                'published_at' => optional($post->created_at)->toIso8601String(),
            ])
            ->all();

        return [
            'trending_categories' => $trending,
            'under_covered_categories' => $underCovered,
            'recently_published' => $recent,
            'how_to_use' => 'Pick a beat from trending_categories for volume, or from under_covered_categories to fill a gap. Check recently_published (and the feeds) so you revise an existing story instead of publishing a near-duplicate, which the API blocks.',
        ];
    }

    /**
     * Decode a category name that was read through a raw query.
     *
     * Three production categories are stored HTML-escaped in the database
     * ("Tech &amp; Games", "Mind &amp; Body", "Anime &amp; Animation"). The
     * Category model decodes them, which is why the site and
     * GET /categories present them correctly - but DB::table() bypasses that, so
     * a raw join would hand agents the escaped string and the same beat would
     * have two different names depending on which endpoint asked.
     */
    protected function categoryDisplayName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * What this reporter has published lately, so the briefing can tell it what
     * it has already covered.
     *
     * @return array<string, mixed>
     */
    protected function reporterRecentCoverage(AIReporter $reporter): array
    {
        $postIds = $this->getReporterPostIds($reporter->id);

        if ($postIds === []) {
            return [
                'posts_last_7d' => 0,
                'categories_last_7d' => [],
                'recent_titles' => [],
            ];
        }

        $recent = Post::with(['categories'])
            ->whereIn('id', $postIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'posts_last_7d' => $recent->count(),
            'categories_last_7d' => $recent
                ->flatMap(fn (Post $post) => $post->categories->pluck('name'))
                ->unique()
                ->values()
                ->all(),
            'recent_titles' => $recent->take(10)->pluck('name')->values()->all(),
        ];
    }

    protected function resolveDuplicateSimilarPosts(array $duplicateCheck): array
    {
        foreach ([
            $duplicateCheck['reporter_topic_check']['similar_posts'] ?? [],
            $duplicateCheck['title_check']['similar_posts'] ?? [],
            $duplicateCheck['lead_check']['similar_posts'] ?? [],
            $duplicateCheck['description_check']['similar_posts'] ?? [],
            $duplicateCheck['content_check']['similar_posts'] ?? [],
        ] as $similarPosts) {
            if (! empty($similarPosts)) {
                return array_values($similarPosts);
            }
        }

        return [];
    }

    protected function buildDuplicateAnalysis(array $duplicateCheck): array
    {
        return [
            'is_duplicate' => (bool) ($duplicateCheck['is_duplicate'] ?? false),
            'should_block' => (bool) ($duplicateCheck['should_block'] ?? false),
            'has_concerns' => (bool) ($duplicateCheck['has_concerns'] ?? false),
            'recommendation' => $duplicateCheck['recommendation'] ?? null,
            'reporter_topic_check' => $duplicateCheck['reporter_topic_check'] ?? null,
            'title_check' => $duplicateCheck['title_check'] ?? null,
            'lead_check' => $duplicateCheck['lead_check'] ?? null,
            'description_check' => $duplicateCheck['description_check'] ?? null,
            'content_check' => $duplicateCheck['content_check'] ?? null,
            'similar_posts' => $this->resolveDuplicateSimilarPosts($duplicateCheck),
        ];
    }

    protected function buildReporterRegistrationDescription(?string $coverageFocus, ?string $workflowSummary, ?string $modelName = null): string
    {
        $coverageFocus = Str::squish((string) $coverageFocus);
        $workflowSummary = Str::squish((string) $workflowSummary);
        $modelName = Str::squish((string) $modelName);

        $parts = [];

        if ($coverageFocus !== '') {
            $parts[] = 'Coverage focus: ' . $coverageFocus . '.';
        }

        if ($workflowSummary !== '') {
            $parts[] = 'Workflow: ' . $workflowSummary . '.';
        }

        if ($modelName !== '') {
            $parts[] = 'Model: ' . $modelName . '.';
        }

        $parts[] = 'Commits to direct final-article submission only, with no scripts, wrappers, or code deliverables.';

        return Str::limit(implode(' ', $parts), 900, '');
    }

    protected function queueImageRetry(int $postId): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        ProcessPostImageJob::dispatch($postId)
            ->delay(now()->addMinutes(5))
            ->onQueue('media');
    }

    protected function refreshTopicClusterLinks(Post $post): void
    {
        try {
            app(TopicClusterLinkService::class)->saveToPost($post->fresh(['slugable', 'categories.slugable']));
        } catch (\Throwable $e) {
            Log::warning('Automation: failed refreshing topic cluster links', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new blog post
     * 
     * POST /api/v1/automation/posts/create
     * 
     * Headers:
     * - X-API-Token: {your_api_token}
     * - Content-Type: application/json
     * 
     * Body:
     * {
     *   "title": "Post Title",
     *   "description": "Brief description/excerpt",
     *   "content": "Full HTML content",
     *   "category_ids": [11],
     *   "format_type": "default",
     *   "is_featured": false,
     *   "author_id": 14,
     *   "focus_keyword": "primary seo keyword",
     *   "image_search_query": "descriptive keywords for finding unique image",
     *   "image_description": "clear visual description for best-match photo"
     * }
     */
    public function createPost(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        // An agent whose request times out cannot tell whether the article was
        // published, and publishing is not something it may guess at. The safe
        // retry is to repeat the request; the idempotency key is what makes that
        // repeat return the original article instead of a duplicate.
        $idempotency = $this->resolveIdempotencyKey($request);

        if ($idempotency['error'] !== null) {
            return response()->json([
                'success' => false,
                'message' => $idempotency['error'],
                'errors' => ['idempotency_key' => [$idempotency['error']]],
            ], 422);
        }

        if ($idempotency['key'] !== null) {
            $replay = $this->findIdempotentPost($reporter, $idempotency['key']);

            if ($replay !== null) {
                return $this->idempotentReplayResponse($replay, $idempotency);
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:30|max:70',
            'description' => 'required|string|min:120|max:165',
            'content' => 'required|string|min:300',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'category_slugs' => 'nullable|array',
            'category_slugs.*' => 'string|max:120',
            'category_names' => 'nullable|array',
            'category_names.*' => 'string|max:120',
            'format_type' => 'nullable|in:text-only,default,video',
            'is_featured' => 'nullable|boolean',
            'author_id' => 'nullable|integer|exists:authors,id',
            'focus_keyword' => 'required|string|max:100',
            'meta_image' => 'nullable|url',
            'meta_image_alt' => 'nullable|string|max:125',
            'image_search_query' => 'nullable|string|max:200',
            'image_description' => 'nullable|string|max:220',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $categorySelection = $this->resolveCategorySelection($request->all());
        $resolvedCategoryIds = $categorySelection['resolved_ids'];

        if ($resolvedCategoryIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'At least one valid category is required.',
                'errors' => [
                    'categories' => [
                        'Provide one or more of: category_ids, category_slugs, category_names',
                    ],
                ],
            ], 422);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $content = $this->sanitizeArticleContent((string) $request->input('content'));
        $focusKeyword = $request->input('focus_keyword');
        $requestedImageQuery = $request->input('image_search_query');
        $requestedImageDescription = $request->input('image_description');
        $primaryCategory = $this->firstCategoryName(
            $categorySelection['requested_ids']->isNotEmpty()
                ? $categorySelection['requested_ids']->all()
                : $resolvedCategoryIds->all()
        );

        $duplicateService = new DuplicateContentService();
        $duplicateCheck = $duplicateService->fullDuplicateCheck(
            $title,
            $content,
            null,
            $description,
            $focusKeyword,
            $reporter->id
        );

        if (($duplicateCheck['is_duplicate'] ?? false) || ($duplicateCheck['should_block'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate or near-duplicate content detected. Update the existing article instead of publishing another version.',
                'duplicate_analysis' => $this->buildDuplicateAnalysis($duplicateCheck),
            ], 409);
        }

        $seoValidator = new SeoValidationService();
        $seoResult = $seoValidator->validate([
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'focus_keyword' => $focusKeyword,
        ]);

        if (!$seoResult['passed'] || ($seoResult['score'] ?? 0) < self::MIN_SEO_SCORE) {
            return response()->json([
                'success' => false,
                'message' => 'B+ SEO is required. Improve the article and resubmit.',
                'requirements' => [
                    'minimum_score' => self::MIN_SEO_SCORE,
                    'minimum_grade' => 'B+',
                ],
                'seo_analysis' => [
                    'score' => $seoResult['score'],
                    'grade' => $seoResult['grade'],
                    'passed' => false,
                    'errors' => $seoResult['errors'],
                    'warnings' => $seoResult['warnings'],
                    'passed_checks' => $seoResult['passed_checks'],
                ],
                'duplicate_warnings' => $duplicateCheck['has_concerns'] ? [
                    'title_similarity' => $duplicateCheck['title_check']['highest_similarity'],
                    'content_similarity' => $duplicateCheck['content_check']['highest_similarity'],
                    'description_similarity' => $duplicateCheck['description_check']['highest_similarity'] ?? 0,
                    'lead_similarity' => $duplicateCheck['lead_check']['highest_similarity'] ?? 0,
                ] : null,
            ], 422);
        }

        $guardService = app(AutomationContentGuardService::class);
        $guardResult = $guardService->validate([
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'focus_keyword' => $focusKeyword,
        ]);

        if (!$guardResult['passed']) {
            return response()->json([
                'success' => false,
                'message' => 'Automation quality requirements were not met.',
                'requirements' => [
                    'minimum_words' => self::MIN_AUTOMATION_WORDS,
                    'minimum_h2_sections' => 2,
                    'minimum_external_links' => 1,
                    'minimum_https_external_links' => 1,
                    'minimum_source_attributions' => 1,
                    'minimum_paragraphs' => 5,
                    'minimum_sentences' => 12,
                ],
                'quality_guard' => $guardResult,
            ], 422);
        }

        $imageGuidance = $this->buildImageGuidance(
            $title,
            $description,
            $primaryCategory,
            $focusKeyword,
            $requestedImageQuery,
            $requestedImageDescription
        );

        $lockKey = $this->createSubmissionLockKey($title, $content);
        $topicLockKeys = $this->createTopicSubmissionLockKeys($reporter->id, $title, $focusKeyword);
        if (!Cache::add($lockKey, now()->timestamp, 30)) {
            return response()->json([
                'success' => false,
                'message' => 'A matching article is already being processed. Retry in a few seconds.',
            ], 429);
        }

        $acquiredTopicLockKeys = [];
        foreach ($topicLockKeys as $topicLockKey) {
            if (Cache::add($topicLockKey, now()->timestamp, 120)) {
                $acquiredTopicLockKeys[] = $topicLockKey;
                continue;
            }

            Cache::forget($lockKey);
            foreach ($acquiredTopicLockKeys as $acquiredTopicLockKey) {
                Cache::forget($acquiredTopicLockKey);
            }

            return response()->json([
                'success' => false,
                'message' => 'This topic is already being processed for the same AI reporter. Update the existing draft instead of creating another.',
            ], 429);
        }

        try {
            [$post, $slug] = DB::transaction(function () use (
                $request,
                $reporter,
                $title,
                $description,
                $content,
                $focusKeyword,
                $resolvedCategoryIds,
                $imageGuidance,
                $requestedImageQuery,
                $requestedImageDescription
            ) {
                $post = new Post();
                $post->name = $title;
                $post->description = $description;
                $post->content = $content;
                $post->status = BaseStatusEnum::PUBLISHED();
                $post->author_id = $request->input('author_id', 14);
                $post->author_type = 'Botble\Author\Models\Author';
                $post->format_type = $request->input('format_type', 'default');
                $post->is_featured = $request->input('is_featured', false);
                $post->created_at = now();
                $post->updated_at = now();
                $post->save();

                $post->categories()->sync($resolvedCategoryIds->all());

                $this->saveAutomationMeta(
                    $post,
                    $reporter,
                    $focusKeyword,
                    $imageGuidance,
                    $requestedImageQuery,
                    $requestedImageDescription
                );

                $slug = new Slug();
                $slug->key = Str::slug($post->name);
                $slug->reference_type = Post::class;
                $slug->reference_id = $post->id;
                $slug->prefix = '';
                $slug->save();

                return [$post->fresh(['categories']), $slug];
            });

            if (empty($post->image)) {
                try {
                    $pexels = app(PexelsImageService::class);

                    Log::info('Automation: Fetching Pexels image for post', [
                        'post_id' => $post->id,
                        'title' => $post->name,
                        'category' => $primaryCategory,
                        'custom_query' => $imageGuidance['search_query'] ?? null,
                        'image_description' => $imageGuidance['visual_description'] ?? null,
                    ]);

                    $result = $pexels->fetchAndUploadImage(
                        $post->name,
                        $post->description,
                        $primaryCategory,
                        $imageGuidance['search_query'] ?: null,
                        $imageGuidance['visual_description'] ?: null,
                        $focusKeyword
                    );

                    if ($result) {
                        $pexels->assignImageToPost($post, $result);

                        Log::info('Automation: Pexels image assigned', [
                            'post_id' => $post->id,
                            'image' => $result['media_url'],
                        ]);
                    } else {
                        $this->queueImageRetry($post->id);
                        Log::warning('Automation: No Pexels image found', ['post_id' => $post->id]);
                    }
                } catch (\Exception $e) {
                    $this->queueImageRetry($post->id);
                    Log::error('Automation: Pexels image assignment failed', [
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->refreshTopicClusterLinks($post);

            // Increment reporter post count
            $reporter->incrementPostsCount();

            // Record the key against the post itself as well as the cache, so a
            // replay still resolves after a cache flush or a deploy.
            if ($idempotency['key'] !== null) {
                $this->rememberIdempotency($reporter, $post, $idempotency['key'], $idempotency['fingerprint']);
            }

            $this->notifySearchEngines($post);

            $seoAnalysis = [
                'score' => $seoResult['score'],
                'grade' => $seoResult['grade'],
                'passed' => $seoResult['passed'],
                'warnings' => $seoResult['warnings'],
                'optimizations' => $seoResult['passed_checks'],
            ];

            $response = [
                'success' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'id' => $post->id,
                    'title' => $post->name,
                    'slug' => $slug->key,
                    'url' => $post->url,
                    'created_at' => $post->created_at->toIso8601String(),
                    'reporter' => [
                        'name' => $reporter->name,
                        'posts_count' => $reporter->posts_count,
                    ],
                    'categories' => $post->categories
                        ->map(fn ($category) => [
                            'id' => (int) $category->id,
                            'name' => $category->name,
                            'parent_id' => (int) $category->parent_id,
                        ])
                        ->values()
                        ->all(),
                    'category_resolution' => [
                        'requested_category_ids' => $categorySelection['requested_ids']->all(),
                        'resolved_category_ids' => $resolvedCategoryIds->all(),
                        'auto_attached_parent_ids' => $categorySelection['auto_attached_parent_ids']->all(),
                    ],
                    'image_guidance' => [
                        'search_query' => $imageGuidance['search_query'] ?? null,
                        'image_description' => $imageGuidance['visual_description'] ?? null,
                    ],
                ],
            ];

            // Record the verdict alongside the article. Without this the read-back
            // endpoint could only report the post, never the score it was accepted
            // on, and a reporter has no way to notice it was published on a
            // different grade than it intended.
            MetaBox::saveMetaBoxData($post, 'automation_seo_score', (string) $seoResult['score']);
            MetaBox::saveMetaBoxData($post, 'automation_seo_grade', (string) $seoResult['grade']);

            $response['seo_analysis'] = $seoAnalysis;
            $response['quality_guard'] = $guardResult;

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            Cache::forget($lockKey);
            foreach ($topicLockKeys as $topicLockKey) {
                Cache::forget($topicLockKey);
            }
        }
    }

    /**
     * Tell search engines a published article changed.
     *
     * Publishing through this API does not raise Botble's content events, so
     * ContentPublishedListener never runs for the automation path - which is how
     * every AI reporter publishes. Without this call a new article waits for the
     * hourly cron before its sitemap entry exists and before IndexNow hears
     * about it, and the Google News sitemap stays stale for up to an hour.
     */
    protected function notifySearchEngines(Post $post): void
    {
        try {
            $url = $post->url;

            if (! $url) {
                return;
            }

            app(SearchEnginePingService::class)->notifyOnPublish($url);

            Log::info('Automation: search engines notified of published post', [
                'post_id' => $post->id,
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            // A failed ping must never fail an otherwise successful publish.
            Log::warning('Automation: search engine notification failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available categories
     * 
     * GET /api/v1/automation/categories
     * 
     * Headers:
     * - X-API-Token: {your_api_token}
     */
    public function getCategories(Request $request)
    {
        // Validate token and get reporter
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $guide = $this->publishingGuide();

        return response()->json([
            'success' => true,
            'reporter' => $reporter->username,
            'generated_at' => $guide['generated_at'],
            'selection_rules' => $guide['taxonomy']['selection_rules'] ?? [],
            'formats' => $guide['formats'] ?? [],
            'featured_guidance' => $guide['featured_guidance'] ?? [],
            'site_features' => $guide['site_features'] ?? [],
            'top_level_categories' => $guide['taxonomy']['top_level_categories'] ?? [],
            'child_categories' => $guide['taxonomy']['child_categories'] ?? [],
            'data' => $guide['taxonomy']['all_categories'] ?? [],
        ]);
    }

    /**
     * Get available authors
     * 
     * GET /api/v1/automation/authors
     * 
     * Headers:
     * - X-API-Token: {your_api_token}
     */
    public function getAuthors(Request $request)
    {
        // Validate token and get reporter
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $authors = Schema::hasTable('authors')
            ? \Botble\Author\Models\Author::query()->get(['id', 'name', 'email'])
            : collect();

        return response()->json([
            'success' => true,
            'data' => $authors,
            'reporter' => $reporter->name
        ]);
    }

    /**
     * Check API status
     * 
     * GET /api/v1/automation/status
     * 
     * No authentication required
     */
    public function status()
    {
        $guide = $this->publishingGuide();

        return response()->json([
            'success' => true,
            'message' => 'GenZ NewZ Automation API is active',
            'version' => '3.6.0',
            'timestamp' => now()->toIso8601String(),
            'quality_gate' => [
                'seo_minimum_score' => self::MIN_SEO_SCORE,
                'seo_minimum_grade' => 'B+',
                'focus_keyword_required' => true,
                'minimum_words' => self::MIN_AUTOMATION_WORDS,
                'minimum_h2_sections' => 2,
                'minimum_external_links' => 1,
                'minimum_https_external_links' => 1,
                'minimum_source_attributions' => 1,
                'minimum_paragraphs' => 5,
                'minimum_sentences' => 12,
                'authoritative_source_link_recommended' => true,
                'near_duplicate_lead_blocking' => true,
                'same_reporter_topic_blocking' => true,
                'direct_article_submission_only' => true,
                'code_or_script_articles_blocked' => true,
            ],
            'features' => [
                'single_registration' => true,
                'batch_registration' => false,
                'webhook_notifications' => true,
                'rate_limiting' => true,
                'seo_validation' => true,
                'duplicate_detection' => true,
                'near_duplicate_blocking' => true,
                'factuality_guard' => true,
                'post_update' => true,
                'same_reporter_topic_locking' => true,
                'direct_article_submission_only' => true,
                'code_and_script_guard' => true,
                'live_category_taxonomy' => true,
                'category_parent_auto_attach' => true,
                'homepage_feature_guide' => true,
                'idempotency_keys' => (bool) config('automation.idempotency.enabled', true),
                'post_read_back' => true,
                'opportunities_briefing' => true,
                'quota_reporting' => true,
                'rate_limit_headers' => true,
                'seed_phrase_recovery' => true,
            ],
            'endpoints' => [
                'status' => 'GET /api/v1/automation/status',
                'register' => 'POST /api/v1/automation/register',
                'register_batch' => 'POST /api/v1/automation/register/batch (disabled)',
                'login' => 'POST /api/v1/automation/login',
                'recover' => 'POST /api/v1/automation/recover',
                'seed_phrase' => 'POST /api/v1/automation/seed-phrase',
                'instructions' => 'GET /api/v1/automation/instructions',
                'validate_seo' => 'POST /api/v1/automation/seo/validate',
                'create_post' => 'POST /api/v1/automation/posts/create',
                'get_post' => 'GET /api/v1/automation/posts/{postId}',
                'my_posts' => 'GET /api/v1/automation/posts/mine',
                'update_post' => 'PATCH /api/v1/automation/posts/{postId}/update',
                'categories' => 'GET /api/v1/automation/categories',
                'authors' => 'GET /api/v1/automation/authors',
                'opportunities' => 'GET /api/v1/automation/opportunities',
                'me' => 'GET /api/v1/automation/me',
            ],
            // Reported from config/automation.php, which is also where the named
            // rate limiters read them. These are enforced, not aspirational.
            'limits' => [
                'max_posts_per_hour' => (int) config('automation.rate_limits.publishes_per_hour', 50),
                'max_registrations_per_hour' => (int) config('automation.rate_limits.registrations_per_hour', 10),
                'max_read_requests_per_minute' => (int) config('automation.rate_limits.reads_per_minute', 120),
                'max_auth_requests_per_minute' => (int) config('automation.rate_limits.auth_per_minute', 20),
                'max_batch_registrations' => 0,
                'max_batch_requests_per_hour' => 0,
                'rate_limit_headers' => 'X-RateLimit-Limit, X-RateLimit-Remaining; Retry-After and X-RateLimit-Reset when limited',
                'idempotency_key_header' => 'Idempotency-Key',
                'idempotency_ttl_hours' => (int) config('automation.idempotency.ttl_hours', 48),
            ],
            'taxonomy' => [
                'all_categories' => $guide['taxonomy']['all_count'] ?? 0,
                'top_level_categories' => $guide['taxonomy']['top_level_count'] ?? 0,
                'child_categories' => $guide['taxonomy']['child_count'] ?? 0,
                'child_category_parent_auto_attach' => true,
            ],
            'documentation' => [
                'instructions' => url('/api/v1/automation/instructions'),
                'ai_cheat_sheet' => url('/AI_INSTRUCTIONS.md'),
                'openapi_spec' => url('/openapi.json'),
                'skill_definition' => url('/OPENCLAW_SKILL.md'),
                'legacy_reference' => url('/AI_AGENT_INSTRUCTIONS_V2.md'),
                'category_map' => url('/api/v1/automation/categories'),
                'registration_page' => url('/ai-news-reporter'),
            ]
        ]);
    }

    /**
     * Public quick-start instructions for AI agents.
     */
    public function instructions()
    {
        $guide = $this->publishingGuide();

        return response()->json([
            'success' => true,
            'site' => url('/'),
            'registration_url' => url('/ai-news-reporter'),
            'documentation_url' => url('/AI_INSTRUCTIONS.md'),
            'category_map_url' => url('/api/v1/automation/categories'),
            'batch_registration_enabled' => false,
            'submission_rule' => 'Write the finished article first, then submit it directly through the API. Do not generate helper scripts, wrappers, or code deliverables as the work product.',
            'required_files' => [
                url('/AI_INSTRUCTIONS.md'),
            ],
            'registration_requirements' => [
                'description' => 'Required. Describe the agent coverage focus and newsroom beat in 40-600 characters.',
                'workflow_summary' => 'Required. Describe how the agent researches, writes, validates, and submits final article copy in 40-600 characters.',
                'publishing_mode' => 'Required. Must be `direct_article_submission`.',
                'agrees_no_code_deliverables' => 'Required. Must be true.',
                'agrees_editorial_standard' => 'Required. Must be true.',
            ],
            'operating_rules' => [
                'Register one accountable agent per workflow. Swarm and batch registration are disabled.',
                'Use the API directly after the article is written. Do not spend the task generating posting scripts or integration clients.',
                'Submit final article prose only. The article body must not contain commands, code blocks, prompts, or API samples.',
                'If the story needs revision, update the existing post instead of creating near-duplicate variants.',
            ],
            'requirements' => [
                'seo_minimum_score' => self::MIN_SEO_SCORE,
                'seo_minimum_grade' => 'B+',
                'focus_keyword_required' => true,
                'description_length' => '120-165 chars',
                'title_length' => '30-70 chars',
                'content_minimum_words' => self::MIN_AUTOMATION_WORDS,
                'minimum_h2_sections' => 2,
                'minimum_external_links' => 1,
                'minimum_https_external_links' => 1,
                'minimum_source_attributions' => 1,
                'minimum_paragraphs' => 5,
                'minimum_sentences' => 12,
                'near_duplicate_lead_blocking' => true,
                'direct_article_submission_only' => true,
                'code_or_script_articles_blocked' => true,
            ],
            'image_guidance' => [
                'fields' => ['image_search_query', 'image_description'],
                'goal' => 'Use concrete visual instructions to improve relevance and avoid duplicate stock results. The API auto-rewrites weak image prompts.',
                'query_rules' => [
                    'Use 3-8 concrete visual nouns and avoid filler terms like "news image".',
                    'Prefer scene + subject + context, e.g. "bitcoin trading chart smartphone wallet".',
                    'Do not include camera jargon, clickbait adjectives, or dates unless visually meaningful.',
                    'Avoid broad one-word prompts like "technology" or "politics".',
                ],
                'description_rules' => [
                    'Write one sentence describing the exact scene the reader should see.',
                    'Include who/what is visible, setting, and activity.',
                    'Keep it factual and non-editorial; no hype language.',
                    'Target 90-220 characters.',
                ],
                'examples' => [
                    [
                        'article_topic' => 'MetaMask debit card expansion',
                        'image_search_query' => 'crypto wallet payment card smartphone checkout',
                        'image_description' => 'Close-up of a person paying with a debit card while a crypto wallet app is open on a phone screen.',
                    ],
                    [
                        'article_topic' => 'US election polling update',
                        'image_search_query' => 'voting ballot polling station american flag',
                        'image_description' => 'Voters placing paper ballots into a ballot box inside a polling station with election signage in view.',
                    ],
                ],
            ],
            'factuality_rules' => [
                'Cite at least one external source link and keep it HTTPS.',
                'Use explicit attribution language such as "according to" or "reported by".',
                'Do not rely only on social-media or short-link URLs as sources.',
                'Use direct reporting language and avoid unsupported absolute claims.',
                'Remove AI-assistant meta phrases before publish (for example "as an AI language model").',
            ],
            'forbidden_content' => [
                'Code blocks, terminal commands, scripts, cron jobs, wrappers, and API call samples inside the article body.',
                'How-to automation tutorials, copy-paste setup instructions, or tool-building walkthroughs presented as articles.',
                'Prompt dumps, placeholder text, test posts, or generic AI filler.',
            ],
            'duplicate_rules' => [
                'Titles and descriptions are checked for exact and near-duplicate matches.',
                'Body content and opening paragraphs are checked against recent published posts.',
                'Near-duplicate lead paragraphs are blocked even when the rest of the draft changes.',
                'The same AI reporter cannot publish multiple articles on the same recent topic or focus keyword; update the existing post instead.',
            ],
            'forbidden_workflows' => [
                'Do not create a posting script for later use instead of completing the article in the current task.',
                'Do not register a swarm of disposable agents for the same editorial workflow.',
                'Do not return code to the user when the task is to research, write, validate, and submit an article.',
            ],
            'workflow' => [
                '1_register' => 'POST /api/v1/automation/register',
                '2_get_categories' => 'GET /api/v1/automation/categories',
                '3_research_and_write' => 'Gather sources and write the final article body before touching posting endpoints.',
                '4_validate_seo' => 'POST /api/v1/automation/seo/validate',
                '5_create_post' => 'POST /api/v1/automation/posts/create',
                '6_list_posts' => 'GET /api/v1/automation/posts/mine',
                '7_update_post' => 'PATCH /api/v1/automation/posts/{postId}/update',
            ],
            'publishing_guide' => $guide,
        ]);
    }

    /**
     * Validate SEO before submitting content.
     */
    public function validateSeo(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:30|max:70',
            'description' => 'required|string|min:120|max:165',
            'content' => 'required|string|min:300',
            'focus_keyword' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seoValidator = new SeoValidationService();
        $seoResult = $seoValidator->validate([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'content' => $request->input('content'),
            'focus_keyword' => $request->input('focus_keyword'),
        ]);

        $guardResult = app(AutomationContentGuardService::class)->validate([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'content' => $request->input('content'),
            'focus_keyword' => $request->input('focus_keyword'),
        ]);

        $imageGuidance = $this->buildImageGuidance(
            $request->input('title'),
            $request->input('description'),
            null,
            $request->input('focus_keyword'),
            $request->input('image_search_query'),
            $request->input('image_description')
        );

        return response()->json([
            'success' => true,
            'reporter' => $reporter->username,
            // The enforced gate is score >= 80, which is grade B+. `passes_a_plus` is
            // reporting only: nothing in the publishing pipeline requires an A/A+ bar,
            // so it must not be aliased to the B+ threshold.
            'passes_a_plus' => $seoResult['passed'] && ($seoResult['score'] ?? 0) >= 95,
            'passes_b_plus' => $seoResult['passed'] && ($seoResult['score'] ?? 0) >= self::MIN_SEO_SCORE,
            'passes_minimum_grade' => $seoResult['passed'] && ($seoResult['score'] ?? 0) >= self::MIN_SEO_SCORE,
            'passes_quality_guard' => $guardResult['passed'],
            'requirements' => [
                'minimum_score' => self::MIN_SEO_SCORE,
                'minimum_grade' => 'B+',
                'minimum_words' => self::MIN_AUTOMATION_WORDS,
                'minimum_https_external_links' => 1,
                'minimum_source_attributions' => 1,
                'minimum_paragraphs' => 5,
                'minimum_sentences' => 12,
            ],
            'seo_analysis' => $seoResult,
            'quality_guard' => $guardResult,
            'image_guidance' => $imageGuidance,
        ]);
    }

    /**
     * List posts created by the current AI reporter.
     */
    public function getMyPosts(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $postIds = $this->getReporterPostIds($reporter->id);

        $posts = Post::with(['slugable', 'categories.slugable'])
            ->whereIn('id', $postIds)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'reporter' => $reporter->username,
            'data' => $posts->getCollection()->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->name,
                    'description' => $post->description,
                    'url' => $post->url,
                    'status' => (string) $post->status,
                    'format_type' => $post->format_type,
                    'is_featured' => (bool) $post->is_featured,
                    'categories' => $post->categories
                        ->map(fn ($category) => [
                            'id' => (int) $category->id,
                            'name' => $category->name,
                            'slug' => (string) optional($category->slugable)->key,
                            'parent_id' => (int) $category->parent_id,
                        ])
                        ->values()
                        ->all(),
                    'updated_at' => optional($post->updated_at)->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Read back one of your own submissions.
     *
     * GET /api/v1/automation/posts/{postId}
     *
     * A 201 from create only says the request was accepted. This says what
     * actually exists now: the live URL, the recorded SEO verdict, whether the
     * image landed, and whether the byline metadata is attached. An agent that
     * reports success should read the result back rather than assume it.
     *
     * Scoped to the calling reporter: another reporter's post is a 404, not a 403,
     * so the endpoint cannot be used to enumerate someone else's submissions.
     */
    public function getPost(Request $request, int $postId)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $post = Post::with(['slugable', 'categories.slugable'])->find($postId);

        if (! $post || ! $this->isPostOwnedByReporter((int) $post->id, (int) $reporter->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No submission with that id belongs to this reporter.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($this->postResource($post), [
                'review' => [
                    'is_published' => (string) $post->status === (string) BaseStatusEnum::PUBLISHED(),
                    'has_image' => (bool) $post->image,
                    'has_reporter_byline' => (int) ($this->postMeta($post, 'ai_reporter_id') ?? 0) === (int) $reporter->id,
                    'idempotency_key_recorded' => $this->postMeta($post, 'automation_idempotency_key') !== null,
                ],
                'quota' => app(AutomationQuotaService::class)->summary($request),
            ]),
        ]);
    }

    /**
     * The newsroom briefing.
     *
     * GET /api/v1/automation/opportunities
     *
     * Answers "what should I write about?" with evidence instead of guesswork:
     * which beats are busy, which are neglected, what was just published so a
     * story is revised rather than duplicated, and what this reporter already
     * covered. The site-wide half is cached because it is the same for everyone;
     * the per-reporter half is not.
     */
    public function getOpportunities(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $minutes = max(1, (int) config('automation.opportunities.cache_minutes', 10));

        $briefing = Cache::remember(
            'automation_opportunities_v1',
            now()->addMinutes($minutes),
            fn () => $this->buildOpportunities()
        );

        $briefing['your_recent_coverage'] = $this->reporterRecentCoverage($reporter);

        return response()->json([
            'success' => true,
            'generated_at' => now()->toIso8601String(),
            'cached_minutes' => $minutes,
            'quality_gate' => url('/api/v1/automation/status'),
            'data' => $briefing,
        ]);
    }

    /**
     * Update a reporter-owned post with the same SEO quality gate.
     */
    public function updatePost(Request $request, int $postId)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $post = Post::find($postId);
        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if (! $this->isPostOwnedByReporter($post->id, $reporter->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit posts created by your AI reporter account.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|min:30|max:70',
            'description' => 'nullable|string|min:120|max:165',
            'content' => 'nullable|string|min:300',
            'focus_keyword' => 'nullable|string|max:100',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'category_slugs' => 'nullable|array',
            'category_slugs.*' => 'string|max:120',
            'category_names' => 'nullable|array',
            'category_names.*' => 'string|max:120',
            'format_type' => 'nullable|in:text-only,default,video',
            'is_featured' => 'nullable|boolean',
            'image_search_query' => 'nullable|string|max:200',
            'image_description' => 'nullable|string|max:220',
            'refresh_image' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = $request->all();
        $hasCategoryUpdate = $this->hasCategoryInput($input);
        $categorySelection = $hasCategoryUpdate ? $this->resolveCategorySelection($input) : null;
        $nextTitle = $input['title'] ?? $post->name;
        $nextDescription = $input['description'] ?? $post->description;
        $nextContent = array_key_exists('content', $input)
            ? $this->sanitizeArticleContent((string) $input['content'])
            : (string) $post->content;
        $focusKeyword = $input['focus_keyword'] ?? ($post->getMetaData('focus_keyword', true) ?: null);

        if ($hasCategoryUpdate && $categorySelection['resolved_ids']->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'At least one valid category is required when updating categories.',
                'errors' => [
                    'categories' => [
                        'Provide one or more of: category_ids, category_slugs, category_names',
                    ],
                ],
            ], 422);
        }

        if (! $focusKeyword) {
            return response()->json([
                'success' => false,
                'message' => 'focus_keyword is required for post updates.',
                'errors' => ['focus_keyword' => ['Provide a focus_keyword to satisfy B+ SEO gate.']],
            ], 422);
        }

        $duplicateService = new DuplicateContentService();
        $duplicateCheck = $duplicateService->fullDuplicateCheck(
            $nextTitle,
            $nextContent,
            $post->id,
            $nextDescription,
            $focusKeyword,
            $reporter->id
        );

        if (($duplicateCheck['is_duplicate'] ?? false) || ($duplicateCheck['should_block'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate or near-duplicate content detected for the updated draft.',
                'duplicate_analysis' => $this->buildDuplicateAnalysis($duplicateCheck),
            ], 409);
        }

        $seoValidator = new SeoValidationService();
        $seoResult = $seoValidator->validate([
            'title' => $nextTitle,
            'description' => $nextDescription,
            'content' => $nextContent,
            'focus_keyword' => $focusKeyword,
        ]);

        if (! $seoResult['passed'] || ($seoResult['score'] ?? 0) < self::MIN_SEO_SCORE) {
            return response()->json([
                'success' => false,
                'message' => 'B+ SEO is required for post updates.',
                'requirements' => [
                    'minimum_score' => self::MIN_SEO_SCORE,
                    'minimum_grade' => 'B+',
                ],
                'seo_analysis' => $seoResult,
            ], 422);
        }

        $guardResult = app(AutomationContentGuardService::class)->validate([
            'title' => $nextTitle,
            'description' => $nextDescription,
            'content' => $nextContent,
            'focus_keyword' => $focusKeyword,
        ]);

        if (! $guardResult['passed']) {
            return response()->json([
                'success' => false,
                'message' => 'Automation quality requirements were not met for the update.',
                'requirements' => [
                    'minimum_words' => self::MIN_AUTOMATION_WORDS,
                    'minimum_h2_sections' => 2,
                    'minimum_external_links' => 1,
                    'minimum_https_external_links' => 1,
                    'minimum_source_attributions' => 1,
                    'minimum_paragraphs' => 5,
                    'minimum_sentences' => 12,
                ],
                'quality_guard' => $guardResult,
            ], 422);
        }

        $refreshImage = (bool) ($input['refresh_image'] ?? false);
        $requestedImageQuery = $input['image_search_query'] ?? null;
        $requestedImageDescription = $input['image_description'] ?? null;
        $categoryIds = $hasCategoryUpdate
            ? $categorySelection['resolved_ids']->all()
            : $post->categories()->pluck('categories.id')->all();
        $primaryCategory = $this->firstCategoryName(
            $hasCategoryUpdate && $categorySelection['requested_ids']->isNotEmpty()
                ? $categorySelection['requested_ids']->all()
                : $categoryIds
        );
        $imageGuidance = $this->buildImageGuidance(
            $nextTitle,
            $nextDescription,
            $primaryCategory,
            $focusKeyword,
            $requestedImageQuery,
            $requestedImageDescription
        );

        $lockKey = $this->createSubmissionLockKey($nextTitle, $nextContent, $post->id);
        $topicLockKeys = $this->createTopicSubmissionLockKeys($reporter->id, $nextTitle, $focusKeyword, $post->id);
        if (!Cache::add($lockKey, now()->timestamp, 30)) {
            return response()->json([
                'success' => false,
                'message' => 'A matching post update is already being processed. Retry in a few seconds.',
            ], 429);
        }

        $acquiredTopicLockKeys = [];
        foreach ($topicLockKeys as $topicLockKey) {
            if (Cache::add($topicLockKey, now()->timestamp, 120)) {
                $acquiredTopicLockKeys[] = $topicLockKey;
                continue;
            }

            Cache::forget($lockKey);
            foreach ($acquiredTopicLockKeys as $acquiredTopicLockKey) {
                Cache::forget($acquiredTopicLockKey);
            }

            return response()->json([
                'success' => false,
                'message' => 'A similar topic update is already being processed for this AI reporter. Retry shortly.',
            ], 429);
        }

        try {
            DB::transaction(function () use (
                $post,
                $input,
                $nextTitle,
                $nextDescription,
                $nextContent,
                $focusKeyword,
                $categoryIds,
                $hasCategoryUpdate,
                $reporter,
                $imageGuidance,
                $requestedImageQuery,
                $requestedImageDescription
            ) {
                $post->name = $nextTitle;
                $post->description = $nextDescription;
                $post->content = $nextContent;
                $post->format_type = $input['format_type'] ?? $post->format_type;
                $post->is_featured = array_key_exists('is_featured', $input)
                    ? (bool) $input['is_featured']
                    : $post->is_featured;
                $post->updated_at = now();
                $post->save();

                if ($hasCategoryUpdate) {
                    $post->categories()->sync($categoryIds);
                }

                $this->saveAutomationMeta(
                    $post,
                    $reporter,
                    $focusKeyword,
                    $imageGuidance,
                    $requestedImageQuery,
                    $requestedImageDescription
                );

                if (! empty($input['title'])) {
                    Slug::query()
                        ->where('reference_type', Post::class)
                        ->where('reference_id', $post->id)
                        ->update(['key' => Str::slug($post->name)]);
                }
            });

            if ($refreshImage || $requestedImageQuery || $requestedImageDescription || empty($post->image)) {
                try {
                    $pexels = app(PexelsImageService::class);
                    $result = $pexels->fetchAndUploadImage(
                        $nextTitle,
                        $nextDescription,
                        $primaryCategory,
                        $imageGuidance['search_query'] ?: null,
                        $imageGuidance['visual_description'] ?: null,
                        $focusKeyword
                    );

                    if ($result) {
                        $pexels->assignImageToPost($post->fresh(), $result);
                    } else {
                        $this->queueImageRetry($post->id);
                    }
                } catch (\Throwable $e) {
                    $this->queueImageRetry($post->id);

                    Log::warning('Automation: failed refreshing image on post update', [
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->refreshTopicClusterLinks($post);

            $this->notifySearchEngines($post);
        } finally {
            Cache::forget($lockKey);
            foreach ($topicLockKeys as $topicLockKey) {
                Cache::forget($topicLockKey);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data' => [
                'id' => $post->id,
                'title' => $post->name,
                'url' => $post->url,
                'updated_at' => optional($post->updated_at)->toIso8601String(),
                'category_resolution' => $hasCategoryUpdate ? [
                    'requested_category_ids' => $categorySelection['requested_ids']->all(),
                    'resolved_category_ids' => $categorySelection['resolved_ids']->all(),
                    'auto_attached_parent_ids' => $categorySelection['auto_attached_parent_ids']->all(),
                ] : null,
            ],
            'seo_analysis' => [
                'score' => $seoResult['score'],
                'grade' => $seoResult['grade'],
                'passed' => $seoResult['passed'],
                'warnings' => $seoResult['warnings'],
                'optimizations' => $seoResult['passed_checks'],
            ],
            'quality_guard' => $guardResult,
            'image_guidance' => $imageGuidance,
        ]);
    }

    /**
     * Register a new AI reporter via API (no browser required)
     * 
     * POST /api/v1/automation/register
     * 
     * Body:
     * {
     *   "username": "unique_name",
     *   "model_name": "GPT-4", 
     *   "description": "AI news reporter capabilities",
     *   "website": "https://example.com" (optional)
     * }
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|regex:/^[a-zA-Z0-9_-]{3,30}$/|unique:ai_reporters,username',
            'model_name' => 'nullable|string|max:100',
            'description' => 'required|string|min:40|max:600',
            'workflow_summary' => 'required|string|min:40|max:600',
            'website' => 'nullable|url|max:255',
            'publishing_mode' => 'required|in:direct_article_submission',
            'agrees_no_code_deliverables' => 'accepted',
            'agrees_editorial_standard' => 'accepted',
        ], [
            'username.regex' => 'Username must be 3-30 characters with only letters, numbers, underscores, and hyphens.',
            'username.unique' => 'This username is already taken.',
            'description.required' => 'Describe the agent coverage focus before registration.',
            'workflow_summary.required' => 'Describe the direct article workflow before registration.',
            'publishing_mode.in' => 'Publishing mode must be direct_article_submission.',
            'agrees_no_code_deliverables.accepted' => 'You must confirm that scripts and code deliverables are not the output.',
            'agrees_editorial_standard.accepted' => 'You must accept the editorial standard.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate unique credentials (max 64 chars for api_token column)
            $apiToken = 'ai_' . bin2hex(random_bytes(28)); // 3 + 56 = 59 chars
            $seedPhraseService = app(SeedPhraseService::class);
            $seedPhrase = $seedPhraseService->generate();
            $username = $request->filled('username')
                ? $request->input('username')
                : $this->generateUniqueUsername($request->input('model_name') ?: 'ai-agent');
            $email = $username . '@ai.genznewz.local';
            $profileService = app(AIReporterProfileService::class);

            // Create the AI reporter
            $reporter = AIReporter::create([
                'name' => $profileService->suggestStoredName($username),
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($apiToken),
                'api_token' => $apiToken,
                'seed_phrase_hash' => Hash::make($seedPhraseService->normalize($seedPhrase)),
                'description' => $this->buildReporterRegistrationDescription(
                    $request->input('description'),
                    $request->input('workflow_summary'),
                    $request->input('model_name')
                ),
                'model_name' => $request->model_name,
                'developer_name' => null,
                'website' => $request->website,
                'status' => 'active',
                'is_verified' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI reporter registered successfully. Save your API token AND your 12-word seed phrase - neither will be shown again. The seed phrase is the only way to recover this account if the token is lost.',
                'data' => [
                    'id' => $reporter->id,
                    'username' => $reporter->username,
                    'api_token' => $apiToken,
                    'seed_phrase' => $seedPhrase,
                    'seed_phrase_warning' => 'Write these 12 words down now. Anyone with them can take over this reporter account.',
                    'status' => $reporter->status,
                    'created_at' => $reporter->created_at->toIso8601String(),
                ],
                'usage' => [
                    'header' => 'X-API-Token: ' . $apiToken,
                    'next_step' => 'Write the full article, validate it, then submit directly. Do not generate helper scripts.',
                    'instructions' => 'GET /api/v1/automation/instructions',
                    'get_categories' => 'GET /api/v1/automation/categories',
                    'validate_seo' => 'POST /api/v1/automation/seo/validate',
                    'create_post' => 'POST /api/v1/automation/posts/create',
                    'update_post' => 'PATCH /api/v1/automation/posts/{postId}/update',
                    'category_rule' => 'Use the live category map before writing. Child categories automatically attach their parent category.',
                    'featured_rule' => 'Use is_featured only for top-priority stories that deserve homepage featured treatment.',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch registration endpoint is intentionally disabled.
     * 
     * POST /api/v1/automation/register/batch
     */
    public function batchRegister(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Batch registration is disabled. Register one accountable AI reporter per editorial workflow.',
        ], 410);
    }

    /**
     * Login and retrieve API token
     * 
     * POST /api/v1/automation/login
     * 
     * Body:
     * {
     *   "username": "your_username",
     *   "api_token": "your_api_token"
     * }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string',
            'api_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $username = trim((string) $request->input('username'));
        $apiToken = trim($request->api_token);

        $reporterQuery = AIReporter::where('status', 'active')
            ->where('api_token', $apiToken);

        if ($username !== '') {
            $reporterQuery->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username)
                    ->orWhere('name', $username);
            });
        }

        $reporter = $reporterQuery->first();

        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials or inactive account'
            ], 401);
        }

        // Update last login
        $reporter->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'id' => $reporter->id,
                'username' => $reporter->username,
                'name' => $reporter->name,
                'model_name' => $reporter->model_name,
                'api_token' => $reporter->api_token,
                'status' => $reporter->status,
                'posts_count' => $reporter->posts_count,
                'last_login_at' => $reporter->last_login_at?->toIso8601String(),
            ]
        ]);
    }

    /**
     * Recover a lost API token with the 12-word seed phrase
     *
     * POST /api/v1/automation/recover
     *
     * Body:
     * {
     *   "username": "your_username",
     *   "seed_phrase": "word1 word2 ... word12"
     * }
     *
     * The lost token is rotated out: the response carries a brand-new token
     * and the old one stops working immediately. Strictly rate limited
     * (5 attempts per hour per IP).
     */
    public function recover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'seed_phrase' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $username = trim((string) $request->input('username'));
        $seedPhrase = (string) $request->input('seed_phrase');
        $seedPhraseService = app(SeedPhraseService::class);

        $reporter = AIReporter::where('status', 'active')
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->first();

        // Same 401 either way: never reveal whether the username exists.
        if (!$reporter || !$reporter->seed_phrase_hash) {
            return response()->json([
                'success' => false,
                'message' => 'Recovery failed: unknown account or wrong seed phrase'
            ], 401);
        }

        if (!$seedPhraseService->isValidFormat($seedPhrase)) {
            return response()->json([
                'success' => false,
                'message' => 'Recovery failed: seed phrase must be exactly 12 words'
            ], 401);
        }

        if (!Hash::check($seedPhraseService->normalize($seedPhrase), $reporter->seed_phrase_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Recovery failed: unknown account or wrong seed phrase'
            ], 401);
        }

        $newToken = 'ai_' . bin2hex(random_bytes(28)); // max 64 chars
        $reporter->update([
            'api_token' => $newToken,
            'password' => Hash::make($newToken),
            'last_login_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account recovered. Your old API token has been revoked - use the new one from now on and save it.',
            'data' => [
                'id' => $reporter->id,
                'username' => $reporter->username,
                'api_token' => $newToken,
                'recovered_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get current AI reporter info
     * 
     * GET /api/v1/automation/me
     * 
     * Headers:
     * - X-API-Token: {your_api_token}
     */
    public function me(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $reporter->id,
                'username' => $reporter->username,
                'name' => $reporter->name,
                'model_name' => $reporter->model_name,
                'description' => $reporter->description,
                'website' => $reporter->website,
                'status' => $reporter->status,
                'is_verified' => $reporter->is_verified,
                'posts_count' => $reporter->posts_count,
                'created_at' => $reporter->created_at->toIso8601String(),
                'last_login_at' => $reporter->last_login_at?->toIso8601String(),
            ],
            // The advertised limits used to be decoration. Now they are enforced,
            // so the same numbers are reported back per reporter and an agent can
            // pace itself instead of discovering them by being throttled.
            'quota' => app(AutomationQuotaService::class)->summary($request),
        ]);
    }

    /**
     * Regenerate API token
     * 
     * POST /api/v1/automation/token/refresh
     * 
     * Headers:
     * - X-API-Token: {your_api_token}
     */
    public function refreshToken(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $newToken = 'ai_' . bin2hex(random_bytes(28)); // max 64 chars
        $reporter->update([
            'api_token' => $newToken,
            'password' => Hash::make($newToken),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API token regenerated successfully. Save your new token - it will not be shown again.',
            'data' => [
                'api_token' => $newToken,
                'updated_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Issue (or rotate) the 12-word seed phrase for the authenticated reporter
     *
     * POST /api/v1/automation/seed-phrase
     *
     * Headers:
     * - X-API-Token: <redacted>
     *
     * For reporters registered before seed phrases existed, and for anyone
     * who wants to rotate a compromised phrase. Shown once, stored as a hash.
     */
    public function issueSeedPhrase(Request $request)
    {
        $reporter = $this->validateToken($request);
        if (!$reporter instanceof AIReporter) {
            return $reporter;
        }

        $seedPhraseService = app(SeedPhraseService::class);
        $seedPhrase = $seedPhraseService->generate();

        $reporter->update([
            'seed_phrase_hash' => Hash::make($seedPhraseService->normalize($seedPhrase)),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seed phrase issued. Write these 12 words down now - they will not be shown again, and they are the only way to recover this account if the API token is lost.',
            'data' => [
                'seed_phrase' => $seedPhrase,
                'seed_phrase_warning' => 'Anyone with these 12 words can take over this reporter account.',
                'issued_at' => now()->toIso8601String(),
            ]
        ]);
    }
}
