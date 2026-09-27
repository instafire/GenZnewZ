<?php

use Botble\Widget\AbstractWidget;

class RecentPostsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Recent posts - LaraMag Theme'),
            'description' => __('Recent posts widget.'),
            'number_display' => 5,
            'ignore_guideline' => true,
        ]);
    }
}
