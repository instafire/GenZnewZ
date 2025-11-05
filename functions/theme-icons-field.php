<?php

use Botble\Theme\Facades\Theme;
use Botble\Theme\Theme as BaseTheme;
use Illuminate\Routing\Events\RouteMatched;

app('events')->listen(RouteMatched::class, function (): void {
    if (! method_exists(BaseTheme::class, 'registerThemeIconFields')) {
        return;
    }

    Theme::registerThemeIconFields([]);
});
