<?php

namespace App\Helpers;

class ImageHelper
{
    public static function lazyImage(string $src, string $alt, array $attributes = []): string
    {
        $defaults = ['loading' => 'lazy', 'decoding' => 'async'];
        $attrs = array_merge($defaults, $attributes);
        
        $attrString = '';
        foreach ($attrs as $key => $value) {
            if ($key !== 'src' && $key !== 'alt') {
                $attrString .= " {$key}=\"{$value}\"";
            }
        }
        
        return "<img src=\"{$src}\" alt=\"{$alt}\"{$attrString}>";
    }
}
