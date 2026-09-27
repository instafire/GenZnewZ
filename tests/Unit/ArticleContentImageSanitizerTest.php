<?php

namespace Tests\Unit;

use App\Services\ArticleContentImageSanitizer;
use Tests\TestCase;

class ArticleContentImageSanitizerTest extends TestCase
{
    public function test_removes_pexels_images_from_html_body(): void
    {
        $content = '<p>Opening paragraph.</p>'
            . '<figure><img src="https://genznewz.com/storage/pexels/photo.webp" alt="Pexels photo">'
            . '<figcaption>Photo credit</figcaption></figure>'
            . '<p>Closing paragraph.</p>';

        $cleaned = app(ArticleContentImageSanitizer::class)->sanitize($content);

        $this->assertStringNotContainsString('storage/pexels', $cleaned);
        $this->assertStringContainsString('Opening paragraph.', $cleaned);
        $this->assertStringContainsString('Closing paragraph.', $cleaned);
    }

    public function test_removes_linked_pexels_images_and_markdown_images(): void
    {
        $content = '<p><a href="https://www.pexels.com/photo/example">'
            . '<img src="https://images.pexels.com/photos/example.jpeg" alt="Example"></a></p>'
            . '<p>![Example](https://genznewz.com/storage/pexels/example.webp)</p>';

        $cleaned = app(ArticleContentImageSanitizer::class)->sanitize($content);

        $this->assertStringNotContainsString('pexels', strtolower($cleaned));
        $this->assertStringNotContainsString('![' , $cleaned);
    }

    public function test_preserves_non_pexels_editorial_images(): void
    {
        $content = '<p>Context paragraph.</p>'
            . '<figure><img src="https://genznewz.com/storage/editorial/map.webp" alt="Editorial map"></figure>'
            . '<p>More context.</p>';

        $cleaned = app(ArticleContentImageSanitizer::class)->sanitize($content);

        $this->assertStringContainsString('storage/editorial/map.webp', $cleaned);
        $this->assertStringContainsString('Context paragraph.', $cleaned);
        $this->assertStringContainsString('More context.', $cleaned);
    }

    public function test_empty_content_stays_empty(): void
    {
        $this->assertSame('', app(ArticleContentImageSanitizer::class)->sanitize(null));
    }
}
