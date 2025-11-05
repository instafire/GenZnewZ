<section class="sub-page">
    <section class="container">
        @php
            $primarySidebar = Theme::partial('primary-sidebar');
        @endphp
        <section @if ($primarySidebar) class="primary fleft" @endif>
            {!! Theme::partial('breadcrumbs') !!}
            <h1 class="single-title">
                {{ $gallery->name }}
            </h1><!-- end .single-pro-title -->
            <section class="single-content">
                <div class="ck-content">
                    {!! BaseHelper::clean($gallery->description) !!}
                </div>
                @if (is_plugin_active('ads'))
                    {!! AdsManager::display('top-single-page', ['style' => 'margin-bottom: 20px']) !!}
                @endif
                <div id="list-photo">
                    @php
                        $images = gallery_meta_data($gallery);
                    @endphp
                    @if (!empty($images))
                        @foreach ($images as $image)
                            @if ($image)
                                <div class="item" data-src="{{ RvMedia::getImageUrl(Arr::get($image, 'img')) }}" data-sub-html="{{ BaseHelper::clean(Arr::get($image, 'description')) }}">
                                    <div class="photo-item">
                                        <div class="thumb">
                                            <a href="{{ BaseHelper::clean(Arr::get($image, 'description')) }}">
                                                {!! RvMedia::image(Arr::get($image, 'img'), BaseHelper::clean(Arr::get($image, 'description'))) !!}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
                @if (is_plugin_active('ads'))
                    {!! AdsManager::display('bottom-single-page', ['style' => 'margin-top: 20px']) !!}
                @endif
                <br>
                <section class="single-comment-content">
                    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $gallery) !!}
                </section><!-- end .single-comment-content -->
            </section><!-- end .single-pro-content -->
        </section><!-- end .primary -->
        {!! $primarySidebar !!}
    </section><!-- end .container -->
</section>
