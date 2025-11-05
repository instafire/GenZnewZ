<section class="sub-page">
    <section class="container">
        @php
            $primarySidebar = Theme::partial('primary-sidebar');
        @endphp
        <section @if ($primarySidebar) class="primary fleft" @endif>
            {!! Theme::partial('breadcrumbs') !!}
            <h1 class="single-title">
                {{ $post->name }}
            </h1>
            <section class="new-item-date">
                <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>&nbsp;
                @if (class_exists($post->author_type))
                    <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                        @if ($post->author->url)
                            <a href="{{ $post->author->url }}">{{ $post->author->name }}</a>
                        @else
                            {{ $post->author->name }}
                        @endif
                    </span>
                @endif

                <span>&nbsp;<i class="fa fa-eye" aria-hidden="true"></i>{{ __(':count views', ['count' => number_format($post->views)]) }}</span>
            </section>
            <section class="single-content">
                @if (is_plugin_active('ads'))
                    {!! AdsManager::display('top-single-page', ['style' => 'margin-bottom: 20px']) !!}
                @endif

                @if ($post->format_type == 'video')
                    @php $url = str_replace('watch?v=', 'embed/', MetaBox::getMetaData($post, 'video_link', true)); @endphp
                    @if (!empty($url))
                        <div class="embed-responsive embed-responsive-16by9 mb30">
                            <iframe class="embed-responsive-item" allowfullscreen frameborder="0" height="315" width="420" src="{{ str_replace('watch?v=', 'embed/', $url) }}"></iframe>
                        </div>
                        <br>
                    @endif
                @endif
                @if (defined('GALLERY_MODULE_SCREEN_NAME') && !empty($galleries = gallery_meta_data($post)))
                    {!! render_object_gallery($galleries, ($post->firstCategory ? $post->firstCategory->name : __('Uncategorized'))) !!}
                @endif

                @if ($post->getMetaData('display_featured_image_at_the_top', true))
                    <div style="margin-bottom: 20px;">
                        {!! RvMedia::image($post->image, $post->name, attributes: ['style' => 'width: 100% !important;']) !!}
                    </div>
                @endif
                <div class='ck-content'>{!! BaseHelper::clean($post->content) !!}</div>
                <br>
                <section class="new-item-shar">
                    <span>{{ __('Share:') }}</span>

                    {!! Theme::renderSocialSharing($post->url, SeoHelper::getDescription(), $post->image) !!}
                </section>
                <br>
                @if ($post->tags && $post->tags->isNotEmpty())
                    <div class="list-tag">
                        <span>
                            <span class="tag-list-title">{{ __('Tags') }}: </span>
                            @foreach ($post->tags as $tag)
                                <a href="{{ $tag->url }}">{!! BaseHelper::clean($tag->name) !!}</a>
                            @endforeach
                        </span>
                    </div>
                @endif
                @if (is_plugin_active('ads'))
                    {!! AdsManager::display('bottom-single-page', ['style' => 'margin-top: 20px']) !!}
                @endif
            </section><!-- end .single-pro-content -->
            <section class="single-comment">
                <section class="block-archive-head">
                    <section class="box-share fright">
                        <div class="addthis_inline_share_toolbox_pjup"></div>
                    </section><!-- end .box-share-->
                    <section class="cboth"></section>
                </section><!-- end .block-archive-head -->
                <section class="single-comment-content">
                    {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $post) !!}
                </section><!-- end .single-comment-content -->
            </section><!-- end .single-comment -->
            @php $relatedPosts = get_related_posts($post->id, 5); @endphp

            @if ($relatedPosts->isNotEmpty())
                <section class="single-pro-related">
                    <section class="block-archive-head">
                        <span class="tf"><i class="fa fa-newspaper-o" aria-hidden="true"></i>{{ __('Related posts') }}</span>
                    </section><!-- end .block-archive-head -->
                    <section class="block-content single-new-related-content">
                        <section>
                            <ul>
                                @foreach ($relatedPosts as $relatedItem)
                                <li class="post1-item-list">
                                    <a href="{{ $relatedItem->url }}"><i class="fa fa-caret-right" aria-hidden="true"></i>{{ $relatedItem->name }}</a>
                                </li>
                                @endforeach
                            </ul>
                        </section><!-- end .featured-pro-wrap -->
                    </section><!-- end .block-content -->
                </section><!-- end .single-pro-related -->
            @endif
        </section><!-- end .primary -->
        {!! $primarySidebar !!}
    </section><!-- end .container -->
</section>
