<?php

namespace App\Http\Controllers;

use App\Services\HomepageDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function categoriesChunk(Request $request, HomepageDataService $homepageDataService): JsonResponse
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = min(10, max(1, (int) $request->query('limit', 5)));

        $homepageData = $homepageDataService->getHomepageData();
        $renderableCategories = $homepageDataService->getRenderableCategories($homepageData);

        $chunk = $renderableCategories
            ->slice($offset, $limit)
            ->values();

        $html = $chunk
            ->map(function (array $section, int $index) use ($offset) {
                return view('theme::partials.home-category-section', [
                    'category' => $section['category'],
                    'posts' => $section['posts'],
                    'index' => $offset + $index + 1,
                ])->render();
            })
            ->implode('');

        $nextOffset = $offset + $chunk->count();

        return response()->json([
            'html' => $html,
            'nextOffset' => $nextOffset,
            'hasMore' => $renderableCategories->count() > $nextOffset,
        ]);
    }
}
