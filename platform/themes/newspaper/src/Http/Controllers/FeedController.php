<?php

namespace Theme\Newspaper\Http\Controllers;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\RssFeed\Http\Controllers\RssFeedController;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * RSS 2.0 + JSON Feed 1.1 endpoints.
 *
 * Feeds are published for both readers (news readers, inbox digests) and
 * automated consumers, so every document is well-formed, self-describing and
 * advertises its own canonical URL via <atom:link rel="self">.
 */
class FeedController
{
    protected const ITEM_LIMIT = 40;

    protected const CACHE_TTL = 300;

    /**
     * Feed names owned by the rss-feed plugin; delegated rather than shadowed.
     */
    protected const PLUGIN_FEED_NAMES = ['posts', 'jobs', 'properties', 'projects', 'products'];

    public function index(string $format = 'rss'): Response
    {
        return $this->buildFeed(null, $format);
    }

    public function category(string $slug, string $format = 'rss'): Response|Responsable
    {
        $category = Category::query()
            ->where('status', 'published')
            ->whereHas('slugable', function ($query) use ($slug): void {
                $query->where('key', $slug)->where('reference_type', Category::class);
            })
            ->first();

        if (! $category) {
            // These routes are registered ahead of the rss-feed plugin's
            // generic `feed/{name}` route, so hand its own feed names back to
            // it rather than shadowing URLs that were already live. Its
            // controller returns a Responsable, which the router converts.
            if ($format === 'rss' && in_array($slug, self::PLUGIN_FEED_NAMES, true)) {
                return app(RssFeedController::class)->show($slug);
            }

            // An unknown slug is a 404 rather than an empty 200, so
            // subscribers and crawlers can tell "no such feed" from "feed
            // exists but is currently empty".
            abort(404);
        }

        return $this->buildFeed($category, $format);
    }

    protected function buildFeed(?Category $category, string $format): Response
    {
        $format = $format === 'json' ? 'json' : 'rss';

        $cacheKey = sprintf(
            'news_feed:%s:%s:%s',
            $format,
            $category?->getKey() ?? 'all',
            $category ? ($category->updated_at?->timestamp ?? 0) : $this->latestPostTimestamp()
        );

        $body = cache()->remember($cacheKey, self::CACHE_TTL, function () use ($category, $format): string {
            $posts = $this->posts($category);

            return $format === 'json'
                ? $this->renderJson($posts, $category)
                : $this->renderRss($posts, $category);
        });

        $contentType = $format === 'json'
            ? 'application/feed+json; charset=UTF-8'
            : 'application/rss+xml; charset=UTF-8';

        return response($body, 200)
            ->header('Content-Type', $contentType)
            // Feeds are cheap to revalidate and expensive to regenerate, so
            // let shared caches hold them briefly.
            ->header('Cache-Control', 'public, max-age=600, stale-while-revalidate=3600');
    }

    protected function latestPostTimestamp(): int
    {
        return (int) Post::query()->where('status', 'published')->max('updated_at')
            ?: (int) Post::query()->where('status', 'published')->max('created_at');
    }

    protected function posts(?Category $category): Collection
    {
        $query = Post::query()
            ->where('status', 'published')
            ->with(['slugable', 'categories', 'author'])
            ->orderByDesc('created_at')
            ->take(self::ITEM_LIMIT);

        if ($category) {
            $query->whereHas('categories', function ($q) use ($category): void {
                $q->where('categories.id', $category->getKey());
            });
        }

        return $query->get();
    }

    protected function feedUrl(?Category $category, string $format): string
    {
        $suffix = $format === 'json' ? '.json' : '';

        if ($category) {
            return url('feed/' . $category->slug . $suffix);
        }

        return url('feed' . $suffix);
    }

    protected function channelTitle(?Category $category): string
    {
        $siteTitle = theme_option('site_title', config('app.name', 'GenZ NewZ'));

        return $category ? $category->name . ' — ' . $siteTitle : $siteTitle;
    }

    protected function channelDescription(?Category $category): string
    {
        $siteDescription = theme_option('site_description')
            ?: 'Breaking news, AI insights, politics, culture, and trending stories for Gen Z.';

        if (! $category) {
            return $siteDescription;
        }

        $categoryDescription = trim(strip_tags((string) $category->description));

        return $categoryDescription !== ''
            ? $categoryDescription
            : sprintf('Latest %s coverage from %s.', $category->name, theme_option('site_title', 'GenZ NewZ'));
    }

    /**
     * Feed readers resolve media relative to the feed document, but not every
     * reader honours xml:base, so rewrite root-relative URLs to absolute ones.
     */
    protected function absolutize(string $html): string
    {
        $base = rtrim(url('/'), '/');

        $html = preg_replace('/(src|href)=(["\'])\/(?!\/)/i', '$1=$2' . $base . '/', $html) ?? $html;

        return $html;
    }

