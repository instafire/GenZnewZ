<?php

namespace App\Services;

class ArticleContentImageSanitizer
{
    /**
     * Remove Pexels-sourced images from article body HTML/Markdown.
     *
     * Pexels images are rendered separately as the post's featured image.
     * Body content may still contain legacy image tags submitted by an
     * automation client, so this remains defensive at both write and render.
     */
    public function sanitize(?string $content): string
    {
        $content = (string) $content;

        if ($content === '') {
            return '';
        }

        // Remove complete media containers first so <source> and attribution
        // markup cannot survive after the image itself is removed.
        $content = preg_replace_callback(
            '/<(figure|picture)\b[^>]*>.*?<\/\1>/is',
            function (array $match): string {
                return $this->containsPexelsReference($match[0]) ? '' : $match[0];
            },
            $content
        ) ?? $content;

        // Remove standalone Pexels image/source tags, including lazy-load
        // attributes such as data-src and data-original.
        $content = preg_replace_callback(
            '/<(img|source)\b[^>]*>/i',
            function (array $match): string {
                return $this->containsPexelsReference($match[0]) ? '' : $match[0];
            },
            $content
        ) ?? $content;

        // Remove Markdown image syntax that points to Pexels or local media
        // uploaded by the Pexels importer.
        $content = preg_replace(
            '/!\[[^\]]*\]\(\s*<?[^)>\s]*(?:pexels|storage\/pexels)[^)>\s]*>?[^)]*\)/i',
            '',
            $content
        ) ?? $content;

        // Clean empty wrappers left by linked-image submissions without
        // removing surrounding editorial paragraphs or text.
        $content = preg_replace('/<a\b[^>]*>\s*<\/a>/i', '', $content) ?? $content;
        $content = preg_replace('/<p\b[^>]*>\s*(?:<a\b[^>]*>\s*<\/a>\s*)<\/p>/i', '', $content) ?? $content;
        $content = preg_replace('/<p\b[^>]*>\s*<br\s*\/?>(?:\s*<br\s*\/?>)*\s*<\/p>/i', '', $content) ?? $content;

        return trim($content);
    }

    protected function containsPexelsReference(string $markup): bool
    {
        return preg_match('/(?:pexels(?:\.com|\.com\/|\/)|storage\/pexels)/i', html_entity_decode($markup)) === 1;
    }
}
