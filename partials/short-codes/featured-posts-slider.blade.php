@if (count($posts) > 0)
    <section class="featured-posts-slider">
        <div class="owl-slider owl-carousel carousel--nav inside"
             data-owl-auto="{{ ($shortcode->is_autoplay ?: 'yes') == 'yes' ? 'true' : 'false' }}"
             data-owl-loop="{{ ($shortcode->is_autoplay ?: 'yes') == 'yes' ? 'true' : 'false' }}"
             data-owl-speed="{{ in_array($shortcode->autoplay_speed, theme_get_autoplay_speed_options()) ? $shortcode->autoplay_speed : 7000 }}"
             data-owl-gap="0"
             data-owl-nav="true"
             data-owl-dots="false"
             data-owl-item="1"
             data-owl-item-xs="1"
             data-owl-item-sm="1"
             data-owl-item-md="1"
             data-owl-item-lg="1"
             data-owl-duration="1000"
             data-owl-mousedrag="on"
        >
            @foreach($posts as $post)
                <div class="slider-item" style="max-height: {{ $shortcode->max_height ?: 400 }}px">
                    <a href="{{ $post->url }}" class="slider-item-overlay">
                        {!! RvMedia::image($post->image, $post->name) !!}
                    </a>
                    <header class="slider-item-header">
                        <h2 class="slider-item-title">{!! BaseHelper::clean($post->name) !!}</h2>
                        @if ($post->description)
                            <span class="slider-item-description">{{ $post->description }}</span>
                        @endif
                        <section class="featured-home-post-item-date" style="color: #fff;">
                            <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>
                            @if (class_exists($post->author_type) && $post->author)
                                <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                    {{ $post->author->name }}
                                </span>
                            @endif
                        </section>
                    </header>
                </div>
            @endforeach
        </div>
    </section>
    <div class="clearfix"></div>
@endif
