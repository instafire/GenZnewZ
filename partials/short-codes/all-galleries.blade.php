@if (function_exists('get_galleries'))
    <div class="clearfix"></div>
    @if (!$galleries->isEmpty())
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('before-galleries', ['style' => 'margin: 10px 0;']) !!}
        @endif
        <section class="block-post-wrap-item block-post1-wrap-item bsize" style="width: 100%;">
            <section class="block-post-wrap-head sidebar-item-head tf">
                <span><i class="fa fa-tags" aria-hidden="true"></i>{{ trans('plugins/gallery::gallery.galleries') }}</span>
            </section>
            <section class="block-post-wrap-content">
                <div class="gallery-wrap">
                    @foreach ($galleries as $gallery)
                        <div class="gallery-item">
                            <div class="img-wrap">
                                <a href="{{ $gallery->url }}">{!! RvMedia::image($gallery->image, $gallery->name, attributes: ['size' => 'medium']) !!}</a>
                            </div>
                            <div class="gallery-detail">
                                <div class="gallery-title"><a href="{{ $gallery->url }}">{{ $gallery->name }}</a></div>
                                <div class="gallery-author">{{ __('Posted At') }}: {{ Theme::formatDate($gallery->created_at) }}</div>
                            </div>
                        </div>
                    @endforeach
                    <div class="cboth"></div>
                </div>
            </section>
        </section>
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('after-galleries', ['style' => 'margin: 10px 0;']) !!}
        @endif
    @endif
    <div class="clearfix"></div>
@endif
