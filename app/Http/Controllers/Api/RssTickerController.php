<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RssNewsTickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RssTickerController extends Controller
{
    public function index(RssNewsTickerService $rssService): JsonResponse
    {
        try {
            $headlines = $rssService->getHeadlines();

            $countryCount = count($headlines);
            $totalItems = array_sum(array_map('count', $headlines));

            Log::debug('RSS Ticker API: Served ' . $totalItems . ' headlines from ' . $countryCount . ' countries');

            return response()->json(['headlines' => $headlines], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('RSS Ticker API Error: ' . $e->getMessage());

            return response()->json([
                'headlines' => [],
                'error' => 'Service temporarily unavailable',
            ], 503);
        }
    }
}
