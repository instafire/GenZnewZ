<?php

namespace App\Services;

use Botble\Blog\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TopicClusterLinkService
{
    protected const START_MARKER = '<!-- genznewz:topic-cluster-links:start -->';
    protected const END_MARKER = '<!-- genznewz:topic-cluster-links:end -->';

    public function applyToPost(Post $post, ?Collection $pool = null): array
    {
        $post->loadMissing(['slugable', 'categories.slugable']);

        $originalContent = trim((string) $post->content);
        $cleanContent = $this->stripExistingClusterBlock((string) $post->content);
        $beforeInternalLinks = $this->countInternalLinks($cleanContent);

        $relatedPosts = $this->selectRelatedPosts($post, $pool);
        $block = $this->buildClusterBlock($post, $relatedPosts);

        if ($block === null) {
            return [
                'changed' => false,
                'before_internal_links' => $beforeInternalLinks,
                'after_internal_links' => $beforeInternalLinks,
                'related_post_ids' => [],
            ];
        }

        $post->content = trim($cleanContent) . "\n\n" . $block;
        $afterInternalLinks = $this->countInternalLinks((string) $post->content);
        $changed = trim((string) $post->content) !== $originalContent;

        return [
            'changed' => $changed,
            'before_internal_links' => $beforeInternalLinks,
            'after_internal_links' => $afterInternalLinks,
            'related_post_ids' => $relatedPosts->pluck('id')->all(),
        ];
    }

    public function saveToPost(Post $post, ?Collection $pool = null): array
    {
        $result = $this->applyToPost($post, $pool);

        if ($result['changed']) {
            $post->saveQuietly();
        }

        return $result;
    }

    protected function selectRelatedPosts(Post $post, ?Collection $pool = null, int $limit = 3): Collection
    {
        $post->loadMissing(['categories', 'slugable']);
        $categoryIds = $post->categories->pluck('id')->filter()->values();

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        $candidates = $pool
            ? $pool
                ->filter(fn (Post $candidate) => (int) $candidate->id !== (int) $post->id)
                ->filter(fn (Post $candidate) => (string) $candidate->status === 'published')
                ->filter(fn (Post $candidate) => $candidate->slugable)
                ->filter(fn (Post $candidate) => $candidate->categories->pluck('id')->intersect($categoryIds)->isNotEmpty())
                ->sortByDesc('id')
                ->values()
            : Post::with(['slugable', 'categories'])
                ->where('status', 'published')
                ->where('id', '!=', $post->id)
                ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds->all()))
                ->orderByDesc('id')
                ->limit(40)
                ->get();

        $primaryCategoryId = $post->categories->first()?->id;

        $primaryMatches = $primaryCategoryId
            ? $candidates->filter(fn (Post $candidate) => $candidate->categories->pluck('id')->contains($primaryCategoryId))
            : collect();

        $secondaryMatches = $candidates->reject(fn (Post $candidate) => $primaryCategoryId && $candidate->categories->pluck('id')->contains($primaryCategoryId));

        $selected = $primaryMatches
            ->merge($secondaryMatches)
            ->unique('id')
            ->take($limit)
            ->values();

        if ($selected->count() < $limit) {
            $fallback = Post::with(['slugable'])
                ->where('status', 'published')
                ->where('id', '!=', $post->id)
                ->whereNotIn('id', $selected->pluck('id')->all())
                ->orderByDesc('id')
                ->limit($limit - $selected->count())
                ->get();

            $selected = $selected->merge($fallback)->unique('id')->take($limit)->values();
        }

        return $selected;
    }

    protected function buildClusterBlock(Post $post, Collection $relatedPosts): ?string
    {
        $links = [];
        $primaryCategory = $post->categories->first();
        $categoryUrl = $primaryCategory ? $this->modelUrl($primaryCategory) : null;
        $links[] = '<a href="' . e(url('/')) . '">the latest GenZ NewZ headlines</a>';

        if ($categoryUrl) {
            $links[] = '<a href="' . e($categoryUrl) . '">more ' . e(Str::lower((string) $primaryCategory->name)) . ' coverage</a>';
        }

        foreach ($relatedPosts as $relatedPost) {
            $url = $this->modelUrl($relatedPost);
            if (! $url) {
                continue;
            }

            $links[] = '<a href="' . e($url) . '">' . e((string) $relatedPost->name) . '</a>';
        }

        $links = array_values(array_unique($links));

        if (count($links) < 3) {
            return null;
        }

        $relatedCopy = $this->joinLinks($links);

        return implode("\n", [
            self::START_MARKER,
            '<div class="topic-cluster-links">',
            '<p><strong>Related coverage:</strong> Continue with ' . $relatedCopy . '.</p>',
            '</div>',
            self::END_MARKER,
        ]);
    }

    protected function stripExistingClusterBlock(string $content): string
    {
        $pattern = '/' . preg_quote(self::START_MARKER, '/') . '.*?' . preg_quote(self::END_MARKER, '/') . '/s';

        return trim((string) preg_replace($pattern, '', $content));
    }

    protected function countInternalLinks(string $content): int
    {
        preg_match_all('/<a[^>]+href=["\'](\/[^"\']*|https?:\/\/(www\.)?genznewz\.com[^"\']*)["\'][^>]*>/i', $content, $matches);

        return count($matches[0]);
    }

    protected function modelUrl(object $model): ?string
    {
        $slug = $model->slugable ?? null;

        if (! $slug || ! $slug->key) {
            return null;
        }

        $prefix = trim((string) ($slug->prefix ?? ''), '/');
        $path = $prefix !== '' ? $prefix . '/' . $slug->key : $slug->key;

        return url($path);
    }

    protected function joinLinks(array $links): string
    {
        if (count($links) === 1) {
            return $links[0];
        }

        if (count($links) === 2) {
            return $links[0] . ' and ' . $links[1];
        }

        $last = array_pop($links);

        return implode(', ', $links) . ', and ' . $last;
    }
}
