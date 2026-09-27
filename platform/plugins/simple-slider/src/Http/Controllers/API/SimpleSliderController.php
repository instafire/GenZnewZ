<?php

namespace Botble\SimpleSlider\Http\Controllers\API;

use Botble\Api\Http\Controllers\BaseApiController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Media\Facades\RvMedia;
use Botble\SimpleSlider\Models\SimpleSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class SimpleSliderController extends BaseApiController
{
    /**
     * Get sliders
     *
     * @group Simple Slider
     *
     * @queryParam keys array Array of slider keys to filter by. Example: ["home-slider", "product-slider"]
     * @bodyParam keys array Array of slider keys to filter by. Example: ["home-slider", "product-slider"]
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
        $query = SimpleSlider::query()
            ->wherePublished()
            ->with(['sliderItems' => function ($query): void {
                $query->orderBy('order');
            }]);

        // Filter by keys if provided (either from GET or POST)
        $keys = $request->input('keys');
        if ($keys && is_array($keys)) {
            $query->whereIn('key', $keys);
        }

        // Get the sliders and format them
        $sliders = $query->get()->map(function ($slider) {
            return $this->formatSlider($slider);
        });

        return $response
            ->setData($sliders)
            ->toApiResponse();
    }

    /**
     * Format slider data for API response
     */
    protected function formatSlider(SimpleSlider $slider): array
    {
        return [
            'id' => $slider->id,
            'name' => $slider->name,
            'key' => $slider->key,
            'description' => $slider->description,
            'items' => $slider->sliderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'image' => RvMedia::getImageUrl($item->image),
                    'link' => $item->link,
                    'order' => $item->order,
                    ...$this->formatMetadata($item),
                ];
            }),
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
