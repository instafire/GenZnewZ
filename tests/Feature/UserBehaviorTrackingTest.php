<?php

namespace Tests\Feature;

use App\Models\UserBehaviorLog;
use Botble\Blog\Models\Post;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithPlugins;

class UserBehaviorTrackingTest extends TestCase
{
    use RefreshDatabaseWithPlugins;

    public function test_tracking_endpoint_requires_valid_input(): void
    {
        $response = $this->postJson('/api/tracking/event', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visitor_id', 'post_id', 'action']);
    }

    public function test_tracking_endpoint_stores_view_event(): void
    {
        $post = $this->createPost();

        $response = $this->postJson('/api/tracking/event', [
            'visitor_id' => 'test-visitor-123',
            'post_id' => $post->id,
            'action' => 'view',
            'source' => 'article_page',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'tracked']);

        $this->assertDatabaseHas('user_behavior_logs', [
            'visitor_id' => 'test-visitor-123',
            'post_id' => $post->id,
            'action' => 'view',
            'source' => 'article_page',
        ]);
    }

    public function test_tracking_endpoint_stores_click_event(): void
    {
        $post = $this->createPost();

        $this->postJson('/api/tracking/event', [
            'visitor_id' => 'test-visitor-456',
            'post_id' => $post->id,
            'action' => 'click',
            'source' => 'recommendation_widget',
        ]);

        $this->assertDatabaseHas('user_behavior_logs', [
            'visitor_id' => 'test-visitor-456',
            'post_id' => $post->id,
            'action' => 'click',
        ]);
    }

    public function test_tracking_endpoint_rejects_invalid_action(): void
    {
        $post = $this->createPost();

        $response = $this->postJson('/api/tracking/event', [
            'visitor_id' => 'test-visitor-789',
            'post_id' => $post->id,
            'action' => 'invalid_action',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_tracking_endpoint_respects_do_not_track(): void
    {
        $post = $this->createPost();

        $response = $this->withHeaders(['DNT' => '1'])->postJson('/api/tracking/event', [
            'visitor_id' => 'dnt-visitor',
            'post_id' => $post->id,
            'action' => 'view',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ignored', 'reason' => 'do_not_track']);

        $this->assertDatabaseMissing('user_behavior_logs', [
            'visitor_id' => 'dnt-visitor',
        ]);
    }

    protected function createPost(): Post
    {
        $post = new Post();
        $post->name = 'Test Post ' . uniqid();
        $post->description = 'Test description';
        $post->content = 'Test content';
        $post->status = 'published';
        $post->views = 0;
        $post->save();

        return $post;
    }
}
