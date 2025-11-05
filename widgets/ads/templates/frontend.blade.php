@if ($config['image_url'])
    <section class="sidebar-item sidebar-item-ads">
        <a href="{{ $config['image_link'] }}" @if ($config['image_new_tab'] == 1) target="_blank" @endif>{!! RvMedia::image(str_replace('/storage/', '/', $config['image_url']), 'Thumb ads') !!}</a>
    </section><!-- end .sidebar-item -->
@endif
