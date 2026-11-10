<?php

namespace App\Services;

/**
 * Render-time social embeds for article bodies.
 *
 * Articles cite YouTube, Instagram and TikTok content with plain links
 * (source lists, inline references). Readers should be able to watch that
 * media without leaving the page, so for every distinct social content
 * URL linked from the body this appends an embedded player right after
 * the block that first links it. The original link is left untouched —
 * it is still the citation — and each target is embedded at most once
 * per article.
 *
 * Supported targets: YouTube videos (watch / shorts / live / youtu.be),
 * Instagram reels, posts and TV, and TikTok videos (full @user/video
 * URLs; vm.tiktok.com short links cannot be resolved without following
 * the redirect, so they stay plain links). The matching iframe hosts are
 * allow-listed in the site CSP (SetSecurityHeaders, frame-src), as is
 * Instagram's embed.js in script-src.
 */
class ArticleSocialEmbedService
{
    public function transform(?string $content): string
    {
        $content = (string) $content;

        if ($content === '' || stripos($content, '<a') === false) {
            return $content;
        }

        $matched = preg_match_all(
            '/<a\b[^>]*\bhref\s*=\s*("|\')(.*?)\1[^>]*>.*?<\/a\s*>/is',
            $content,
            $anchors,
            PREG_OFFSET_CAPTURE
        );

        if (!$matched) {
            return $content;
        }

        $seen = [];
        $insertions = [];

        foreach ($anchors[0] as $i => [$anchorHtml, $anchorOffset]) {
            $href = html_entity_decode((string) $anchors[2][$i][0], ENT_QUOTES);

            $embed = $this->embedFor($href);

            if ($embed === null || isset($seen[$embed['key']])) {
                continue;
            }

            $seen[$embed['key']] = true;

            // Idempotent across stacked transforms: never embed a target
            // the body already carries a player (or a previous pass) for.
            if (str_contains($content, 'data-gnz-embed="' . $embed['key'] . '"')) {
                continue;
            }

            $anchorEnd = $anchorOffset + strlen($anchorHtml);
            $insertions[] = [$this->blockEndPosition($content, $anchorEnd), $embed['html']];
        }

        // Splice from the end of the document backwards so earlier
        // insertion offsets stay valid.
        usort($insertions, fn (array $a, array $b): int => $b[0] <=> $a[0]);

        $hasInstagram = false;

        foreach ($insertions as [$position, $html]) {
            $hasInstagram = $hasInstagram || str_contains($html, 'instagram-media');
            $content = substr($content, 0, $position) . $html . substr($content, $position);
        }

        // Instagram's embed script sizes each player to its content; it
        // is loaded once per article, after the players it manages.
        if ($hasInstagram) {
            $content .= '<script async src="https://www.instagram.com/embed.js"></script>';
        }

        return $content;
    }

    /**
     * Where the embed goes: right after the block element that contains
     * the link (paragraph, list item, blockquote, figure). Falls back to
     * right after the link itself when the link is not inside a block we
     * recognise, or when a heading starts first (a link inside a heading
     * must not push its player below the following paragraph).
     */
    protected function blockEndPosition(string $content, int $from): int
    {
        $blockEnd = null;

        foreach (['</p>', '</li>', '</blockquote>', '</figure>'] as $tag) {
            $pos = stripos($content, $tag, $from);

            if ($pos !== false && ($blockEnd === null || $pos < $blockEnd)) {
                $blockEnd = $pos + strlen($tag);
            }
        }

        if ($blockEnd === null) {
            return $from;
        }

        $heading = preg_match('/<h[1-6]\b/i', $content, $m, PREG_OFFSET_CAPTURE, $from)
            ? $m[0][1]
            : null;

        if ($heading !== null && $heading < $blockEnd) {
            return $from;
        }

        return $blockEnd;
    }

    /**
     * Map a URL to its embed, or null when it is not embeddable content.
     *
     * @return array{key: string, html: string}|null
     */
    protected function embedFor(string $url): ?array
    {
        $parts = parse_url(trim($url));

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';

        // YouTube
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            $id = null;

            if (preg_match('#^/(?:shorts|live|embed)/([A-Za-z0-9_-]{6,20})#', $path, $m)) {
                $id = $m[1];
            } elseif (preg_match('#^/watch#', $path)) {
                parse_str($parts['query'] ?? '', $query);
                $candidate = (string) ($query['v'] ?? '');
                $id = preg_match('/^[A-Za-z0-9_-]{6,20}$/', $candidate) ? $candidate : null;
            }

            if ($id !== null) {
                return [
                    'key' => 'youtube:' . $id,
                    'html' => '<div class="gnz-embed gnz-embed--youtube" data-gnz-embed="youtube:' . $id . '">'
                        . '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" title="YouTube video player" loading="lazy" '
                        . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
                        . 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
                ];
            }

            return null;
        }

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            if (preg_match('#^/([A-Za-z0-9_-]{6,20})#', $path, $m)) {
                $id = $m[1];

                return [
                    'key' => 'youtube:' . $id,
                    'html' => '<div class="gnz-embed gnz-embed--youtube" data-gnz-embed="youtube:' . $id . '">'
                        . '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" title="YouTube video player" loading="lazy" '
                        . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
                        . 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
                ];
            }

            return null;
        }

        // Instagram: reels, posts, TV. Uses the official blockquote +
        // embed.js form on purpose: a bare /embed iframe has no fixed
        // height that fits every post (tall reel content ends up clipped
        // behind the iframe's own scrollbar), while embed.js measures
        // each player and sizes it to its content.
        if (in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
            if (preg_match('#^/(reel|reels|p|tv)/([A-Za-z0-9_-]{5,25})#', $path, $m)) {
                $kind = $m[1] === 'reels' ? 'reel' : $m[1];
                $code = $m[2];
                $permalink = 'https://www.instagram.com/' . $kind . '/' . $code . '/';

                return [
                    'key' => 'instagram:' . $code,
                    'html' => '<div class="gnz-embed gnz-embed--instagram" data-gnz-embed="instagram:' . $code . '">'
                        . '<blockquote class="instagram-media" data-instgrm-permalink="' . $permalink . '" data-instgrm-version="14">'
                        . '<a href="' . $permalink . '" target="_blank" rel="noopener">View this post on Instagram</a>'
                        . '</blockquote></div>',
                ];
            }

            return null;
        }

        // TikTok: full video URLs only (short links need a redirect chase)
        if (in_array($host, ['tiktok.com', 'www.tiktok.com', 'm.tiktok.com'], true)) {
            if (preg_match('#^/@[^/]+/video/(\d{8,25})#', $path, $m)) {
                $id = $m[1];

                return [
                    'key' => 'tiktok:' . $id,
                    'html' => '<div class="gnz-embed gnz-embed--tiktok" data-gnz-embed="tiktok:' . $id . '">'
                        . '<iframe src="https://www.tiktok.com/embed/v2/' . $id . '" title="TikTok video" loading="lazy" '
                        . 'allow="encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
                ];
            }

            return null;
        }

        return null;
    }
}
