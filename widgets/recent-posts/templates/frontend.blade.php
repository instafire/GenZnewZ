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
                @foreach (get_recent_posts($config['number_display']) as $post)
                    <section class="sidebar-new-item">
                        <section class="sidebar-new-item-thumb fleft thumb-full">
                            {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image', 'size' => 'thumb']) !!}
                        </section>
                        <!-- end .sidebar-new-item-thumb -->
                        <section class="sidebar-new-item-info">
                            <h2 class="post1-item-list">
                                <a class="white-space" href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                            </h2><!-- end .post1-item-list -->
                            <section class="sidebar-new-item-des">
                                <i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}
                            </section><!-- end .sidebar-new-item-des -->
                        </section><!-- end .sidebar-new-item-info -->
                        <section class="cboth"></section><!-- end .cboth -->
                    </section><!-- end .sidebar-new-item -->
                @endforeach
            </section><!-- end .footer-item-content -->
    </section><!-- end .footer-item -->
@endif
