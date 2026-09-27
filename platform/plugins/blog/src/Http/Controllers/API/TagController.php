<?php

namespace Botble\Blog\Http\Controllers\API;

use Botble\Api\Http\Controllers\BaseApiController;
use Botble\Blog\Http\Resources\TagResource;
use Botble\Blog\Models\Tag;
use Illuminate\Http\Request;

class TagController extends BaseApiController
{
    /**
     * List tags
     *
     * @group Blog
     *
     * @queryParam per_page integer The number of items to return per page (default: 10).
     * @queryParam page integer The page number to retrieve (default: 1).
     *
     * @response 200 {
     *   "error": false,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Laravel",
     *       "slug": "laravel",
     *       "description": "PHP Framework for web development",
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "message": null
     * }
     */
    public function index(Request $request)
    {
        $data = Tag::query()
            ->wherePublished()
            ->with('slugable')
            ->paginate($request->integer('per_page', 10) ?: 10);

        return $this
            ->httpResponse()
            ->setData(TagResource::collection($data))
            ->toApiResponse();
    }
}
