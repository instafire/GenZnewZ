<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Widget\Database\Traits\HasWidgetSeeder;

class WidgetSeeder extends BaseSeeder
{
    use HasWidgetSeeder;

    public function run(): void
    {
        $data = [
            [
                'widget_id' => 'TextWidget',
                'sidebar_id' => 'footer_sidebar',
                'position' => 0,
                'data' => [
                    'id' => 'TextWidget',
                    'name' => 'About us',
                    'content' => 'Donec nec justo eget felis facilisis fermentum. Aliquam porttitor mauris sit amet orci. Aenean dignissim pellentesque felis.\r\n\r\nMorbi in sem quis dui placerat ornare. Pellentesque odio nisi, euismod in, pharetra a, ultricies in, diam. Sed arcu.',
                ],
            ],
            [
                'widget_id' => 'RecentPostsWidget',
                'sidebar_id' => 'footer_sidebar',
                'position' => 1,
                'data' => [
                    'id' => 'RecentPostsWidget',
                    'name' => 'Recent Posts',
                    'number_display' => 5,
                ],
            ],
            [
                'widget_id' => 'CustomMenuWidget',
                'sidebar_id' => 'footer_sidebar',
                'position' => 2,
                'data' => [
                    'id' => 'CustomMenuWidget',
                    'name' => 'Featured categories',
                    'menu_id' => 'featured-categories',
                ],
            ],
            [
                'widget_id' => 'FacebookWidget',
                'sidebar_id' => 'footer_sidebar',
                'position' => 3,
                'data' => [
                    'id' => 'FacebookWidget',
                    'name' => 'Fanpage Facebook',
                    'facebook_name' => 'Archi Elite',
                    'facebook_url' => 'https://www.facebook.com/archielite.tech',
                ],
            ],
            [
                'widget_id' => 'RecentPostsWidget',
                'sidebar_id' => 'primary_sidebar',
                'position' => 0,
                'data' => [
                    'id' => 'RecentPostsWidget',
                    'name' => 'Recent Posts',
                    'number_display' => 5,
                ],
            ],
            [
                'widget_id' => 'AdsWidget',
                'sidebar_id' => 'primary_sidebar',
                'position' => 1,
                'data' => [
                    'id' => 'AdsWidget',
                    'name' => null,
                    'image_link' => '#',
                    'image_new_tab' => '1',
                    'image_url' => $this->filePath('banners/2.jpg'),
                ],
            ],
            [
                'widget_id' => 'PopularPostsWidget',
                'sidebar_id' => 'primary_sidebar',
                'position' => 2,
                'data' => [
                    'id' => 'PopularPostsWidget',
                    'name' => 'Popular Posts',
                    'number_display' => 5,
                ],
            ],
            [
                'widget_id' => 'VideoPostsWidget',
                'sidebar_id' => 'primary_sidebar',
                'position' => 3,
                'data' => [
                    'id' => 'VideoPostsWidget',
                    'name' => 'Video Posts',
                    'number_display' => 5,
                ],
            ],
            [
                'widget_id' => 'AdsWidget',
                'sidebar_id' => 'primary_sidebar',
                'position' => 4,
                'data' => [
                    'id' => 'AdsWidget',
                    'name' => null,
                    'image_link' => '#',
                    'image_new_tab' => '1',
                    'image_url' => $this->filePath('banners/2.jpg'),
                ],
            ],
        ];

        $this->createWidgets($data);
    }
}
