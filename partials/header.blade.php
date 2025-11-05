<!doctype html>
<html {!! Theme::htmlAttributes() !!}>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport"/>

    {!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=' . urlencode(theme_option('primary_font', 'Roboto Condensed')) . ':wght@300;400;700&display=swap') !!}

    <style>
        :root {
            --color-1st: {{ theme_option('primary_color', '#aa0909') }};
            --primary-color: {{ theme_option('primary_color', '#aa0909') }};
            --primary-font: '{{ theme_option('primary_font', 'Roboto Condensed') }}', sans-serif;
        }
    </style>

    {!! Theme::header() !!}

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8161801058322526"
        crossorigin="anonymous"></script>
    </head>

<body {!! Theme::bodyAttributes() !!}>
{!! apply_filters(THEME_FRONT_BODY, null) !!}
<header class="header">
    <section class="header-menu-top">
        <section class="container">
            <section class="header-menu-top-left fleft">
                {!!
                    Menu::renderMenuLocation('header-menu', [
                        'options' => ['id' => 'menu-header-top-menu', 'class' => 'menu'],
                        'theme'   => true,
                        'view'    => 'menu',
                    ])
                !!}
            </section><section class="header-menu-top-right header-social fright">
                <div class="language-wrapper">
                    {!! apply_filters('language_switcher') !!}
                </div>
            </section><section class="cboth"></section></section></section><section class="header-top">
        <section class="container">
            <h1 class="logo fleft">
                <a href="{{ route('public.single') }}" title="{{ theme_option('site_title') }}">
                    @if (!theme_option('logo'))
                        <span>Lara</span>Mag
                    @else
                        {!! RvMedia::image(theme_option('logo'), theme_option('site_title'), attributes: ['title' => theme_option('site_title')]) !!}
                    @endif
                </a>
            </h1><section class="header-banner" style="height: 90px; overflow: hidden">
                <ins class="adsbygoogle"
                    style="display:inline-block;width:728px;height:90px"
                    data-ad-client="ca-pub-8161801058322526"
                    data-ad-slot="5973355986"></ins>
                <script>
                    (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
                </section></section></section><section class="header-bottom">
        <section class="container">
            <a class="icon-home fleft icon-home-active icon-home-active" href="{{ route('public.single') }}" title="Homepage"></a>
            <section class="collap-main-nav fleft">
                <img src="{{ Theme::asset()->url('images/icon/collapse.png') }}" alt="collapse"/>
            </section>
            <section class="main-nav fleft">
                <section class="main-nav-inner tf">
                    <section class="close-nav">
                        <i class="fa fa-times" aria-hidden="true"></i> {{ __('Close menu') }}
                    </section>{!!
                        Menu::renderMenuLocation('main-menu', [
                            'options' => ['id' => 'menu-header-main-menu', 'class' => 'menu'],
                            'theme'   => true,
                            'view'    => 'menu',
                        ])
                    !!}
                </section></section><a href="#" class="search-btn" title="search" @if (!Menu::isLocationHasMenu('second-menu')) style="margin-right: 0;" @endif><i class="fa fa-search"></i></a>
            @if (Menu::isLocationHasMenu('second-menu'))
                <section class="collap-nav-second bsize">
                    ...
                    {!!
                        Menu::renderMenuLocation('second-menu', [
                            'options' => ['id' => 'menu-header-second-menu', 'class' => 'menu'],
                            'theme' => true,
                        ])
                    !!}
                </section>@endif
            <section class="cboth"></section></section></section></header>@if (is_plugin_active('blog'))
    <div class="super-search hide">
        <form class="quick-search" action="{{ route('public.search') }}">
            <input type="text" name="q" placeholder="{{ __('Type to search...') }}" class="form-control search-input" autocomplete="off">
            <span class="search-btn" title="search">&times;</span>
        </form>
        <div class="search-result"></div>
    </div>
@endif