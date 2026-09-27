<?php

namespace Database\Seeders;

use Botble\Author\Models\Author;
use Botble\Base\Supports\BaseSeeder;
use Botble\Blog\Models\Post;
use Botble\Slug\Facades\SlugHelper;

class AuthorSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('authors');

        Author::query()->truncate();

        $faker = $this->fake();

        for ($i = 0; $i < 10; $i++) {
            $author = Author::query()->create([
                'name' => $faker->name(),
                'email' => $faker->safeEmail(),
                'description' => $faker->text(),
                'avatar' => $this->filePath('authors/' . ($i + 1) . '.jpg'),
            ]);

            $author->save();

            SlugHelper::createSlug($author);
        }

        foreach (Post::query()->get() as $post) {
            $post->author_id = $faker->numberBetween(1, 10);
            $post->author_type = Author::class;
            $post->save();
        }
    }
}
