<?php

namespace Tests\Concerns;

use Botble\Blog\Models\Category;
use Botble\Blog\Models\Post;
use Botble\Slug\Models\Slug;

trait CreatesPosts
{
    /**
     * Create a published post with a slug for use in tests.
     */
    protected function createPost(array $attributes = []): Post
    {
        $post = new Post();
        $post->name = $attributes['name'] ?? 'Test Post ' . uniqid();
        $post->description = $attributes['description'] ?? 'Test description';
        $post->content = $attributes['content'] ?? 'Test content';
        $post->status = $attributes['status'] ?? 'published';
        $post->is_featured = $attributes['is_featured'] ?? false;
        $post->format_type = $attributes['format_type'] ?? 'default';
        $post->views = $attributes['views'] ?? 0;
        // The featured rail only considers posts that have an image, so callers
        // that pass `image` must actually get one persisted.
        $post->image = $attributes['image'] ?? null;
        $post->author_id = $attributes['author_id'] ?? null;
        $post->author_type = $attributes['author_type'] ?? null;
        $post->created_at = $attributes['created_at'] ?? now();
        $post->save();

        // Production post slugs resolve at the site root (no prefix), so the
        // fixtures must match or $post->url points at a URL that 404s.
        Slug::create([
            'reference_type' => Post::class,
            'reference_id' => $post->id,
            'key' => 'test-post-' . $post->id,
            'prefix' => '',
        ]);

        return $post;
    }

    /**
     * Create a published category with a slug for use in tests.
     */
    protected function createCategory(array $attributes = []): Category
    {
        $category = new Category();
        $category->name = $attributes['name'] ?? 'Test Category ' . uniqid();
        $category->description = $attributes['description'] ?? 'Test category description';
        $category->status = $attributes['status'] ?? 'published';
        $category->parent_id = $attributes['parent_id'] ?? 0;
        $category->order = $attributes['order'] ?? 0;
        $category->save();

        // Categories are served under /topic/ in production.
        Slug::create([
            'reference_type' => Category::class,
            'reference_id' => $category->id,
            'key' => 'test-category-' . $category->id,
            'prefix' => 'topic',
        ]);

        return $category;
    }
}
