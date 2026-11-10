<?php

namespace App\Services;

use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Quality gate for agent-supplied featured images (2026-11-09).
 *
 * AI reporter agents generate each article's featured image themselves with
 * their own image models and tokens, then upload it via
 * POST /api/v1/automation/media/upload. The server pays nothing for
 * generation — this service only refuses images that are not fit to serve:
 * too small, wrong shape, blank/flat, or a duplicate of an image already
 * used on another post. A rejected upload is never stored and the agent gets a
 * 422 naming the failed checks, so it can re-roll once; after a failed
 * re-roll the post still publishes and the server falls back to Pexels.
 *
 * Checks (all must pass):
 *  - valid, decodable image file (GD)
 *  - dimensions >= 1024x576, aspect 16:9 ±5% (1.70–1.86)
 *  - file size 15 KB–2 MB (catches blank/broken renders)
 *  - luminance mean/stddev on a 32x32 sample: rejects near-black,
 *    near-white and flat frames
 *  - 8x8 average-hash dedupe vs hashes stored for earlier agent images
 *    (post meta `ai_image_ahash`); Hamming distance <= 5 counts as a dup
 */
class AgentImageGateService
{
    public const MIN_WIDTH = 1024;
    public const MIN_HEIGHT = 576;
    public const MIN_ASPECT = 1.70;
    public const MAX_ASPECT = 1.86;
    public const MIN_BYTES = 15 * 1024;
    public const MAX_BYTES = 2 * 1024 * 1024;
    public const MIN_STDDEV = 4.0;
    public const MIN_MEAN = 6.0;
    public const MAX_MEAN = 250.0;
    public const MAX_DUPLICATE_HAMMING = 5;

    /**
     * @return array{ok: bool, errors: string[], width: int|null, height: int|null, bytes: int|null, ahash: string|null}
     */
    public function validateLocalFile(string $path): array
    {
        $errors = [];
        $width = null;
        $height = null;
        $bytes = is_file($path) ? filesize($path) : null;
        $ahash = null;

        if ($bytes === false || $bytes === null) {
            return ['ok' => false, 'errors' => ['image_missing: file not found on disk'], 'width' => null, 'height' => null, 'bytes' => null, 'ahash' => null];
        }

        if ($bytes < self::MIN_BYTES) {
            $errors[] = 'image_too_small: ' . $bytes . ' bytes is below the 15 KB minimum (blank or broken render?)';
        }

        if ($bytes > self::MAX_BYTES) {
            $errors[] = 'image_too_large: ' . $bytes . ' bytes exceeds the 2 MB maximum';
        }

        $info = @getimagesize($path);

        if ($info === false) {
            $errors[] = 'image_unreadable: not a decodable image file';

            return ['ok' => false, 'errors' => $errors, 'width' => null, 'height' => null, 'bytes' => $bytes, 'ahash' => null];
        }

        [$width, $height] = [$info[0], $info[1]];

        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            $errors[] = "image_dimensions: {$width}x{$height} is below the 1024x576 minimum";
        } elseif ($height > 0) {
            $aspect = $width / $height;

            if ($aspect < self::MIN_ASPECT || $aspect > self::MAX_ASPECT) {
                $errors[] = 'image_aspect: ' . number_format($aspect, 2) . ' is outside the 16:9 range (1.70-1.86); generate 1024x576';
            }
        }

        $sample = $this->luminanceSample($path);

        if ($sample === null) {
            $errors[] = 'image_unreadable: GD could not decode the image';
        } else {
            [$mean, $stddev] = $sample;

            if ($mean < self::MIN_MEAN) {
                $errors[] = 'image_blank: frame is near-black (mean luminance ' . number_format($mean, 1) . ')';
            } elseif ($mean > self::MAX_MEAN) {
                $errors[] = 'image_blank: frame is near-white (mean luminance ' . number_format($mean, 1) . ')';
            } elseif ($stddev < self::MIN_STDDEV) {
                $errors[] = 'image_blank: frame is flat/blank (luminance stddev ' . number_format($stddev, 1) . ' below 4.0)';
            }
        }

        if (empty($errors)) {
            $ahash = $this->averageHash($path);

            if ($ahash === null) {
                $errors[] = 'image_unreadable: could not hash the image';
            } elseif ($this->isDuplicate($ahash)) {
                $errors[] = 'image_duplicate: this image (or a near-identical copy) is already used on another post; generate a fresh one';
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'width' => $width, 'height' => $height, 'bytes' => $bytes, 'ahash' => $ahash];
    }

    /**
     * Mean + stddev of luminance over a 32x32 downsample, or null when GD
     * cannot decode the file.
     *
     * @return array{float, float}|null
     */
    protected function luminanceSample(string $path): ?array
    {
        $raw = @file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        $src = @imagecreatefromstring($raw);

        if ($src === false) {
            return null;
        }

        $small = imagecreatetruecolor(32, 32);
        imagecopyresampled($small, $src, 0, 0, 0, 0, 32, 32, imagesx($src), imagesy($src));

        $sum = 0.0;
        $sumSq = 0.0;
        $n = 0;

        for ($y = 0; $y < 32; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $sum += $lum;
                $sumSq += $lum * $lum;
                $n++;
            }
        }

        imagedestroy($small);
        imagedestroy($src);

        $mean = $sum / $n;
        $variance = max(0.0, ($sumSq / $n) - ($mean * $mean));

        return [$mean, sqrt($variance)];
    }

    /**
     * 8x8 average hash as a 16-char hex string, or null when undecodable.
     */
    public function averageHash(string $path): ?string
    {
        $raw = @file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        $src = @imagecreatefromstring($raw);

        if ($src === false) {
            return null;
        }

        $small = imagecreatetruecolor(8, 8);
        imagecopyresampled($small, $src, 0, 0, 0, 0, 8, 8, imagesx($src), imagesy($src));

        $pixels = [];

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $pixels[] = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            }
        }

        imagedestroy($small);
        imagedestroy($src);

        $mean = array_sum($pixels) / count($pixels);
        $bits = '';

        foreach ($pixels as $lum) {
            $bits .= ($lum >= $mean) ? '1' : '0';
        }

        $hex = '';

        for ($i = 0; $i < 64; $i += 4) {
            $hex .= dechex(bindec(substr($bits, $i, 4)));
        }

        return $hex;
    }

    public function isDuplicate(string $ahash, ?int $excludePostId = null): bool
    {
        $stored = DB::table('meta_boxes')
            ->where('meta_key', 'ai_image_ahash')
            ->where('reference_type', Post::class)
            ->when($excludePostId !== null, fn ($q) => $q->where('reference_id', '!=', $excludePostId))
            ->pluck('meta_value')
            ->all();

        foreach ($stored as $other) {
            if (! is_string($other) || strlen($other) !== 16 || strlen($ahash) !== 16) {
                continue;
            }

            if ($this->hammingDistance($ahash, $other) <= self::MAX_DUPLICATE_HAMMING) {
                return true;
            }
        }

        return false;
    }

    protected function hammingDistance(string $a, string $b): int
    {
        $distance = 0;

        for ($i = 0; $i < 16; $i++) {
            $xor = hexdec($a[$i]) ^ hexdec($b[$i]);
            $distance += substr_count(decbin($xor), '1');
        }

        return $distance;
    }

    public function storeHash(Post $post, string $ahash): void
    {
        MetaBox::saveMetaBoxData($post, 'ai_image_ahash', $ahash);
    }
}
