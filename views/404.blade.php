@php
    SeoHelper::setTitle(__('404 - Not found'));
    Theme::fireEventGlobalAssets();
    Theme::breadcrumb()->add(__('Home'), route('public.index'))->add(SeoHelper::getTitle());
@endphp

{!! Theme::partial('header') !!}
<div class="container">
    {!! Theme::partial('breadcrumbs') !!}
</div>

<style>
    .error-code {
        color: #22292f;
        font-size: 3rem;
    }

    .error-border {
        background-color: var(--color-1st);
        height: .25rem;
        width: 6rem;
        margin-bottom: 1.5rem;
    }

    .error-page a {
        color: var(--color-1st);
    }

    .error-page ul li {
        margin-bottom : 5px;
    }
</style>

<div class="container">
    <section class="sub-page">
        <section class="container error-page">
            <br>
            <br>
            <br>
                <div class="error-code">
                    404
                    <div class="clearfix"></div>
                </div>
                <br>

                <div class="error-border"></div>

                <h3 style="margin-bottom: 10px;">{{ __('This may have occurred because of several reasons') }}:</h3>
                <ul>
                    <li>{{ __('The page you requested does not exist.') }}</li>
                    <li>{{ __('The link you clicked is no longer.') }}</li>
                    <li>{{ __('The page may have moved to a new location.') }}</li>
                    <li>{{ __('An error may have occurred.') }}</li>
                    <li>{{ __('You are not authorized to view the requested resource.') }}</li>
                </ul>
                <br>

                <strong>{!! BaseHelper::clean(__('Please try again in a few minutes, or alternatively return to the homepage by <a href=":link">clicking here</a>.', ['link' => route('public.single')])) !!}</strong>
            <br>
            <br>
        </section>
    </section>
</div>

{!! Theme::partial('footer') !!}
