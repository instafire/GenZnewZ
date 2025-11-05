<section class="sub-page">
    <section class="container">
        @php
            $primarySidebar = Theme::partial('primary-sidebar');
        @endphp
        <section @if ($primarySidebar) class="primary fleft" @endif>
            <section class="block-archive-head">
                <span class="tf"><i class="fa fa-th-list" aria-hidden="true"></i>{{  __('Search result for: ":query"', ['query' => Request::input('q')]) }}</span>
            </section><!-- end .block-archive-head -->

            @if ($posts->isNotEmpty())
                <section class="archive-pro-wrap">
                    <ul>
                        @foreach($posts as $post)
                            <section class="new-item bsize">
                                <a class="new-item-thumb thumb-full fleft item-thumbnail" href="{{ $post->url }}">
                                    {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                                    <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                    <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                                </a><!-- end .new-item-thumb -->
                                <section class="new-item-info">
                                    <h2 class="new-item-title post1-item-title">
                                        <a href="{{ $post->url  }}">{!! BaseHelper::clean($post->name) !!}</a>
                                    </h2><!-- end .new-item-title -->
                                    <section class="new-item-date">
                                        <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>&nbsp;
                                        <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                        @if (class_exists($post->author_type))
                                            @if ($post->author->url)
                                                <a href="{{ $post->author->url }}">{{ $post->author->name }}</a>
                                            @else
                                                {{ $post->author->name }}
                                            @endif
                                        @endif
                                    </span>
                                    </section>
                                    <section class="new-item-des">
                                        {{ $post->description }}
                                    </section><!-- end .new-item-des -->
                                    <section class="new-item-morelink">
                                        <a href="{{ $post->url }}">{{ __('View more') }}<i class="fa fa-angle-right" aria-hidden="true"></i></a>
                                    </section><!-- end .new-item-morelink -->
                                </section><!-- end .new-item-info -->
                                <section class="cboth"></section><!-- end .cboth -->
                            </section><!-- end .new-item -->
                        @endforeach
                    </ul>
                </section><!-- end .archive-pro-wrap -->

                @if ($posts->isNotEmpty())
                    <section class="pagination">
                        {!! $posts->links() !!}
                    </section><!-- end .pagination -->
                @endif
            @else
                <section class="archive-pro-wrap">
                    <p>{{ __('No posts found!') }}</p>
                </section>
            @endif
        </section><!-- end .primary -->
        {!! $primarySidebar !!}
    </section><!-- end .container -->
</section>
