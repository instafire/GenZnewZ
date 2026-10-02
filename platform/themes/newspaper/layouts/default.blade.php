{!! Theme::partial('header') !!}

@include('theme::partials.breadcrumbs')

<main class="container" id="main-content">
    {!! Theme::content() !!}
    {{-- Fallback for views rendered outside the theme pipeline (e.g. the 404
         error view): they use @extends/@section('content'), which Theme::content()
         never sees. No normal view registers a 'content' section, so this is
         empty in the regular pipeline. --}}
    @yield('content')
</main>

{!! Theme::partial('footer') !!}
