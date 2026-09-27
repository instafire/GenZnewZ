<?php

namespace Botble\Blog\Http\Resources;

use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\Shortcode\Facades\Shortcode;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => Shortcode::compile((string) $this->content, true)->toHtml(),
            'image' => $this->image ? RvMedia::url($this->image) : null,
            'categories' => CategoryResource::collection($this->categories),
            'tags' => TagResource::collection($this->tags),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
