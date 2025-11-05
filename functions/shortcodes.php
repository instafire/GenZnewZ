<?php

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Models\BaseQueryBuilder;
use Botble\Blog\Models\Category;
use Botble\Gallery\Facades\Gallery;
use Botble\Shortcode\Compilers\Shortcode;
use Botble\Shortcode\Facades\Shortcode as ShortcodeFacade;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Shortcode\ShortcodeField;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

app()->booted(function (): void {
    ThemeSupport::registerGoogleMapsShortcode();
    ThemeSupport::registerYoutubeShortcode();

    if (is_plugin_active('blog')) {
        add_shortcode('featured-posts', __('Featured posts'), __('Featured posts'), function (Shortcode $shortcode) {
            return Theme::partial('short-codes.featured-posts', compact('shortcode'));
        });

        shortcode()->setAdminConfig('featured-posts', function (array $attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add('limit', 'number', [
                    'label' => __('Limit'),
                    'value' => Arr::get($attributes, 'limit', 8),
                ]);
        });

        ShortcodeFacade::registerLoadingState('featured-posts', Theme::getThemeNamespace('partials.short-codes.featured-posts-skeleton'));
        ShortcodeFacade::setPreviewImage('featured-posts', Theme::asset()->url('images/ui-blocks/featured-posts.png'));

        add_shortcode('category-posts', __('Category posts'), __('Category posts'), function (Shortcode $shortcode) {
            $categoryIds = $shortcode->category_ids;

            if ($categoryIds) {
                $categoryIds = ShortcodeField::parseIds($categoryIds);

                $categories = Category::query()
                    ->whereIn('id', $categoryIds)
                    ->wherePublished()
                    ->get();
            } else {
                $categories = get_all_categories([
                    'categories.status' => BaseStatusEnum::PUBLISHED,
                    'categories.parent_id' => 0,
                    'is_featured' => 1,
                ]);
            }

            return Theme::partial('short-codes.category-posts', compact('categories', 'shortcode'));
        });

        shortcode()->setAdminConfig('category-posts', function (array $attributes) {
            $categories = Category::query()
                ->wherePublished()
                ->pluck('name', 'id')
                ->all();

            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'category_ids[]',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Select categories'))
                        ->choices($categories)
                        ->when(Arr::get($attributes, 'category_ids'), function (SelectFieldOption $option, $categoriesIds): void {
                            $option->selected(explode(',', $categoriesIds));
                        })
                        ->multiple()
                        ->searchable()
                        ->helperText(__('Leave categories empty if you want to show posts from all categories.'))
                );
        });

        ShortcodeFacade::registerLoadingState('category-posts', Theme::getThemeNamespace('partials.short-codes.category-posts-skeleton'));
        ShortcodeFacade::setPreviewImage('category-posts', Theme::asset()->url('images/ui-blocks/category-posts.png'));

        add_shortcode(
            'featured-posts-slider',
            __('Featured posts slider'),
            __('Featured posts slider'),
            function (Shortcode $shortcode) {
                $posts = get_featured_posts($shortcode->limit ? (int) $shortcode->limit : 5);

                $version = '1.0.1';
                $dist = asset('vendor/core/plugins/simple-slider');

                Theme::asset()
                    ->container('footer')
                    ->usePath(false)
                    ->add(
                        'simple-slider-owl-carousel-css',
                        $dist . '/libraries/owl-carousel/owl.carousel.css',
                        [],
                        [],
                        $version
                    )
                    ->add('simple-slider-css', $dist . '/css/simple-slider.css', [], [], $version)
                    ->add(
                        'simple-slider-owl-carousel-js',
                        $dist . '/libraries/owl-carousel/owl.carousel.js',
                        ['jquery'],
                        [],
                        $version
                    )
                    ->add('simple-slider-js', $dist . '/js/simple-slider.js', ['jquery'], [], $version);

                return Theme::partial('short-codes.featured-posts-slider', compact('posts', 'shortcode'));
            }
        );

        shortcode()->setAdminConfig('featured-posts-slider', function (array $attributes, ?string $content) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add('limit', 'number', [
                    'label' => __('Limit'),
                    'value' => Arr::get($attributes, 'limit', 5),
                ])
                ->add('max_height', 'number', [
                    'label' => __('Max height (px)'),
                    'value' => Arr::get($attributes, 'max_height', 400),
                ])
                ->add('is_autoplay', 'select', [
                    'label' => __('Is autoplay?'),
                    'value' => Arr::get($attributes, 'is_autoplay', 'yes'),
                    'choices' => [
                        'no' => trans('core/base::base.no'),
                        'yes' => trans('core/base::base.yes'),
                    ],
                ])
                ->add('is_infinite', 'select', [
                    'label' => __('Loop?'),
                    'value' => Arr::get($attributes, 'is_infinite', 'yes'),
                    'choices' => [
                        'no' => trans('core/base::base.no'),
                        'yes' => trans('core/base::base.yes'),
                    ],
                ])
                ->add('autoplay_speed', 'customSelect', [
                    'label' => __('Autoplay speed (if autoplay enabled)'),
                    'value' => Arr::get($attributes, 'autoplay_speed', 7000),
                    'choices' => theme_get_autoplay_speed_options(),
                ]);
        });

        ShortcodeFacade::registerLoadingState('featured-posts-slider', Theme::getThemeNamespace('partials.short-codes.featured-posts-slider-skeleton'));
        ShortcodeFacade::setPreviewImage('featured-posts-slider', Theme::asset()->url('images/ui-blocks/featured-posts-slider.png'));

        add_shortcode(
            'recent-posts',
            __('Recent posts'),
            __('Recent posts'),
            function (Shortcode $shortcode) {
                $posts = get_latest_posts(7, [], ['slugable']);

                if ($posts->isEmpty()) {
                    return null;
                }

                $withSidebar = ($shortcode->with_sidebar ?: 'yes') === 'yes';

                return Theme::partial('short-codes.recent-posts', [
                    'title' => $shortcode->title,
                    'withSidebar' => $withSidebar,
                    'posts' => $posts,
                    'shortcode' => $shortcode,
                ]);
            }
        );

        shortcode()->setAdminConfig('recent-posts', function (array $attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add('title', 'text', [
                    'label' => __('Title'),
                    'value' => Arr::get($attributes, 'title'),
                ])
                ->add('with_sidebar', 'select', [
                    'label' => __('With primary sidebar?'),
                    'value' => Arr::get($attributes, 'with_sidebar', 'yes'),
                    'choices' => [
                        'yes' => trans('core/base::base.yes'),
                        'no' => trans('core/base::base.no'),
                    ],
                ]);
        });

        ShortcodeFacade::registerLoadingState('recent-posts', Theme::getThemeNamespace('partials.short-codes.recent-posts-skeleton'));
        ShortcodeFacade::setPreviewImage('recent-posts', Theme::asset()->url('images/ui-blocks/recent-posts.png'));

        add_shortcode(
            'featured-categories-posts',
            __('Featured categories posts'),
            __('Featured categories posts'),
            function (Shortcode $shortcode) {
                $with = [
                    'slugable',
                    'posts' => function (BelongsToMany|BaseQueryBuilder $query): void {
                        $query
                            ->wherePublished()->latest();
                    },
                    'posts.slugable',
                ];

                if (is_plugin_active('language-advanced')) {
                    $with[] = 'posts.translations';
                }

                $posts = collect();

                $categoryIds = ShortcodeField::parseIds($shortcode->category_id);

                if ($categoryIds) {
                    $categories = Category::query()
                        ->with($with)
                        ->wherePublished()
                        ->whereIn('id', $categoryIds)
                        ->select([
                            'id',
                            'name',
                            'description',
                            'icon',
                        ])
                        ->get();
                } else {
                    $categories = get_featured_categories(2, $with);
                }

                foreach ($categories as $category) {
                    $posts = $posts->merge($category->posts->take(3));
                }

                $posts = $posts->sortByDesc('created_at');

                if ($posts->isEmpty()) {
                    return null;
                }

                $withSidebar = ($shortcode->with_sidebar ?: 'yes') === 'yes';

                return Theme::partial(
                    'short-codes.featured-categories-posts',
                    [
                        'title' => $shortcode->title,
                        'withSidebar' => $withSidebar,
                        'posts' => $posts,
                        'shortcode' => $shortcode,
                    ]
                );
            }
        );

        shortcode()->setAdminConfig('featured-categories-posts', function (array $attributes) {
            $categories = Category::query()->wherePublished()->pluck('name', 'id')->all();
            $categoryIds = Arr::get($attributes, 'category_id');

            if (! is_array($categoryIds)) {
                $categoryIds = $categoryIds ? explode(',', $categoryIds) : null;
            }

            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add('title', 'text', [
                    'label' => __('Title'),
                    'value' => Arr::get($attributes, 'title'),
                ])
                ->add(
                    'category_id',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Choose categories'))
                        ->choices($categories)
                        ->selected($categoryIds)
                        ->searchable()
                        ->multiple(),
                )
                ->add('with_sidebar', 'select', [
                    'label' => __('With primary sidebar?'),
                    'value' => Arr::get($attributes, 'with_sidebar', 'yes'),
                    'choices' => [
                        'yes' => trans('core/base::base.yes'),
                        'no' => trans('core/base::base.no'),
                    ],
                ]);
        });

        ShortcodeFacade::registerLoadingState('featured-categories-posts', Theme::getThemeNamespace('partials.short-codes.featured-categories-posts-skeleton'));
        ShortcodeFacade::setPreviewImage('featured-categories-posts', Theme::asset()->url('images/ui-blocks/featured-categories-posts.png'));
    }

    if (is_plugin_active('gallery')) {
        add_shortcode('all-galleries', __('All Galleries'), __('All Galleries'), function (Shortcode $shortcode) {
            Gallery::registerAssets();

            $galleries = get_galleries((int) $shortcode->limit ?: 8);

            return Theme::partial('short-codes.all-galleries', compact('galleries'));
        });

        shortcode()->setAdminConfig('all-galleries', function (array $attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add('limit', 'number', [
                    'label' => __('Limit'),
                    'value' => Arr::get($attributes, 'limit', 8),
                ]);
        });

        ShortcodeFacade::registerLoadingState('all-galleries', Theme::getThemeNamespace('partials.short-codes.all-galleries-skeleton'));
        ShortcodeFacade::setPreviewImage('all-galleries', Theme::asset()->url('images/ui-blocks/all-galleries.png'));
    }

    if (is_plugin_active('contact')) {
        ShortcodeFacade::setPreviewImage('contact-form', Theme::asset()->url('images/ui-blocks/contact-form.png'));
    }

    if (is_plugin_active('simple-slider')) {
        ShortcodeFacade::setPreviewImage('simple-slider', Theme::asset()->url('images/ui-blocks/simple-slider.png'));
        ShortcodeFacade::registerLoadingState('simple-slider', Theme::getThemeNamespace('partials.short-codes.sliders-skeleton'));
        
        add_filter(SIMPLE_SLIDER_VIEW_TEMPLATE, function () {
            return Theme::getThemeNamespace() . '::partials.short-codes.sliders';
        }, 120);

        add_filter(SHORTCODE_REGISTER_CONTENT_IN_ADMIN, function (?string $data, string $key, array $attributes) {
            if ($key == 'simple-slider') {
                $form = ShortcodeForm::createFromArray($attributes)
                    ->withLazyLoading()
                    ->add('max_height', 'number', [
                        'label' => __('Max height (px)'),
                        'value' => Arr::get($attributes, 'max_height', 400),
                    ])
                    ->add('is_autoplay', 'select', [
                        'label' => __('Is autoplay?'),
                        'value' => Arr::get($attributes, 'is_autoplay', 'yes'),
                        'choices' => [
                            'no' => trans('core/base::base.no'),
                            'yes' => trans('core/base::base.yes'),
                        ],
                    ])
                    ->add('is_infinite', 'select', [
                        'label' => __('Loop?'),
                        'value' => Arr::get($attributes, 'is_infinite', 'yes'),
                        'choices' => [
                            'no' => trans('core/base::base.no'),
                            'yes' => trans('core/base::base.yes'),
                        ],
                    ])
                    ->add('autoplay_speed', 'customSelect', [
                        'label' => __('Autoplay speed (if autoplay enabled)'),
                        'value' => Arr::get($attributes, 'autoplay_speed', 7000),
                        'choices' => theme_get_autoplay_speed_options(),
                    ]);

                return $data . $form->renderForm(['wrapper' => false]);
            }

            return $data;
        }, 50, 3);
    }
});
