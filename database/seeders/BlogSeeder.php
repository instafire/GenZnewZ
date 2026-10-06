<?php

namespace Database\Seeders;

use Botble\Base\Models\MetaBox;
use Botble\Base\Supports\BaseSeeder;
use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Blog\Models\Tag;
use Botble\Language\Facades\Language;
use Botble\Language\Models\LanguageMeta;
use Botble\Slug\Facades\SlugHelper;

class BlogSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('news');

        $posts = Post::query()->get();

        $index = 1;
        foreach ($posts as $post) {
            /**
             * @var Post $post
             */
            $post->image = $this->filePath('news/' . $index . '.jpg');
            $post->views = rand(100, 2500);
            $post->save();

            SlugHelper::createSlug($post);

            if ($index == $posts->count() / 2) {
                $index = 1;
            }

            $index++;
        }

        $posts = LanguageMeta::query()->where('reference_type', Post::class)
            ->where('lang_meta_code', '!=', Language::getDefaultLocaleCode())
            ->get();

        $postMap = Post::query()
            ->whereIn('id', $posts->pluck('reference_id')->unique()->values()->all())
            ->get()
            ->keyBy('id');

        foreach ($posts as $item) {
            $post = $postMap->get($item->reference_id);

            if (! $post) {
                continue;
            }

            $originalId = LanguageMeta::query()->where('lang_meta_origin', $item->lang_meta_origin)
                ->where('lang_meta_code', Language::getDefaultLocaleCode())
                ->value('reference_id');

            if (! $originalId) {
                continue;
            }

            $post->delete();

            $item->delete();
        }

        $categories = LanguageMeta::query()->where('reference_type', Category::class)
            ->where('lang_meta_code', '!=', Language::getDefaultLocaleCode())
            ->get();

        $categoryMap = Category::query()
            ->whereIn('id', $categories->pluck('reference_id')->unique()->values()->all())
            ->get()
            ->keyBy('id');

        foreach ($categories as $item) {
            $category = $categoryMap->get($item->reference_id);

            if (! $category) {
                continue;
            }

            $originalId = LanguageMeta::query()->where('lang_meta_origin', $item->lang_meta_origin)
                ->where('lang_meta_code', Language::getDefaultLocaleCode())
                ->value('reference_id');

            if (! $originalId) {
                continue;
            }

            $category->delete();

            $item->delete();
        }

        $tags = LanguageMeta::query()->where('reference_type', Tag::class)
            ->where('lang_meta_code', '!=', Language::getDefaultLocaleCode())
            ->get();

        $tagMap = Tag::query()
            ->whereIn('id', $tags->pluck('reference_id')->unique()->values()->all())
            ->get()
            ->keyBy('id');

        foreach ($tags as $item) {
            $tag = $tagMap->get($item->reference_id);

            if (! $tag) {
                continue;
            }

            $originalId = LanguageMeta::query()->where('lang_meta_origin', $item->lang_meta_origin)
                ->where('lang_meta_code', Language::getDefaultLocaleCode())
                ->value('reference_id');

            if (! $originalId) {
                continue;
            }

            $tag->delete();

            $item->delete();
        }

        MetaBox::query()
            ->toBase()
            ->where('meta_key', 'video_link')
            ->where('meta_value', '["https:\/\/www.youtube.com\/embed\/fpGO2eFpN44"]')
            ->update(['meta_value' => '["https:\/\/www.youtube.com\/embed\/09R8_2nJtjg"]']);

        MetaBox::query()
            ->toBase()
            ->where('meta_key', 'video_link')
            ->where('meta_value', '["https:\/\/www.youtube.com\/embed\/XY77nTAuiK0"]')
            ->update(['meta_value' => '["https:\/\/www.youtube.com\/embed\/aJOTlE1K90k"]']);

        MetaBox::query()
            ->toBase()
            ->where('meta_key', 'video_link')
            ->where('meta_value', '["https:\/\/www.youtube.com\/embed\/JrdfCVff2KE"]')
            ->update(['meta_value' => '["https:\/\/www.youtube.com\/embed\/aJOTlE1K90k"]']);
    }
}
