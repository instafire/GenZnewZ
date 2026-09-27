<?php

namespace App\Listeners;

use App\Services\SearchEnginePingService;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Blog\Models\Post;
use Botble\Page\Models\Page;

class ContentPublishedListener
{
    protected SearchEnginePingService $searchPing;

    public function __construct(SearchEnginePingService $searchPing)
    {
        $this->searchPing = $searchPing;
    }

    /**
     * Handle content creation events
     */
    public function handleCreated(CreatedContentEvent $event): void
    {
        $data = $event->data;

        // Only handle published posts and pages
        if (! $this->shouldIndex($data)) {
            return;
        }

        $url = $this->getUrl($data);
        
        if ($url) {
            $this->searchPing->notifyOnPublish($url);
            \Log::info('Search ping: content created and submitted for indexing', ['url' => $url]);
        }
    }

    /**
     * Handle content update events
     */
    public function handleUpdated(UpdatedContentEvent $event): void
    {
        $data = $event->data;

        // Only handle published posts and pages
        if (! $this->shouldIndex($data)) {
            return;
        }

        $url = $this->getUrl($data);
        
        if ($url) {
            $this->searchPing->notifyOnPublish($url);
            \Log::info('Search ping: content updated and submitted for indexing', ['url' => $url]);
        }
    }

    /**
     * Check if content should be indexed
     */
    protected function shouldIndex($data): bool
    {
        // Check if it's a Post
        if ($data instanceof Post) {
            return $data->status === 'published';
        }

        // Check if it's a Page
        if ($data instanceof Page) {
            return $data->status === 'published';
        }

        // Check for array data with status
        if (is_array($data) && isset($data['status'])) {
            return $data['status'] === 'published';
        }

        return false;
    }

    /**
     * Get the URL for the content
     */
    protected function getUrl($data): ?string
    {
        if ($data instanceof Post || $data instanceof Page) {
            // Try to get URL from the model
            if (method_exists($data, 'getUrlAttribute') || isset($data->url)) {
                return $data->url;
            }

            // Try to get URL from slugable relationship
            if ($data->slugable) {
                return url($data->slugable->key);
            }
        }

        return null;
    }
}
