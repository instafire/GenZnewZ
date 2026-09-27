<?php

use Botble\Widget\AbstractWidget;

class TextWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Text - LaraMag Theme',
            'description' => __('Arbitrary text or HTML.'),
            'content' => null,
        ]);
    }
}
