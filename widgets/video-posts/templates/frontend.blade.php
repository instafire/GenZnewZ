@if (is_plugin_active('blog'))
    @php
        $posts = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)->advancedGet([
            'take'      => $config['number_display'],
            'condition' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED, 'format_type' => 'video'],
            'order_by'  => ['created_at' => 'DESC'],
            'with'      => ['slugable'],
        ]);
    @endphp
    @if ($posts->isNotEmpty())
        @if ($sidebar == 'footer_sidebar')
            <section class="footer-item">
                <section class="footer-item-head">
                    <span>{{ $config['name'] }}</span>
                </section><!-- end .footer-item-head -->
                <section class="footer-item-content">
        @else
            <section class="sidebar-item">
                <section class="sidebar-item-head tf">
                    <span><i class="fa fa-video-camera" aria-hidden="true"></i>{{ $config['name'] }}</span>
                </section><!-- end .sidebar-item-head -->
                <section class="sidebar-item-content">
        @endif
                @foreach($posts as $post)
                    <a class="sidebar-video-item form-popup thumb-full fleft" href="#video-popup-{{ $post->id }}" rel="video-popup">
                        {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image', 'size' => 'thumb']) !!} <i class="fa fa-play" aria-hidden="true"></i>
                        <section class="popup-hidden">
                            <section id="video-popup-{{ $post->id }}" class="sidebar-video-item-popup">
                                <p>
                                    <iframe src="{{ str_replace('watch?v=', 'embed/', MetaBox::getMetaData($post, 'video_link', true)) }}" width="560" height="315"
                                            frameborder="0" allowfullscreen="allowfullscreen"></iframe>
                                </p>
                            </section><!-- end .sidebar-video-item-popup -->
                        </section><!-- end .popup-hidden -->
                    </a><!-- end .sidebar-video-item -->
                @endforeach
                <section class="cboth"></section><!-- end .cboth -->
            </section><!-- end .sidebar-item-content -->
        </section><!-- end .sidebar-item -->
    @endif
@endif
