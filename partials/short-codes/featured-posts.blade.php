@if (is_plugin_active('ads'))
    {!! AdsManager::display('before-featured-posts', ['style' => 'margin: 10px 0;']) !!}
@endif

<section class="featured-home-post">
    <section class="container">
        @if (is_plugin_active('blog'))
            @foreach(get_featured_posts((int) $shortcode->limit ?: 5) as $post)
                <section class="featured-home-post-item thumb-full fleft">
                    {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                    <section class="featured-home-post-item-info bsize">
                        <h2 class="featured-home-post-item-title">
                            <a href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                        </h2><!-- end .featured-home-post-item-title -->
                        <section class="featured-home-post-item-date">
                            <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>
                            @if (class_exists($post->author_type) && $post->author)
                                <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                    {{ $post->author->name }}
                                </span>
                            @endif
                        </section><!-- end .featured-home-post-item-date -->
                        <section class="featured-home-post-item-des">
                            {{ Str::limit($post->description, 80) }}
                        </section><!-- end .featured-home-post-item-des -->
                    </section><!-- end .featured-home-post-item-info -->
                </section><!-- end .featured-home-post-item -->
            @endforeach
        @endif
        <section class="cboth"></section><!-- end .cboth -->
    </section><!-- end .featured-home-post -->
</section><!-- end .featured-home-post -->

@if (is_plugin_active('ads'))
    {!! AdsManager::display('after-featured-posts', ['style' => 'margin: 10px 0;']) !!}
@endif
