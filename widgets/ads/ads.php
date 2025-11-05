<?php

use Botble\Widget\AbstractWidget;

class AdsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Ads ',
            'description' => __('Widget to show image ads on sidebar.'),
            'image_url' => null,
            'image_link' => null,
            'image_new_tab' => 0,
        ]);
    }
}
