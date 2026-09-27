<?php

namespace Botble\Gallery;

use Botble\Base\Facades\BaseHelper;
use Botble\Gallery\Models\GalleryMeta;
use Botble\Language\Facades\Language;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Botble\Page\Models\Page;
use Botble\Theme\Facades\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class GallerySupport
{
    public function registerModule(string|array $model): static
    {
        if (! is_array($model)) {
            $model = [$model];
        }

        config([
            'plugins.gallery.general.supported' => array_merge($this->getSupportedModules(), $model),
        ]);

        return $this;
    }

    public function getSupportedModules(): array
    {
        return config('plugins.gallery.general.supported', []);
    }

    public function removeModule(string|array $model): static
    {
        $models = $this->getSupportedModules();

        foreach ($this->getSupportedModules() as $key => $item) {
            if ($item == $model) {
                Arr::forget($models, $key);

                break;
            }
        }

        config(['plugins.gallery.general.supported' => $models]);

        return $this;
    }

    public function saveGallery(Request $request, ?Model $data): void
    {
        if ($data && in_array($data::class, $this->getSupportedModules()) && $request->has('gallery')) {
            $meta = GalleryMeta::query()
                ->where([
                    'reference_id' => $data->getKey(),
                    'reference_type' => $data::class,
                ])
                ->first();

            $gallery = (string) $request->input('gallery');

            if (
                defined('LANGUAGE_MODULE_SCREEN_NAME') &&
                ($currentLanguage = Language::getRefLang()) &&
                $currentLanguage != Language::getDefaultLocaleCode()
            ) {
                $formRequest = new Request();
                $formRequest->replace([
                    'language' => $request->input('language'),
                    Language::refLangKey() => $currentLanguage,
                    'images' => $gallery,
                ]);

                if (! $meta) {
                    $meta = new GalleryMeta();
                    $meta->reference_id = $data->getKey();
                    $meta->reference_type = $data::class;
                    $meta->images = json_decode($gallery, true);
                    $meta->save();
                }

                LanguageAdvancedManager::save($meta, $formRequest);
            } else {
                if (empty($meta->images)) {
                    $this->deleteGallery($data);
                }

                if (! $meta) {
                    $meta = new GalleryMeta();
                    $meta->reference_id = $data->getKey();
                    $meta->reference_type = $data::class;
                }

                $meta->images = json_decode($gallery, true);
                $meta->save();
            }
        }
    }

    public function deleteGallery(?Model $data): bool
    {
        if (in_array($data::class, $this->getSupportedModules())) {
            GalleryMeta::query()
                ->where([
                    'reference_id' => $data->getKey(),
                    'reference_type' => $data::class,
                ])
                ->delete();
        }

        return true;
    }

    public function registerAssets(): static
    {
        Theme::asset()
            ->usePath(false)
            ->add('lightgallery-css', asset('vendor/core/plugins/gallery/libraries/lightgallery/css/lightgallery.min.css'), [], [], '1.0.0')
            ->add('gallery-css', asset('vendor/core/plugins/gallery/css/gallery.css'), [], [], '1.0.0');

        Theme::asset()
            ->container('footer')
            ->usePath(false)
            ->add(
                'lightgallery-js',
                asset('vendor/core/plugins/gallery/libraries/lightgallery/js/lightgallery.min.js'),
                ['jquery'],
                ['defer'],
                '1.0.0'
            )
            ->add(
                'imagesloaded',
                asset('vendor/core/plugins/gallery/js/imagesloaded.pkgd.min.js'),
                ['jquery'],
                ['defer'],
                '1.0.0'
            )
            ->add('masonry', asset('vendor/core/plugins/gallery/js/masonry.pkgd.min.js'), ['jquery'], [], '1.0.0')
            ->add('gallery-js', asset('vendor/core/plugins/gallery/js/gallery.js'), ['jquery'], [], '1.0.0');

        return $this;
    }

    public function getGalleriesPageUrl(): ?string
    {
        $pageId = theme_option('galleries_page_id');

        $defaultURL = Route::has('public.galleries') ? route('public.galleries') : BaseHelper::getHomepageUrl();

        if (! $pageId) {
            return $defaultURL;
        }

        $page = $this->getPage($pageId);

        return $page ? $page->url : $defaultURL;
    }

    protected function getPage(int|string|null $pageId): Model|Page|null
    {
        if (! $pageId) {
            return null;
        }

        return Page::query()
            ->where('id', $pageId)
            ->wherePublished()
            ->select(['id', 'name'])
            ->with(['slugable'])
            ->first();
    }

    public function isEnabledGalleryImagesMetaBox(): bool
    {
        return config('plugins.gallery.general.enable_gallery_images_meta_box', true);
    }

    public function disableGalleryImagesMetaBox(): static
    {
        config()->set('plugins.gallery.general.enable_gallery_images_meta_box', false);

        return $this;
    }
}
