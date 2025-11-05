<?php

use Botble\Widget\AbstractWidget;

class FacebookWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Facebook - LaraMag Theme',
            'description' => 'Facebook fan page widget',
            'facebook_name' => null,
            'facebook_url' => null,
            'facebook_tabs' => null,
        ]);
    }
}
