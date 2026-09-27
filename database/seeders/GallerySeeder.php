<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Gallery\Database\Traits\HasGallerySeeder;
use Botble\Gallery\Models\Gallery;
use Botble\Slug\Models\Slug;

class GallerySeeder extends BaseSeeder
{
    use HasGallerySeeder;

    public function run(): void
    {
        Slug::query()->where('reference_type', Gallery::class)->delete();

        $this->uploadFiles('galleries');

        $galleries = [
            [
                'name' => 'Perfect',
            ],
            [
                'name' => 'New Day',
            ],
            [
                'name' => 'Happy Day',
            ],
            [
                'name' => 'Nature',
            ],
            [
                'name' => 'Morning',
            ],
            [
                'name' => 'Photography',
            ],
        ];

        $faker = $this->fake();

        $images = [];
        for ($i = 0; $i < 10; $i++) {
            $images[] = [
                'img' => $this->filePath('galleries/' . ($i + 1) . '.jpg'),
                'description' => $faker->text(150),
            ];
        }

        foreach ($galleries as $index => &$item) {
            $item['image'] = $this->filePath('galleries/' . ($index + 1) . '.jpg');
            $item['is_featured'] = true;
        }

        $this->createGalleries($galleries, $images);
    }
}
