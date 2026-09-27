<?php

namespace Botble\Blog\Http\Controllers\API;

use Botble\Api\Http\Controllers\BaseApiController;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Blog\Http\Resources\CategoryResource;
use Botble\Blog\Http\Resources\ListCategoryResource;
use Botble\Blog\Models\Category;
use Botble\Blog\Repositories\Interfaces\CategoryInterface;
use Botble\Blog\Supports\FilterCategory;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    /**
     * List categories
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
     *       "name": "Technology",
     *       "slug": "technology",
     *       "description": "Latest tech news and updates",
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "message": null
     * }
     */
    public function index(Request $request)
    {
        $data = Category::query()
            ->wherePublished()->latest()
            ->with(['slugable'])
            ->paginate($request->integer('per_page', 10) ?: 10);

        return $this
            ->httpResponse()
            ->setData(ListCategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Filters categories
     *
     * @group Blog
     *
     * @queryParam page integer Current page of the collection (default: 1).
     * @queryParam per_page integer Maximum number of items to be returned (default: 10).
     * @queryParam search string Limit results to those matching a string.
     * @queryParam order string Order sort attribute ascending or descending (default: desc). One of: asc, desc.
     * @queryParam order_by string Sort collection by object attribute (default: created_at). One of: created_at, updated_at, id, name.
     *
     * @response 200 {
     *   "error": false,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology",
     *       "description": "Latest tech news and updates"
     *     }
     *   ],
     *   "message": null
     * }
     */
    public function getFilters(Request $request, CategoryInterface $categoryRepository)
    {
        $filters = FilterCategory::setFilters($request->input());
        $data = $categoryRepository->getFilters($filters);

        return $this
            ->httpResponse()
            ->setData(CategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get category by slug
     *
     * @group Blog
     * @queryParam slug Find by slug of category.
     */
    public function findBySlug(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Category::class));

        if (! $slug) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Not found');
        }

        $category = Category::query()
            ->with('slugable')
            ->where([
                'id' => $slug->reference_id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->first();

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Not found');
        }

        return $this
            ->httpResponse()
            ->setData(new ListCategoryResource($category))
            ->toApiResponse();
    }
}
