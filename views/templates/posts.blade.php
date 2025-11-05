@if ($posts->isNotEmpty())
    <section class="archive-featured-new">
        @foreach ($posts as $postCategory)
            @if ($loop->index < 3)
                <a class="featured-new-item fleft thumb-full item-thumbnail" href="{{ $postCategory->url }}">
                    {!! RvMedia::image($postCategory->image, $postCategory->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                    <h2 class="featured-new-item-title white-space bsize">
                        {{ $postCategory->name }}
                    </h2><!-- end .featured-new-item-title -->
                    <div class="thumbnail-hoverlay main-color-1-bg"></div>
                    <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                </a><!-- end .featured-new-item -->
            @endif
        @endforeach
        <section class="cboth"></section><!-- end .cboth -->
    </section><!-- end .archive-featured-new -->

    <section class="archive-pro-wrap">
        <ul>
            @foreach($posts as $postCategory)
                @if ($loop->index >= 3)
                    <section class="new-item bsize">
                        <a class="new-item-thumb thumb-full fleft item-thumbnail" href="{{ $postCategory->url }}">
                            {!! RvMedia::image($postCategory->image, $postCategory->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                            <div class="thumbnail-hoverlay main-color-1-bg"></div>
                            <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                        </a><!-- end .new-item-thumb -->
                        <section class="new-item-info">
                            <h2 class="new-item-title post1-item-title">
                                <a href="{{ $postCategory->url  }}">{{ $postCategory->name }}</a>
                            </h2><!-- end .new-item-title -->
                            <section class="new-item-date">
                                <i class="fa fa-calendar" aria-hidden="true"></i>{{ __('Posted At') }}: {{ Theme::formatDate($postCategory->created_at) }}
                            </section><!-- end .new-item-date -->
                            <section class="new-item-des">
                                {{ $postCategory->description }}
                            </section><!-- end .new-item-des -->
                            <section class="new-item-morelink">
                                <a href="{{ $postCategory->url }}">{{ __('View more') }}<i class="fa fa-angle-right" aria-hidden="true"></i></a>
                            </section><!-- end .new-item-morelink -->
                        </section><!-- end .new-item-info -->
                        <section class="cboth"></section><!-- end .cboth -->
                    </section><!-- end .new-item -->
                @endif
            @endforeach
        </ul>
    </section><!-- end .archive-pro-wrap -->

    <section class="pagination">
        {!! $posts->withQueryString()->links() !!}
    </section><!-- end .pagination -->
@endif
