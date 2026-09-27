<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Blog\Database\Traits\HasBlogSeeder;
use Botble\Blog\Models\Category;
use Botble\Menu\Database\Traits\HasMenuSeeder;
use Botble\Page\Database\Traits\HasPageSeeder;
use Botble\Page\Models\Page;

class MenuSeeder extends BaseSeeder
{
    use HasMenuSeeder;
    use HasPageSeeder;
    use HasBlogSeeder;

    public function run(): void
    {
        $data = [
            [
                'name' => 'Main menu',
                'slug' => 'main-menu',
                'location' => 'main-menu',
                'items' => [
                    [
                        'title' => 'Business',
                        'reference_id' => $this->getCategoryId('Business'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Economy',
                        'reference_id' => $this->getCategoryId('Economy'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Law',
                        'reference_id' => $this->getCategoryId('Law'),
                        'reference_type' => Category::class,
                        'children' => [
                            [
                                'title' => 'Behind the scenes',
                                'reference_id' => $this->getCategoryId('Behind the scenes'),
                                'reference_type' => Category::class,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Discovery',
                        'reference_id' => $this->getCategoryId('Discovery'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Entertainment',
                        'reference_id' => $this->getCategoryId('Entertainment'),
                        'reference_type' => Category::class,
                        'children' => [
                            [
                                'title' => 'Football',
                                'reference_id' => $this->getCategoryId('Football'),
                                'reference_type' => Category::class,
                            ],
                            [
                                'title' => 'Fashion',
                                'reference_id' => $this->getCategoryId('Fashion'),
                                'reference_type' => Category::class,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Hot & Cool',
                        'reference_id' => $this->getCategoryId('Hot & Cool'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Life',
                        'reference_id' => $this->getCategoryId('Life'),
                        'reference_type' => Category::class,
                        'children' => [
                            [
                                'title' => 'Beautify',
                                'reference_id' => $this->getCategoryId('Beautify'),
                                'reference_type' => Category::class,
                            ],
                        ],
                    ],
                    [
                        'title' => 'Sport',
                        'reference_id' => $this->getCategoryId('Sport'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'About us',
                        'reference_id' => $this->getPageId('About us'),
                        'reference_type' => Page::class,
                        'children' => [
                            [
                                'title' => 'Contact',
                                'reference_id' => $this->getPageId('Contact'),
                                'reference_type' => Page::class,
                            ],
                            [
                                'title' => 'Introduction',
                                'reference_id' => $this->getPageId('Introduction'),
                                'reference_type' => Page::class,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Second menu',
                'slug' => 'second-menu',
                'location' => 'second-menu',
                'items' => [
                    [
                        'title' => 'Beautify',
                        'reference_id' => $this->getCategoryId('Beautify'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Love',
                        'reference_id' => $this->getCategoryId('Love'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Other sports',
                        'reference_id' => $this->getCategoryId('Other sports'),
                        'reference_type' => Category::class,
                    ],
                ],
            ],
            [
                'name' => 'Header menu',
                'slug' => 'header-menu',
                'location' => 'header-menu',
                'items' => [
                    [
                        'title' => 'Introduction',
                        'reference_id' => $this->getPageId('Introduction'),
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'About us',
                        'reference_id' => $this->getPageId('About us'),
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'Contact',
                        'reference_id' => $this->getPageId('Contact'),
                        'reference_type' => Page::class,
                    ],
                ],
            ],
            [
                'name' => 'Featured categories',
                'slug' => 'featured-categories',
                'items' => [
                    [
                        'title' => 'Discovery',
                        'reference_id' => $this->getCategoryId('Discovery'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Football',
                        'reference_id' => $this->getCategoryId('Football'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Hot & Cool',
                        'reference_id' => $this->getCategoryId('Hot & Cool'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Other sports',
                        'reference_id' => $this->getCategoryId('Other sports'),
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Video',
                        'reference_id' => $this->getCategoryId('Video'),
                        'reference_type' => Category::class,
                    ],
                ],
            ],
        ];

        $this->createMenus($data);
    }
}
