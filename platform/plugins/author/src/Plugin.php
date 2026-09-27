<?php

namespace Botble\Author;

use Botble\ACL\Models\User;
use Botble\Author\Models\Author;
use Botble\Blog\Models\Post;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Illuminate\Support\Facades\Schema;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        if (is_plugin_active('blog')) {
            Post::query()
                ->toBase()
                ->where('author_type', Author::class)
                ->update([
                    'author_id' => User::query()->value('id'),
                    'author_type' => User::class,
                ]);
        }

        Schema::dropIfExists('authors');
    }
}
