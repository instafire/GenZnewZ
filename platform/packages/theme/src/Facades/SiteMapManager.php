<?php

namespace Botble\Theme\Facades;

use Botble\Theme\Supports\SiteMapManager as SiteMapManagerSupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Botble\Theme\Supports\SiteMapManager init(string|null $prefix = null, string $extension = 'xml')
 * @method static \Botble\Theme\Supports\SiteMapManager addSitemap(string $loc, string|null $lastModified = null)
 * @method static string route(string|null $key = null)
 * @method static \Botble\Theme\Supports\SiteMapManager add(string $url, string|null $date = null, string $priority = '1.0', string $sequence = 'daily')
 * @method static bool isCached()
 * @method static \Botble\Sitemap\Sitemap getSiteMap()
 * @method static \Illuminate\Http\Response render(string $type = 'xml')
 * @method static array getKeys()
 * @method static string getKeysRegex()
 * @method static \Botble\Theme\Supports\SiteMapManager registerKey(array|string $key, string|null $value = null)
 * @method static \Botble\Theme\Supports\SiteMapManager removeKey(array|string $key)
 * @method static array allowedExtensions()
 * @method static \Botble\Theme\Supports\SiteMapManager setItemsPerPage(int $itemsPerPage)
 * @method static int getItemsPerPage()
 * @method static string getCacheKey(string|null $prefix = null, string $extension = 'xml')
 * @method static void clearCache()
 * @method static \Botble\Theme\Supports\SiteMapManager registerPattern(string $name, string $pattern)
 * @method static string|null getPattern(string $name)
 * @method static string generateKey(string $prefix, string $patternName, array $params = [])
 * @method static \Botble\Theme\Supports\SiteMapManager registerSitemapWithPattern(string $prefix, string $patternName = 'monthly-archive')
 * @method static array|null extractPaginationData(string $key, string $pattern)
 * @method static array|null extractPaginationDataByPattern(string $key, string $prefix, string $patternName)
 * @method static void createPaginatedSitemaps(string $baseKey, int $totalItems, string|null $lastModified = null)
 * @method static \Botble\Theme\Supports\SiteMapManager registerMonthlyArchives(string $prefix, int $yearsBack = 1, int $maxPagesPerMonth = 5)
 *
 * @see \Botble\Theme\Supports\SiteMapManager
 */
class SiteMapManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SiteMapManagerSupport::class;
    }
}
