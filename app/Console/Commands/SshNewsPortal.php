<?php

namespace App\Console\Commands;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SshNewsPortal extends Command
{
    protected $signature = 'portal:ssh {--limit=12 : Max posts shown per listing}';

    protected $description = 'Interactive SSH portal to browse GenZ NewZ categories and read articles in terminal';

    public function handle(): int
    {
        $this->line('');
        $this->info('GenZ NewZ SSH Portal');
        $this->line('Browse categories and read articles from your terminal.');
        $this->line(str_repeat('-', 62));

        while (true) {
            $action = $this->choice('Choose an action', [
                'Latest headlines',
                'Featured stories',
                'Browse by category',
                'Search articles',
                'Exit',
            ], 0);

            if ($action === 'Exit') {
                $this->line('Goodbye.');
                return self::SUCCESS;
            }

            if ($action === 'Latest headlines') {
                $this->showPostList($this->getLatestPosts());
                continue;
            }

            if ($action === 'Featured stories') {
                $this->showPostList($this->getFeaturedPosts());
                continue;
            }

            if ($action === 'Browse by category') {
                $this->browseByCategory();
                continue;
            }

            if ($action === 'Search articles') {
                $this->searchArticles();
            }
        }
    }

    protected function getLatestPosts(): Collection
    {
        return Post::query()
            ->wherePublished()
            ->with(['categories', 'slugable'])
            ->latest('created_at')
            ->limit($this->listingLimit())
            ->get();
    }

    protected function getFeaturedPosts(): Collection
    {
        return Post::query()
            ->wherePublished()
            ->where('is_featured', true)
            ->with(['categories', 'slugable'])
            ->latest('created_at')
            ->limit($this->listingLimit())
            ->get();
    }

    protected function listingLimit(): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            return 12;
        }

        return min($limit, 50);
    }

    protected function browseByCategory(): void
    {
        $categories = Category::query()
            ->wherePublished()
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($categories->isEmpty()) {
            $this->warn('No published categories found.');
            return;
        }

        $indexMap = [];
        $categoryOptions = [];

        foreach ($categories as $category) {
            $label = sprintf('%s', $category->name);
            $indexMap[$label] = (int) $category->id;
            $categoryOptions[] = $label;
        }

        $categoryOptions[] = 'Back';

        $selected = $this->choice('Select a category', $categoryOptions, 0);

        if ($selected === 'Back') {
            return;
        }

        $categoryId = $indexMap[$selected] ?? null;

        if (! $categoryId) {
            $this->warn('Invalid category selection.');
            return;
        }

        $posts = Post::query()
            ->wherePublished()
            ->whereHas('categories', function ($query) use ($categoryId): void {
                $query->where('categories.id', $categoryId);
            })
            ->with(['categories', 'slugable'])
            ->latest('created_at')
            ->limit($this->listingLimit())
            ->get();

        $this->showPostList($posts);
    }

    protected function searchArticles(): void
    {
        $query = trim((string) $this->ask('Enter keyword(s)'));

        if ($query === '') {
            $this->warn('Search cancelled (empty keyword).');
            return;
        }

        $posts = Post::query()
            ->wherePublished()
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->with(['categories', 'slugable'])
            ->latest('created_at')
            ->limit($this->listingLimit())
            ->get();

        $this->showPostList($posts);
    }

    protected function showPostList(Collection $posts): void
    {
        if ($posts->isEmpty()) {
            $this->warn('No articles found.');
            return;
        }

        while (true) {
            $indexMap = [];
            $options = [];

            foreach ($posts as $index => $post) {
                $publishedDate = optional($post->created_at)->format('Y-m-d') ?: 'date unknown';

                $label = sprintf('%02d. %s (%s)', $index + 1, $this->truncate($post->name, 80), $publishedDate);
                $indexMap[$label] = $post;
                $options[] = $label;
            }

            $options[] = 'Back';

            $selected = $this->choice('Select an article to read', $options, 0);

            if ($selected === 'Back') {
                return;
            }

            $post = $indexMap[$selected] ?? null;

            if (! $post) {
                $this->warn('Invalid article selection.');
                return;
            }

            $this->renderPost($post);

            if (! $this->confirm('Read another article from this list?', true)) {
                return;
            }
        }
    }

    protected function renderPost(Post $post): void
    {
        $this->line('');
        $this->info($post->name);
        $this->line(str_repeat('=', 62));

        $categoryNames = $post->categories->pluck('name')->filter()->values()->all();
        $publishedDate = optional($post->created_at)->format('F j, Y g:i A') ?: 'Unknown publication time';

        $this->line('Published: ' . $publishedDate);
        $this->line('Categories: ' . (empty($categoryNames) ? 'Uncategorized' : implode(', ', $categoryNames)));
        $this->line('URL: ' . ($post->url ?? 'N/A'));

        $summary = $this->cleanInlineText((string) ($post->description ?? ''));
        if ($summary !== '') {
            $this->line('');
            $this->line('Summary:');
            $this->line($this->wrapText($summary));
        }

        $paragraphs = $this->extractParagraphs((string) ($post->content ?? ''));

        if (empty($paragraphs)) {
            $this->line('');
            $this->warn('This article has no text body available in terminal format.');
            return;
        }

        $this->line('');
        $this->line('Article:');
        $this->renderParagraphs($paragraphs);
        $this->line('');
    }

    protected function cleanInlineText(string $text): string
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?: '';

        return trim($plain);
    }

    /**
     * Preserve article paragraph breaks when converting HTML to terminal text.
     *
     * @return array<int, string>
     */
    protected function extractParagraphs(string $html): array
    {
        $html = trim($html);

        if ($html === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $html);
        $normalized = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $normalized) ?: $normalized;
        $normalized = preg_replace('/<li[^>]*>/i', '- ', $normalized) ?: $normalized;
        $normalized = preg_replace('/<\/(p|div|section|article|h1|h2|h3|h4|h5|h6|li|blockquote|ul|ol)>/i', "\n\n", $normalized) ?: $normalized;

        $plain = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $chunks = preg_split('/\n\s*\n+/u', $plain) ?: [];

        $paragraphs = [];

        foreach ($chunks as $chunk) {
            $line = trim($chunk);
            if ($line === '') {
                continue;
            }

            $line = preg_replace('/[ \t]+/u', ' ', $line) ?: $line;
            $line = preg_replace('/\n+/u', ' ', $line) ?: $line;

            if ($line !== '') {
                $paragraphs[] = $line;
            }
        }

        return $paragraphs;
    }

    /**
     * @param array<int, string> $paragraphs
     */
    protected function renderParagraphs(array $paragraphs): void
    {
        foreach ($paragraphs as $paragraph) {
            $this->line($this->wrapText($paragraph));
            $this->line('');
        }
    }

    protected function wrapText(string $text): string
    {
        return wordwrap($text, 100);
    }

    protected function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1)) . '…';
    }
}
