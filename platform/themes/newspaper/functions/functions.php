<?php

use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Blog\Forms\PostForm;
use Botble\Blog\Supports\PostFormat;
use Botble\Gallery\Facades\Gallery;
use Botble\Media\Facades\RvMedia;
use Botble\Menu\Facades\Menu;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Illuminate\Support\Facades\Auth;
use Theme\Newspaper\Models\Member;

// Register Member Authentication
app()->booted(function () {
    // Register view namespace
    $view = app('view');
    $view->addNamespace('theme.newspaper', platform_path('themes/newspaper/views'));
    
    // Register member auth config
    config(['auth.guards.member' => [
        'driver' => 'session',
        'provider' => 'members',
    ]]);
    
    config(['auth.providers.members' => [
        'driver' => 'eloquent',
        'model' => Member::class,
    ]]);
});

// Handle custom page templates (register, login, contact, terms, privacy, cookies)
add_filter(BASE_FILTER_PUBLIC_SINGLE_DATA, function ($data) {
    // Handle case where data is already processed (array with view info)
    if (is_array($data) && isset($data['data']['page'])) {
        $page = $data['data']['page'];
        $customTemplates = ['register', 'login', 'contact', 'terms', 'privacy', 'cookies'];
        if ($page && in_array($page->template, $customTemplates)) {
            $data['view'] = 'templates.' . $page->template;
            return $data;
        }
        return $data;
    }
    
    // Handle case where data is still a Slug object
    if ($data instanceof \Botble\Slug\Models\Slug) {
        $slug = $data;
        if ($slug->reference_type === 'Botble\Page\Models\Page') {
            $page = \Botble\Page\Models\Page::find($slug->reference_id);
            $customTemplates = ['register', 'login', 'contact', 'terms', 'privacy', 'cookies'];
            if ($page && in_array($page->template, $customTemplates)) {
                return [
                    'view' => 'templates.' . $page->template,
                    'default_view' => 'packages/page::themes.page',
                    'data' => compact('page'),
                    'slug' => $page->slug,
                ];
            }
        }
    }
    
    return $data;
}, 128);

app()->booted(function (): void {
    ThemeSupport::registerToastNotification();
    ThemeSupport::registerPreloader();
    ThemeSupport::registerSocialSharing();
    ThemeSupport::registerDateFormatOption();

    register_page_template([
        'homepage' => __('Homepage'),
        'videos' => __('Videos Page'),
        'register' => __('Register'),
        'login' => __('Login'),
        'contact' => __('Contact Us'),
        'terms' => __('Terms of Use'),
        'privacy' => __('Privacy Policy'),
        'cookies' => __('Cookie Policy'),
    ]);
    
    // Register custom template for Videos category
    add_filter('theme_category_template', function($template, $category) {
        if ($category->slug === 'videos') {
            return 'category-videos';
        }
        return $template;
    }, 10, 2);
    
    // Use video template for video format posts
    add_filter('theme_post_template', function($template, $post) {
        if ($post->format_type === 'video') {
            return 'post-video';
        }
        return $template;
    }, 10, 2);

    register_sidebar([
        'id' => 'footer_sidebar',
        'name' => __('Footer sidebar'),
        'description' => __('Area for footer widgets'),
    ]);

    Menu::addMenuLocation('second-menu', __('Second menu'))
        ->addMenuLocation('header-menu', __('Header Navigation'));

    RvMedia::addSize('featured', 560, 380)
        ->addSize('medium', 540, 360)
        // The article hero asks for `large`. It was never registered, so RvMedia
        // silently fell back to the full-size original - a ~340 KB Pexels file
        // standing in for a 900x600 slot, which is the page's LCP element.
        // Registering it gives the hero a real, small derivative.
        ->addSize('large', 1024, 683);

    if (is_plugin_active('blog')) {
        PostFormat::registerPostFormat([
            'video' => [
                'key' => 'video',
                'icon' => 'fa fa-camera',
                'name' => __('Video'),
            ],
            'text-only' => [
                'key' => 'text-only',
                'icon' => 'fa fa-align-left',
                'name' => __('Text Only'),
            ],
        ]);

        PostForm::extend(function (PostForm $form) {
            return $form
                ->addAfter(
                    'content',
                    'video_link',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('YouTube Video URL'))
                        ->placeholder('Ex: https://www.youtube.com/watch?v=FN7ALfpGxiI')
                        ->metadata()
                        ->toArray()
                )
                ->addAfter(
                    'image',
                    'display_featured_image_at_the_top',
                    OnOffField::class,
                    OnOffFieldOption::make()
                        ->label(__('Display the featured image at the start of the post'))
                        ->metadata()
                        ->toArray()
                );
        });
    }

    if (! function_exists('theme_get_autoplay_speed_options')) {
        function theme_get_autoplay_speed_options(): array
        {
            $options = [2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 12000, 15000];

            return array_combine($options, $options);
        }
    }

    // Gallery assets are registered conditionally by the plugin itself:
    // galleries.blade.php (index), GalleryService (single gallery pages) and
    // the all-galleries shortcode. Do NOT register them globally — loading
    // masonry/lightgallery/imagesloaded (~46 KB JS) on every page that never
    // uses them was a measured mobile-TBT regression (2026-09).
});
