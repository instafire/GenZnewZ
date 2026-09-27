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

        foreach ($posts as $item) {
            $post = Post::query()->find($item->reference_id);

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

        foreach ($categories as $item) {
            $category = Category::query()->find($item->reference_id);

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

        foreach ($tags as $item) {
            $tag = Tag::query()->find($item->reference_id);

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
