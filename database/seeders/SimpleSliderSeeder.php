<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\SimpleSlider\Models\SimpleSliderItem;

class SimpleSliderSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('sliders');

        $sliders = SimpleSliderItem::query()->get();

        $index = 1;
        foreach ($sliders as $slider) {
            $slider->image = $this->filePath('sliders/' . $index . '.jpg');
            $slider->save();

            if ($index == $sliders->count() / 2) {
                $index = 1;
            }

            $index++;
        }
    }
}
