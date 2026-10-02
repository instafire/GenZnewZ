<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HtmlToMarkdown;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Public read API for AI agents and other machine consumers.
 *
 * No authentication required. Published posts only. Complements the
 * Botble blog plugin API (/api/v1/posts, /api/v1/search, /api/v1/categories)
 * with agent-shaped payloads: ISO dates, word counts, plain-text content,
 * author names, and per-article Markdown URLs.
 */
class ArticleReaderController extends Controller
{
    protected const DEFAULT_PER_PAGE = 20;
    protected const MAX_PER_PAGE = 50;

    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery();

        if ($request->filled('topic')) {
            $category = $this->categoryBySlug((string) $request->input('topic'));
            if (! $category) {
                return response()->json([
                    'error' => 'Unknown topic slug. See GET /api/v1/topics for the live list.',
                ], 404);
            }
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $category->getKey()));
        }

        foreach (['from' => '>=', 'to' => '<='] as $param => $operator) {
            if ($request->filled($param)) {
                try {
                    $date = Carbon::parse((string) $request->input($param));
                } catch (\Throwable) {
                    return response()->json(['error' => "Invalid date for '{$param}'. Use YYYY-MM-DD."], 422);
                }
                $query->where('created_at', $operator, $param === 'from' ? $date->startOfDay() : $date->endOfDay());
            }
        }

        $paginator = $query->paginate($this->perPage($request));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Post $post) => $this->articleArray($post, false))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->findBySlug($slug);
        if (! $post) {
            return response()->json(['error' => 'Article not found.'], 404);
        }

        return response()->json(['data' => $this->articleArray($post, true)]);
    }

    public function topics(): JsonResponse
    {
        $categories = Category::query()
            ->where('status', 'published')
            ->with('slugable')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->getKey(),
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => $category->url,
                'articles_url' => url('/api/v1/articles?topic=' . $category->slug),
            ])
            ->all();

        return response()->json(['data' => $categories]);
    }

    /**
     * Clean Markdown rendering of one article for AI consumers.
     * GET /{slug}.md — registered in routes/web.php before the catch-all
     * post route (slugs exclude dots, so there is no conflict).
     */
    public function markdown(string $slug): Response
    {
        $post = $this->findBySlug($slug);
        if (! $post) {
            return response('Article not found.', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $article = $this->articleArray($post, true);
        $body = HtmlToMarkdown::convert((string) $post->content);

        $lines = [
            '# ' . $article['title'],
            '',
            '> ' . $article['description'],
            '',
            '- URL: ' . $article['url'],
            '- Published: ' . $article['published_at'],
            '- Topics: ' . implode(', ', array_column($article['categories'], 'name')),
            '- Author: ' . ($article['author'] ?? 'GenZ NewZ'),
        ];
        if ($article['image']) {
            $lines[] = '- Image: ' . $article['image'];
        }
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = $body;

        return response(
            implode("\n", $lines) . "\n",
            200,
            ['Content-Type' => 'text/markdown; charset=utf-8']
        );
    }

    protected function baseQuery()
    {
        return Post::query()
            ->where('status', 'published')
            ->with(['slugable', 'categories', 'tags', 'author'])
            ->orderByDesc('created_at');
    }

    protected function findBySlug(string $slug): ?Post
    {
        return Post::query()
            ->where('status', 'published')
            ->whereHas('slugable', function ($query) use ($slug) {
                $query->where('key', $slug)->where('reference_type', Post::class);
            })
            ->with(['slugable', 'categories', 'tags', 'author'])
            ->first();
    }

    protected function categoryBySlug(string $slug): ?Category
    {
        return Category::query()
            ->where('status', 'published')
            ->whereHas('slugable', function ($query) use ($slug) {
                $query->where('key', $slug)->where('reference_type', Category::class);
            })
            ->first();
    }

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage ?: self::DEFAULT_PER_PAGE));
    }

    protected function articleArray(Post $post, bool $full): array
    {
        $slug = $post->slugable?->key;
        $content = (string) $post->content;

        $article = [
            'id' => $post->getKey(),
            'title' => (string) $post->name,
            'slug' => $slug,
            'description' => (string) $post->description,
            'url' => (string) $post->url,
            'markdown_url' => $slug ? url('/' . $slug . '.md') : null,
            'image' => $this->imageUrl($post),
            'published_at' => ($post->created_at ?? now())->toIso8601ZuluString(),
            'updated_at' => ($post->updated_at ?? $post->created_at ?? now())->toIso8601ZuluString(),
            'word_count' => str_word_count(HtmlToMarkdown::toText($content)),
            'categories' => $post->categories->map(fn (Category $c) => [
                'id' => $c->getKey(),
                'name' => $c->name,
                'slug' => $c->slug,
            ])->all(),
            'tags' => $post->tags->pluck('name')->all(),
            'author' => $post->author->name ?? null,
        ];

        if ($full) {
            $article['content_text'] = HtmlToMarkdown::toText($content);
            $article['content_html'] = $content;
        }

        return $article;
    }

    protected function imageUrl(Post $post): ?string
    {
        if (! $post->image) {
            return null;
        }

        $url = RvMedia::getImageUrl($post->image, 'large', false, RvMedia::getDefaultImage());

        if (! $url) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
