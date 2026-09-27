<?php

namespace Tests\Feature;

use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

/**
 * Guards the front-end delivery fixes: the image component's on-disk fallback
 * chain, the article hero preload hint, OS-level dark mode, and the 48px touch
 * target minimum.
 *
 * These all live in the theme rather than in application code, so they are the
 * kind of change that silently regresses. Two of them had already shipped broken
 * once: the hero requested an unregistered `large` size (so RvMedia handed back
 * the multi-hundred-KB original) and the preload hint was built from an
 * already-sized URL, producing a path like `name-1024x683-560x380.webp` that the
 * <img> never referenced.
 */
class ThemeFrontendDeliveryTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    private const IMAGE_PARTIAL = 'platform/themes/newspaper/partials/image.blade.php';

    private const HEADER_PARTIAL = 'platform/themes/newspaper/partials/header.blade.php';

    private const THEME_JS_SOURCE = 'platform/themes/newspaper/assets/js/newspaper.js';

    private const THEME_JS_DEPLOYED = 'public/themes/newspaper/public/js/newspaper.js';

    private const CSS_SOURCE = 'platform/themes/newspaper/public/css/newspaper.css';

    private const CSS_DEPLOYED = 'public/themes/newspaper/public/css/newspaper.css';

    /**
     * Render the image partial exactly as the article template does.
     *
     * The partial is compiled straight from source rather than through the
     * `theme::` view namespace: that namespace is only registered once the theme's
     * service provider has booted, which the test harness stubs for outgoing URLs
     * only. Compiling the file directly exercises the same code path.
     */
    private function renderImagePartial(array $data): string
    {
        return Blade::render(
            (string) file_get_contents(base_path(self::IMAGE_PARTIAL)),
            $data
        );
    }

    /** The smallest registered size, which is what an unsized request must fall back to. */
    private function smallestRegisteredSize(): array
    {
        $sizes = [];

        foreach ((array) RvMedia::getSizes() as $name => $dimensions) {
            if (is_string($dimensions) && preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
                $sizes[$name] = ['w' => (int) $matches[1], 'h' => (int) $matches[2]];
            }
        }

        $this->assertNotEmpty($sizes, 'The media package must expose at least one registered size.');

        uasort($sizes, fn ($a, $b) => $a['w'] <=> $b['w']);

        return reset($sizes);
    }

    public function test_image_partial_uses_an_on_disk_derivative_instead_of_the_original(): void
    {
        Storage::fake('public');

        $size = $this->smallestRegisteredSize();
        $derivative = "hero-{$size['w']}x{$size['h']}.webp";

        // Only the derivative exists; the original does not need to, because the
        // component decides purely on what it can see on the public disk.
        Storage::disk('public')->put("posts/{$derivative}", 'fake-image-bytes');

        $html = $this->renderImagePartial([
            'image' => 'posts/hero.webp',
            'alt' => 'A headline',
            'size' => 'large',
            'lazy' => false,
        ]);

        $this->assertStringContainsString($derivative, $html, 'The hero must use the derivative that exists on disk.');
        $this->assertMatchesRegularExpression(
            '/<img[^>]+width="' . $size['w'] . '"[^>]+height="' . $size['h'] . '"/',
            $html,
            'Declared dimensions must be the real derivative dimensions, not a fabricated ratio.'
        );
        $this->assertStringContainsString('loading="eager"', $html, 'The above-the-fold hero must not be lazy loaded.');
    }

    public function test_image_partial_never_references_a_derivative_missing_from_disk(): void
    {
        Storage::fake('public');

        // A media row can exist while its derivative was never generated. RvMedia
        // still returns a derivative URL in that case, which is exactly what used
        // to 404 the hero sitewide.
        $html = $this->renderImagePartial([
            'image' => 'posts/never-generated.webp',
            'alt' => 'A headline',
            'size' => 'large',
        ]);

        $this->assertDoesNotMatchRegularExpression(
            '/-\d+x\d+\.webp/',
            $html,
            'No sized derivative URL may be emitted when no derivative exists on disk.'
        );

        $this->assertStringContainsString('never-generated.webp', $html, 'The original is the last-resort fallback.');
    }

    public function test_image_partial_degrades_safely_when_no_image_is_given(): void
    {
        $html = $this->renderImagePartial(['image' => null, 'alt' => 'Nothing here']);

        $this->assertSame('', trim($html), 'An empty image must render nothing rather than a broken <img>.');
    }

    public function test_hero_preload_never_double_appends_a_size_suffix(): void
    {
        $source = (string) file_get_contents(base_path(self::HEADER_PARTIAL));

        $this->assertStringContainsString('rel="preload" as="image"', $source, 'The LCP hero must be preloaded.');

        // The guard that keeps an already-sized URL from being rewritten to
        // `name-1024x683-560x380.webp` — a URL no <img> on the page uses.
        $this->assertStringContainsString('$heroIsUnsized', $source);
        $this->assertStringContainsString(
            'preg_match(\'/-\\d+x\\d+',
            $source,
            'The preload must skip URLs that already carry a size suffix.'
        );

        $this->assertStringContainsString('fetchpriority="high"', $source);
    }

    public function test_hero_preload_candidates_match_the_image_component_sizes(): void
    {
        $header = (string) file_get_contents(base_path(self::HEADER_PARTIAL));

        $this->assertMatchesRegularExpression(
            "/foreach \(\['1024x683', '560x380', '540x360'\] as \\\$heroStep\)/",
            $header,
            'The preload fallback order must match the sizes the image component can emit.'
        );

        // Preloading a URL the hero does not use wastes a request, so the hint is
        // only emitted when the file was confirmed present.
        $this->assertStringContainsString("Storage::disk('public')->exists(\$heroCandidatePath)", $header);
    }

    public function test_dark_mode_follows_the_operating_system_preference(): void
    {
        $js = (string) file_get_contents(base_path(self::THEME_JS_SOURCE));

        $this->assertStringContainsString(
            'prefers-color-scheme: dark',
            $js,
            'Dark mode must respond to the OS preference, not only to the manual toggle.'
        );

        $this->assertStringContainsString("stored === 'enabled'", $js, 'An explicit choice must win over the OS.');
        $this->assertStringContainsString("stored === 'disabled'", $js);
        $this->assertStringContainsString("addEventListener('change'", $js, 'OS changes must be followed until the reader chooses.');
    }

    /**
     * The theme's JS is served from public/themes, which is a separate deployed copy,
     * so a fix applied only to the source are never reaches a visitor.
     */
    public function test_deployed_javascript_matches_its_source(): void
    {
        $source = (string) file_get_contents(base_path(self::THEME_JS_SOURCE));
        $deployed = (string) file_get_contents(base_path(self::THEME_JS_DEPLOYED));

        $this->assertSame($source, $deployed, 'Deploy the theme JS to public/themes or the change will not ship.');
    }

    public function test_mobile_touch_targets_meet_the_48px_minimum(): void
    {
        foreach ([self::CSS_SOURCE, self::CSS_DEPLOYED] as $path) {
            $css = (string) file_get_contents(base_path($path));

            $this->assertStringContainsString('min-height: 48px', $css, "{$path} must reserve 48px tall touch targets.");
            $this->assertStringContainsString('nav-menu-toggle', $css, "{$path} must size the mobile nav controls.");

            // Match real declarations only: the files deliberately mention 44px in a
            // comment explaining why the minimum was raised.
            $this->assertDoesNotMatchRegularExpression(
                '/(?:min-)?(?:height|width)\s*:\s*44px/',
                $css,
                "{$path} still declares a 44px target, below the accessible minimum."
            );
        }
    }
}
