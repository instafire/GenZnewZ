<?php

use Botble\Menu\Models\Menu;
use Botble\Widget\AbstractWidget;

class CustomMenuWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Custom Menu'),
            'description' => __('Add a custom menu to your widget area.'),
            'menu_id' => null,
        ]);
    }

    protected function adminConfig(): array
    {
        return [
            'menus' => Menu::query()->pluck('name', 'slug')->all(),
        ];
    }
}
