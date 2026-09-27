<?php

namespace Botble\Page\Http\Resources;

use Botble\Media\Facades\RvMedia;
use Botble\Page\Models\Page;
use Botble\Shortcode\Facades\Shortcode;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Page
 */
class PageResource extends JsonResource
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
            'template' => $this->template,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
