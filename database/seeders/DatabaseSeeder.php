<?php

namespace Database\Seeders;

use Botble\ACL\Database\Seeders\UserSeeder;
use Botble\Base\Events\SeederPrepared;
use Botble\Base\Supports\BaseSeeder;
use Botble\Base\Supports\Database;
use Botble\Contact\Database\Seeders\ContactSeeder;
use Botble\Language\Database\Seeders\LanguageSeeder;

class DatabaseSeeder extends BaseSeeder
{
    public function run(): void
    {
        SeederPrepared::dispatch();

        Database::restoreFromPath(base_path('database.sql'));

        $this->command->call('migrate');

        $this->call(LanguageSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(GallerySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ContactSeeder::class);
        $this->call(AuthorSeeder::class);
        $this->call(AdsSeeder::class);
        $this->call(BlogSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(WidgetSeeder::class);
        $this->call(ThemeOptionSeeder::class);
        $this->call(SimpleSliderSeeder::class);
        $this->call(CommentSeeder::class);

        $this->finished();
    }
}
