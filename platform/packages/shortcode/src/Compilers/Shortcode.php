<?php

namespace Botble\Shortcode\Compilers;

use Botble\Base\Facades\Html;
use Botble\Media\Facades\RvMedia;

class Shortcode
{
    public function __construct(
        protected string $name,
        protected array $attributes = [],
        public ?string $content = null
    ) {
    }

    public function get(string $attribute, ?string $fallback = null): string
    {
        $value = $this->{$attribute};

        if (! empty($value)) {
            return $attribute . '="' . $value . '"';
        } elseif (! empty($fallback)) {
            return $attribute . '="' . $fallback . '"';
        }

        return '';
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get(string $param)
    {
        return $this->attributes[$param] ?? null;
    }

    public function htmlAttributes(): string
    {
        $attributes = [
            'data-block-id' => $this->name,
        ];

        $styles = [];

        if ($this->background_color) {
            $variable = '--block-' . $this->name . '-background-color';
            $styles[] = "{$variable}: {$this->background_color}; background-color: var({$variable}) !important;";
        }

        if ($backgroundImage = $this->background_image) {
            $backgroundImage = RvMedia::getImageUrl($backgroundImage);

            $variable = '--block-' . $this->name . '-background-image';
            $styles[] = "{$variable}: url({$backgroundImage}); background-image: var({$variable}) !important; background-size: cover;";
        }

        if ($this->text_color) {
            $variable = '--block-' . $this->name . '-color';
            $styles[] = "{$variable}: {$this->text_color}; color: var({$variable}) !important;";
        }

        if ($this->custom_css) {
            $styles[] = $this->custom_css;
        }

        if (! empty($styles)) {
            $attributes['style'] = implode(' ', $styles);
        }

        return Html::attributes($attributes);
    }
}
