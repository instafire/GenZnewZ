@if ($page->template != 'homepage')
    <section class="sub-page">
        <section class="container">
            @php
                $primarySidebar = Theme::partial('primary-sidebar');
            @endphp
            <section @if ($primarySidebar) class="primary fleft" @endif>
                {!! Theme::partial('breadcrumbs') !!}
                <h1 class="single-title">
                    {{ $page->name }}
                </h1><!-- end .single-pro-title -->
                <section class="single-content">
                    @if (defined('GALLERY_MODULE_SCREEN_NAME') && !empty($galleries = gallery_meta_data($page)))
                        {!! render_object_gallery($galleries) !!}
                    @endif
                    @if (is_plugin_active('ads'))
                        {!! AdsManager::display('top-single-page', ['style' => 'margin-bottom: 20px']) !!}
                    @endif
			{!! apply_filters(PAGE_FILTER_FRONT_PAGE_CONTENT, Html::tag('div', BaseHelper::clean($page->content), ['class' =>
'ck-content'])->toHtml(), $page) !!}
                    @if (is_plugin_active('ads'))
                        {!! AdsManager::display('bottom-single-page', ['style' => 'margin-top: 20px']) !!}
                    @endif
                </section><!-- end .single-pro-content -->
            </section><!-- end .primary -->
            {!! $primarySidebar !!}
        </section><!-- end .container -->
    </section>
@else
    @if (defined('GALLERY_MODULE_SCREEN_NAME') && !empty($galleries = gallery_meta_data($page)))
        {!! render_object_gallery($galleries) !!}
    @endif
	{!! apply_filters(PAGE_FILTER_FRONT_PAGE_CONTENT, Html::tag('div', BaseHelper::clean($page->content), ['class' =>
'ck-content'])->toHtml(), $page) !!}
@endif
