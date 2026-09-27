<?php

namespace Botble\Gallery\Providers;

use Botble\Base\Facades\AdminHelper;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\Html;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Models\BaseModel;
use Botble\Base\Supports\ServiceProvider;
use Botble\Gallery\Facades\Gallery;
use Botble\Gallery\Models\Gallery as GalleryModel;
use Botble\Gallery\Services\GalleryService;
use Botble\Page\Models\Page;
use Botble\Page\Tables\PageTable;
use Botble\Shortcode\Compilers\Shortcode;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Slug\Models\Slug;
use Botble\Theme\Events\RenderingThemeOptionSettings;
use Botble\Theme\Facades\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action(BASE_ACTION_META_BOXES, [$this, 'addGalleryBox'], 13, 2);

        add_filter('facebook_comment_html', [$this, 'renderGalleryFacebookComments'], 10, 2);

        if (function_exists('shortcode')) {
            add_shortcode(
                'gallery',
                trans('plugins/gallery::gallery.gallery_images'),
                trans('plugins/gallery::gallery.add_gallery_short_code'),
                [$this, 'render']
            );

            shortcode()->setAdminConfig('gallery', function (array $attributes) {
                $galleries = GalleryModel::query()
                    ->pluck('name', 'id')
                    ->all();

                $galleryIds = explode(',', Arr::get($attributes, 'gallery_ids', ''));

                return ShortcodeForm::createFromArray($attributes)
                    ->withLazyLoading()
                    ->add('title', TextField::class, [
                        'label' => __('Title'),
                    ])
                    ->add(
                        'limit',
                        NumberField::class,
                        NumberFieldOption::make()
                            ->label(__('Limit'))
                            ->helperText(__('Number of galleries to show. Set to 0 or leave it empty to show all. It will be overridden if you select galleries below.'))
                            ->defaultValue(5)
                    )
                    ->add(
                        'gallery_ids',
                        SelectField::class,
                        SelectFieldOption::make()
                            ->label(__('Galleries'))
                            ->choices($galleries)
                            ->selected($galleryIds)
                            ->searchable()
                            ->multiple()
                    );
            });

            shortcode()->registerLoadingState('gallery', 'plugins/gallery::shortcodes.gallery-skeleton');
        }

        add_filter(BASE_FILTER_PUBLIC_SINGLE_DATA, [$this, 'handleSingleView'], 11);

        PageTable::beforeRendering(function (): void {
            add_filter(PAGE_FILTER_PAGE_NAME_IN_ADMIN_LIST, [$this, 'addAdditionNameToPageName'], 147, 2);
        });

        if (defined('PAGE_MODULE_SCREEN_NAME')) {
            add_filter(PAGE_FILTER_FRONT_PAGE_CONTENT, [$this, 'renderGalleriesPage'], 2, 2);
        }

        $this->app['events']->listen(RenderingThemeOptionSettings::class, function (): void {
            add_action(RENDERING_THEME_OPTIONS_PAGE, [$this, 'addThemeOptions'], 11);
        });
    }

    public function addGalleryBox(string $context, array|string|Model|null $object = null): void
    {
        if (
            (Gallery::isEnabledGalleryImagesMetaBox() || $object instanceof GalleryModel) &&
            AdminHelper::isInAdmin(true) &&
            $object instanceof BaseModel &&
            in_array($object::class, Gallery::getSupportedModules()) &&
            $context == 'advanced'
        ) {
            Assets::addStylesDirectly(['vendor/core/plugins/gallery/css/admin-gallery.css'])
                ->addScriptsDirectly(['vendor/core/plugins/gallery/js/gallery-admin.js'])
                ->addScripts(['sortable']);

            MetaBox::addMetaBox(
                'gallery_wrap',
                trans('plugins/gallery::gallery.gallery_box'),
                [$this, 'galleryMetaField'],
                $object::class,
                $context
            );
        }
    }

    public function galleryMetaField(): string
    {
        $value = null;
        $args = func_get_args();

        if ($args[0] && $args[0]->id) {
            $value = gallery_meta_data($args[0]);
        }

        return view('plugins/gallery::gallery-box', compact('value'))->render();
    }

    public function render(Shortcode $shortcode): string
    {
        $limit = (int) $shortcode->limit;

        $galleryIds = shortcode()->fields()->parseIds($shortcode->gallery_ids);

        $galleries = GalleryModel::query()
            ->with(['slugable', 'user'])
            ->wherePublished()
            ->when($limit > 0 && ! $galleryIds, fn ($query) => $query->limit($limit))
            ->when($galleryIds, fn ($query) => $query->whereIn('id', $galleryIds))
            ->oldest('order')->latest()
            ->get();

        $view = apply_filters('galleries_box_template_view', 'plugins/gallery::shortcodes.gallery');

        return view($view, compact('shortcode', 'galleries'))->render();
    }

    public function handleSingleView(Slug|array $slug): Slug|array
    {
        return (new GalleryService())->handleFrontRoutes($slug);
    }

    public function renderGalleriesPage(?string $content, Page $page): ?string
    {
        if ($page->getKey() == theme_option('galleries_page_id')) {
            $view = 'plugins/gallery::themes.galleries';

            if (view()->exists($viewPath = Theme::getThemeNamespace() . '::views.galleries')) {
                $view = $viewPath;
            }

            return view($view, ['galleries' => get_galleries(-1)])->render();
        }

        return $content;
    }

    public function addAdditionNameToPageName(?string $name, Page $page): ?string
    {
        if ($page->getKey() == theme_option('galleries_page_id')) {
            $subTitle = Html::tag(
                'span',
                trans('plugins/gallery::gallery.galleries_page'),
                ['class' => 'additional-page-name']
            )
                ->toHtml();

            if (Str::contains($name, ' —')) {
                return $name . ', ' . $subTitle;
            }

            return $name . ' —' . $subTitle;
        }

        return $name;
    }

    public function addThemeOptions(): void
    {
        $pages = Page::query()->wherePublished()->pluck('name', 'id')->all();

        if (! empty($pages)) {
            theme_option()
                ->setField([
                    'id' => 'galleries_page_id',
                    'section_id' => 'opt-text-subsection-page',
                    'type' => 'customSelect',
                    'label' => trans('plugins/gallery::gallery.galleries_page'),
                    'attributes' => [
                        'name' => 'galleries_page_id',
                        'list' => ['' => trans('core/base::forms.select_placeholder')] + $pages,
                        'value' => '',
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ]);
        }
    }

    public function renderGalleryFacebookComments(string $html, ?object $object = null): string
    {
        if ($object instanceof GalleryModel && theme_option('facebook_comment_enabled_in_gallery', 'no') === 'yes') {
            return view('packages/theme::partials.facebook-comments')->render();
        }

        return $html;
    }
}
