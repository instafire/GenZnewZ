<?php

namespace Botble\Page\Http\Controllers\API;

use Botble\Api\Http\Controllers\BaseApiController;
use Botble\Page\Http\Resources\ListPageResource;
use Botble\Page\Http\Resources\PageResource;
use Botble\Page\Models\Page;
use Illuminate\Http\Request;

class PageController extends BaseApiController
{
    /**
     * List pages
     *
     * @group Page
     *
     * @queryParam per_page integer The number of items to return per page (default: 10).
     * @queryParam page integer The page number to retrieve (default: 1).
     *
     * @response 200 {
     *   "error": false,
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "About Us",
     *       "slug": "about-us",
     *       "content": "This is the about us page content...",
     *       "published_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "message": null
     * }
     */
    public function index(Request $request)
    {
        $pages = Page::query()
            ->wherePublished()
            ->with('slugable')
            ->paginate($request->integer('per_page', 10) ?: 10);

        return $this
            ->httpResponse()
            ->setData(ListPageResource::collection($pages))
            ->toApiResponse();
    }

    /**
     * Get page by ID
     *
     * @group Page
     *
     * @urlParam id integer required The ID of the page to retrieve.
     *
     * @response 200 {
     *   "error": false,
     *   "data": {
     *     "id": 1,
     *     "title": "About Us",
     *     "slug": "about-us",
     *     "content": "This is the about us page content...",
     *     "published_at": "2023-01-01T00:00:00.000000Z"
     *   },
     *   "message": null
     * }
     *
     * @response 404 {
     *   "error": true,
     *   "message": "Not found"
     * }
     */
    public function show(int|string $id)
    {
        $page = Page::query()
            ->where('id', $id)
            ->wherePublished()
            ->with('slugable')
            ->first();

        if (! $page) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(__('Not found'));
        }

        return $this
            ->httpResponse()
            ->setData(new PageResource($page))
            ->toApiResponse();
    }
}
