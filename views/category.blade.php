<section class="sub-page">
    <section class="container">
        @php
            $primarySidebar = Theme::partial('primary-sidebar');
        @endphp
        <section @if ($primarySidebar) class="primary fleft" @endif>
            <section class="block-archive-head">
                <span class="tf"><i class="fa fa-th-list" aria-hidden="true"></i>{!! BaseHelper::clean($category->name) !!}</span>
                <ul>
                    @foreach ($category->children()->get() as $child)
                        <li>
                            <a href="{{ $child->url }}">
                                {{ $child->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section><!-- end .block-archive-head -->

            <section class="archive-featured-new">
                @foreach ($posts as $post)
                    @if ($loop->index < 3)
                        <a class="featured-new-item fleft thumb-full item-thumbnail" href="{{ $post->url }}">
                            {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                            <section class="featured-new-item-info bsize">
                                <div class="featured-new-item-title">
                                    <h2 class="white-space">{!! BaseHelper::clean($post->name) !!}</h2>
                                    <section class="featured-home-post-item-date">
                                        <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>

                                        @if (class_exists($post->author_type))
                                            <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                                {{ $post->author->name }}
                                            </span>
                                        @endif
                                    </section><!-- end .featured-home-post-item-date -->
                                </div><!-- end .featured-new-item-title -->
                            </section>
                            <div class="thumbnail-hoverlay main-color-1-bg"></div>
                            <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                        </a><!-- end .featured-new-item -->
                    @endif
                @endforeach
                <section class="cboth"></section><!-- end .cboth -->
            </section><!-- end .archive-featured-new -->

            <section class="archive-pro-wrap">
                <ul>
                    @foreach($posts as $post)
                        @if ($loop->index >= 3)
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
                        @endif
                    @endforeach
                </ul>
            </section><!-- end .archive-pro-wrap -->

            @if ($posts->isNotEmpty())
                <section class="pagination">
                    {!! $posts->links() !!}
                </section><!-- end .pagination -->
            @endif
        </section><!-- end .primary -->
        {!! $primarySidebar !!}
    </section><!-- end .container -->
</section>
