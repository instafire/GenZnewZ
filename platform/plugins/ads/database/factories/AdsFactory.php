<?php

namespace Botble\Ads\Database\Factories;

use Botble\Ads\Models\Ads;
use Botble\Base\Enums\BaseStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdsFactory extends Factory
{
    protected $model = Ads::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'key' => $this->faker->unique()->slug,
            'status' => BaseStatusEnum::PUBLISHED,
            'expired_at' => Carbon::now()->addDays(30),
            'location' => 'not_set',
            'image' => $this->faker->imageUrl(),
            'url' => $this->faker->url,
            'clicked' => $this->faker->numberBetween(0, 1000),
            'order' => $this->faker->numberBetween(0, 100),
            'open_in_new_tab' => true,
            'ads_type' => 'custom_ad',
        ];
    }
}
