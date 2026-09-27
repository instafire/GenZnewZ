@if (is_plugin_active('blog'))
    @if ($sidebar == 'footer_sidebar')
        <section class="footer-item">
            <section class="footer-item-head">
                <span>{{ $config['name'] }}</span>
            </section><!-- end .footer-item-head -->
            <section class="footer-item-content">
    @else
        <section class="sidebar-item">
            <section class="sidebar-item-head tf">
                <span><i class="fa fa-newspaper-o" aria-hidden="true"></i>{{ $config['name'] }}</span>
            </section><!-- end .sidebar-item-head -->
            <section class="sidebar-item-content">
    @endif
            @foreach(get_popular_posts($config['number_display']) as $post)
            <h2 class="post1-item-list">
                <a href="{{ $post->url }}"><i class="fa fa-caret-right" aria-hidden="true"></i>{!! BaseHelper::clean($post->name) !!}</a>
            </h2><!-- end .post1-item-list -->
            @endforeach
        </section><!-- end .sidebar-item-contentt -->
    </section><!-- end .sidebar-item -->
@endif
