<?php

namespace Botble\Ads\Http\Controllers\API;

use Botble\Ads\Models\Ads;
use Botble\Api\Http\Controllers\BaseApiController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class AdsController extends BaseApiController
{
    /**
     * Get ads
     *
     * @group Ads
     *
     * @queryParam keys array Array of ad keys to filter by. Example: ["homepage-banner", "sidebar-banner"]
     * @bodyParam keys array Array of ad keys to filter by. Example: ["homepage-banner", "sidebar-banner"]
     *
     * @return BaseHttpResponse
     */
    public function index(Request $request, BaseHttpResponse $response)
    {
        if ($request->has('keys')) {
            $validator = Validator::make($request->all(), [
                'keys' => ['required', 'array'],
                'keys.*' => ['string'],
            ]);

            if ($validator->fails()) {
                return $response
                    ->setError()
                    ->setCode(422)
                    ->setMessage($validator->errors()->first())
                    ->toApiResponse();
            }
        }

        // Build the base query
        $query = Ads::query()
            ->wherePublished()
            ->where(function ($query): void {
                $query->where('expired_at', '>=', Carbon::now())
                    ->orWhere('ads_type', 'google_adsense');
            })
            ->oldest('order');

        // Filter by keys if provided (either from GET or POST)
        $keys = $request->input('keys');
        if ($keys && is_array($keys)) {
            $query->whereIn('key', $keys);
        }

        // Get the ads and format them
        $ads = $query->get()->map(function ($ad) {
            return $this->formatAd($ad);
        });

        return $response
            ->setData($ads)
            ->toApiResponse();
    }

    /**
     * Format ad data for API response
     */
    protected function formatAd(Ads $ad): array
    {
        return [
            'key' => $ad->key,
            'name' => $ad->name,
            'image' => $ad->image_url,
            'tablet_image' => $ad->tablet_image_url,
            'mobile_image' => $ad->mobile_image_url,
            'link' => $ad->url,
            'order' => $ad->order,
            'open_in_new_tab' => $ad->open_in_new_tab,
            'ads_type' => $ad->ads_type,
            'google_adsense_slot_id' => $ad->google_adsense_slot_id,
            ...$this->formatMetadata($ad),
        ];
    }

    /**
     * Format metadata to key-value pairs
     */
    protected function formatMetadata($item): array
    {
        $formattedMetadata = [];

        foreach ($item->metadata as $meta) {
            if (isset($meta['meta_key']) && isset($meta['meta_value']) && is_array($meta['meta_value']) && count($meta['meta_value']) > 0) {
                $formattedMetadata[$meta['meta_key']] = Arr::first($meta['meta_value']);
            }
        }

        return $formattedMetadata;
    }
}
