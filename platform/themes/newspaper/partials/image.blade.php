@php
// Optimized image component with WebP, lazy loading, exact dimensions and srcset.
//
// Usage: @include('theme::partials.image', [
//     'image' => $post->image, 'alt' => $post->name, 'size' => 'medium',
// ])
//
// Why this is defensive rather than trusting RvMedia blindly: RvMedia returns a
// derivative URL whether or not the file exists. Only `thumb`, `medium` and
// `featured` had ever been generated for the existing library, so any other
// size (notably `large`, which the article hero asks for) resolved to a URL
// that 404s. Here every candidate is checked on disk and the first one that
// exists wins, so adding a new size can never produce a broken <img>.

$image = $image ?? null;
$alt = $alt ?? '';
$size = $size ?? 'medium';
$class = $class ?? '';
$imgClass = $imgClass ?? '';
$lazy = $lazy ?? true;
$loading = $loading ?? ($lazy ? 'lazy' : 'eager');
$fetchPriority = $fetchPriority ?? null;

$altText = trim(strip_tags($alt));
if (empty($altText)) {
    $altText = 'GenZ NewZ article image';
}

// Registered sizes, name => [w, h]. Authoritative: this is whatever the theme
// and core have registered, so it cannot drift from RvMedia's own naming.
$registeredSizes = [];
foreach ((array) RvMedia::getSizes() as $sizeName => $dimensions) {
    if (is_string($dimensions) && preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
        $registeredSizes[$sizeName] = ['w' => (int) $matches[1], 'h' => (int) $matches[2]];
    }
}

if (empty($registeredSizes)) {
    $registeredSizes = ['medium' => ['w' => 540, 'h' => 360]];
}

// Ascending by width, so "the smallest that is big enough" is a simple scan.
uasort($registeredSizes, fn ($a, $b) => $a['w'] <=> $b['w']);

$imageUrl = $image ? RvMedia::getImageUrl($image) : RvMedia::getDefaultImage();
$defaultRatio = '66.67%';

// Memoised across every include in the request: a homepage renders dozens of
// these and each one would otherwise re-stat the same few paths.
static $existenceCache = [];

/**
 * Absolute URL of the `-WxH` derivative, matching RvMedia's own filename scheme
 * (name-1024x683.webp) without re-deriving it through the media service.
 */
$derivativeUrl = function (int $width, int $height) use ($imageUrl): string {
    $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    if (! $extension) {
        return $imageUrl;
    }

    return substr($imageUrl, 0, -(strlen($extension) + 1)) . "-{$width}x{$height}.{$extension}";
};

/** Storage-relative path for the same derivative, used for the existence check. */
$derivativePath = function (int $width, int $height) use ($imageUrl): string {
    $path = ltrim((string) parse_url($imageUrl, PHP_URL_PATH), '/');
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    if (! $extension) {
        return $path;
    }

    return substr($path, 0, -(strlen($extension) + 1)) . "-{$width}x{$height}.{$extension}";
};

$existsOnDisk = function (string $path) use (&$existenceCache, $image): bool {
    if (array_key_exists($path, $existenceCache)) {
        return $existenceCache[$path];
    }

    // Files live on the `public` disk; strip its web prefix before asking.
    $relative = preg_replace('#^storage/#', '', $path);

    return $existenceCache[$path] = (bool) $image && Storage::disk('public')->exists($relative);
};

// Candidate list: every registered derivative that is actually on disk, then
// the original as the last resort. Widths become the srcset descriptors.
$candidates = [];

foreach ($registeredSizes as $name => $dims) {
    $path = $derivativePath($dims['w'], $dims['h']);

    if ($existsOnDisk($path)) {
        $candidates[$dims['w']] = [
            'url' => $derivativeUrl($dims['w'], $dims['h']),
            'w' => $dims['w'],
            'h' => $dims['h'],
        ];
    }
}

// The original's true width is unknown without reading the file, so it is only
// advertised as a 2x/3x-quality fallback rather than a precise descriptor.
$originalFallback = ['url' => $imageUrl, 'w' => null, 'h' => null];

// Pick the base src: the requested size when available, otherwise the smallest
// candidate at least as wide as the request, otherwise the largest available.
// The chain must not assume `medium` exists - outside the theme's own bootstrap
// only `thumb` is registered, and indexing a missing key was an exception.
$requested = $registeredSizes[$size]
    ?? $registeredSizes['medium']
    ?? (reset($registeredSizes) ?: ['w' => 540, 'h' => 360]);

$selected = null;
foreach ($candidates as $candidate) {
    if ($candidate['w'] >= $requested['w']) {
        $selected = $candidate;
        break;
    }
}

if (! $selected) {
    $selected = $candidates ? end($candidates) : $originalFallback;
    reset($candidates);
}

$srcUrl = $selected['url'];
$width = $selected['w'];
$height = $selected['h'];
$ratio = $height ? rtrim(rtrim(number_format($height / $width * 100, 4, '.', ''), '0'), '.') . '%' : $defaultRatio;

// A srcset is only useful with two or more real widths.
$srcset = [];
foreach ($candidates as $candidate) {
    $srcset[] = $candidate['url'] . ' ' . $candidate['w'] . 'w';
}

$hasSrcset = count($srcset) >= 2;
$isHero = ! $lazy;
@endphp

@if($image)
<div class="optimized-image-wrapper {{ $class }}" style="--aspect-ratio: {{ $ratio }};">
    <picture class="optimized-image">
        {{-- Modern formats first; every candidate below is confirmed present on disk. --}}
        <source srcset="{{ $hasSrcset ? implode(', ', $srcset) : $srcUrl }}"
                type="image/webp"
                @if($width) width="{{ $width }}" @endif
                @if($height) height="{{ $height }}" @endif>

        <img src="{{ $srcUrl }}"
             alt="{{ $altText }}"
             @if($width) width="{{ $width }}" @endif
             @if($height) height="{{ $height }}" @endif
             @if($hasSrcset) srcset="{{ implode(', ', $srcset) }}" sizes="{{ $isHero ? '(max-width: 900px) 100vw, 900px' : '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px' }}" @endif
             loading="{{ $loading }}"
             @if($fetchPriority) fetchpriority="{{ $fetchPriority }}" @endif
             decoding="async"
             class="img-fluid {{ $imgClass }}">
    </picture>
</div>
@endif
