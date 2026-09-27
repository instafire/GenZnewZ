<?php

namespace Botble\Theme\Supports;

use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Youtube
{
    public static function getYoutubeVideoEmbedURL(?string $url): string
    {
        $url = rtrim((string) $url, '/');

        if (! $url) {
            return $url;
        }

        // Extract video ID from the URL
        $videoId = self::getYoutubeVideoID($url);

        if (! $videoId) {
            return $url;
        }

        // Build clean embed URL with just the video ID
        return 'https://www.youtube.com/embed/' . $videoId;
    }

    public static function getYoutubeWatchURL(?string $url): string
    {
        $url = rtrim((string) $url, '/');

        if (! $url) {
            return $url;
        }

        if (Str::contains($url, 'embed/')) {
            $url = str_replace('embed/', 'watch?v=', $url);
        } else {
            $exploded = explode('/', $url);

            if (count($exploded) > 1) {
                $videoID = str_replace('embed', '', str_replace('watch?v=', '', Arr::last($exploded)));

                $url = 'https://www.youtube.com/watch?v=' . $videoID;
            }
        }

        return $url;
    }

    public static function getYoutubeVideoID(?string $url): ?string
    {
        $url = rtrim((string) $url, '/');

        if (! $url) {
            return $url;
        }

        // Handle different YouTube URL formats
        $patterns = [
            // Standard watch URL: https://www.youtube.com/watch?v=VIDEO_ID
            '/(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.*&v=)([a-zA-Z0-9_-]{11})/',
            // Short URL: https://youtu.be/VIDEO_ID
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            // Embed URL: https://www.youtube.com/embed/VIDEO_ID
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // Shorts URL: https://www.youtube.com/shorts/VIDEO_ID
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
            // Mobile URL: https://m.youtube.com/watch?v=VIDEO_ID
            '/m\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function isYoutubeURL(?string $url): bool
    {
        $url = rtrim((string) $url, '/');

        if (! $url) {
            return false;
        }

        $regExp = '/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|shorts\/|v\/)?)([\w\-]+)(\S+)?$/';

        return preg_match($regExp, $url);
    }

    public static function getThumbnail(?string $url): string
    {
        $id = self::getYoutubeVideoID($url);

        return $id ? "https://i.ytimg.com/vi_webp/$id/maxresdefault.webp" : RvMedia::getDefaultImage();
    }
}
