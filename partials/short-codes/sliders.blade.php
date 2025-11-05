@if (count($sliders) > 0)
    <div class='owl-slider owl-carousel carousel--nav inside'
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
         data-owl-mousedrag="on">
        @foreach($sliders as $slider)
        <div class='slider-item' style="max-height: {{ $shortcode->max_height ?: 400 }}px">
            @if ($slider->link) <a href='{{ $slider->link }}' class='slider-item-overlay'>@endif{!! RvMedia::image($slider->image, $slider->title) !!}@if ($slider->link) </a> @endif
        @if ($slider->title || $slider->description)
            <header class='slider-item-header'>
                @if ($slider->title)
                <h2 class='slider-item-title'>{{ $slider->title }}</h2>
                @endif
                @if ($slider->description)
                <span class='slider-item-description'>{{ $slider->description }}</span>
                @endif
            </header>
            @endif
        </div>
        @endforeach
    </div>
    <div class="clearfix"></div>
@endif
