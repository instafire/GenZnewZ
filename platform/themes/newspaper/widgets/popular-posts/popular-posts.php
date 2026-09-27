<?php

use Botble\Widget\AbstractWidget;

class PopularPostsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Popular Posts - LaraMag Theme',
            'description' => 'Show list popular posts',
            'number_display' => 5,
        ]);
    }
}