    protected function summary(Post $post): string
    {
        $description = trim(strip_tags((string) $post->description));

        if ($description === '') {
            $description = Str::limit(trim(strip_tags((string) $post->content)), 280);
        }

        return $description;
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

    protected function publishedAt(Post $post): Carbon
    {
        return $post->created_at ?? now();
    }

    protected function renderRss(Collection $posts, ?Category $category): string
    {
        $feedUrl = $this->feedUrl($category, 'rss');
        $siteUrl = $category ? $category->url : url('/');
        $title = $this->channelTitle($category);
        $description = $this->channelDescription($category);

        $lastBuild = $posts->max(fn (Post $post) => $this->publishedAt($post)->timestamp)
            ? Carbon::createFromTimestamp((int) $posts->max(fn (Post $post) => $this->publishedAt($post)->timestamp))
            : now();

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" '
            . 'xmlns:atom="http://www.w3.org/2005/Atom" '
            . 'xmlns:content="http://purl.org/rss/1.0/modules/content/" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" />'
        );

        $channel = $xml->addChild('channel');
        $channel->addChild('title', htmlspecialchars($title, ENT_XML1));
        $channel->addChild('link', htmlspecialchars($siteUrl, ENT_XML1));
        $channel->addChild('description', htmlspecialchars($description, ENT_XML1));
        $channel->addChild('language', 'en-us');
        $channel->addChild('generator', 'GenZ NewZ Feed');
        $channel->addChild('lastBuildDate', $lastBuild->toRfc2822String());
        $channel->addChild('ttl', '10');

        $self = $channel->addChild('atom:link', '', 'http://www.w3.org/2005/Atom');
        $self->addAttribute('href', $feedUrl);
        $self->addAttribute('rel', 'self');
        $self->addAttribute('type', 'application/rss+xml');

        foreach ($posts as $post) {
            $item = $channel->addChild('item');
            $item->addChild('title', htmlspecialchars((string) $post->name, ENT_XML1));
            $item->addChild('link', htmlspecialchars($post->url, ENT_XML1));

            $guid = $item->addChild('guid', htmlspecialchars($post->url, ENT_XML1));
            $guid->addAttribute('isPermaLink', 'true');

            $item->addChild('pubDate', $this->publishedAt($post)->toRfc2822String());
            $item->addChild('description', htmlspecialchars($this->summary($post), ENT_XML1));

            $content = $item->addChild('content:encoded', '', 'http://purl.org/rss/1.0/modules/content/');
            $content[0] = '<![CDATA[' . $this->absolutize((string) $post->content) . ']]>';

            $author = $post->author->name ?? 'GenZ NewZ Staff';
            $item->addChild('dc:creator', htmlspecialchars($author, ENT_XML1), 'http://purl.org/dc/elements/1.1/');

            foreach ($post->categories as $postCategory) {
                $item->addChild('category', htmlspecialchars((string) $postCategory->name, ENT_XML1));
            }

            if ($image = $this->imageUrl($post)) {
                $enclosure = $item->addChild('enclosure');
                $enclosure->addAttribute('url', $image);
                $enclosure->addAttribute('type', 'image/jpeg');
                $enclosure->addAttribute('length', '0');
            }
        }

        return $xml->asXML();
    }

    protected function renderJson(Collection $posts, ?Category $category): string
    {
        $items = $posts->map(function (Post $post): array {
            $image = $this->imageUrl($post);

            return array_filter([
                'id' => (string) $post->url,
                'url' => (string) $post->url,
                'title' => (string) $post->name,
                'summary' => $this->summary($post),
                'content_html' => $this->absolutize((string) $post->content),
                'image' => $image,
                'date_published' => $this->publishedAt($post)->toIso8601ZuluString(),
                'date_modified' => ($post->updated_at ?? $this->publishedAt($post))->toIso8601ZuluString(),
                'authors' => [[
                    'name' => $post->author->name ?? 'GenZ NewZ Staff',
                    'url' => $post->author->url ?? url('/'),
                ]],
                'tags' => $post->categories->pluck('name')->values()->all(),
                'language' => 'en-us',
            ], fn ($value) => $value !== null);
        })->values()->all();

        $feed = array_filter([
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $this->channelTitle($category),
            'home_page_url' => $category ? $category->url : url('/'),
            'feed_url' => $this->feedUrl($category, 'json'),
            'description' => $this->channelDescription($category),
            'language' => 'en-us',
            'items' => $items,
        ], fn ($value) => $value !== null);

        return json_encode(
            $feed,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '{}';
    }
}
