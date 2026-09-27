{!! Theme::partial('header') !!}

@include('theme::partials.breadcrumbs')

<main class="container" id="main-content">
    {!! Theme::content() !!}
</main>

{!! Theme::partial('footer') !!}
