<?php

namespace Botble\SimpleSlider\Forms\Settings;

use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Setting\Forms\SettingForm;
use Botble\SimpleSlider\Http\Requests\Settings\SimpleSliderSettingRequest;

class SimpleSliderSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/simple-slider::simple-slider.settings.title'))
            ->setSectionDescription(trans('plugins/simple-slider::simple-slider.settings.description'))
            ->setValidatorClass(SimpleSliderSettingRequest::class)
            ->add(
                'simple_slider_using_assets',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/simple-slider::simple-slider.settings.using_assets'))
                    ->defaultValue((bool) setting('simple_slider_using_assets', true))
            )
            ->add(
                'simple_slider_assets',
                HtmlField::class,
                HtmlFieldOption::make()
                    ->content(view('plugins/simple-slider::partials.simple-slider-asset-fields')->render())
            );
    }
}
