<?php

namespace Botble\Gallery\Facades;

use Botble\Gallery\GallerySupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static static registerModule(array|string $model)
 * @method static array getSupportedModules()
 * @method static static removeModule(array|string $model)
 * @method static void saveGallery(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model|null $data)
 * @method static bool deleteGallery(\Illuminate\Database\Eloquent\Model|null $data)
 * @method static static registerAssets()
 * @method static string|null getGalleriesPageUrl()
 * @method static bool isEnabledGalleryImagesMetaBox()
 * @method static static disableGalleryImagesMetaBox()
 *
 * @see \Botble\Gallery\GallerySupport
 */
class Gallery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GallerySupport::class;
    }
}
