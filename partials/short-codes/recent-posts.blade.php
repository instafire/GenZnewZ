@if (is_plugin_active('blog'))
    @php
        $primarySidebar = $withSidebar ? Theme::partial('primary-sidebar') : null;
    @endphp
    <section @if ($primarySidebar) class="primary fleft" @else style="width: 100%;" @endif>
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('before-recent-posts', ['style' => 'margin: 10px 0;']) !!}
        @endif
        <section class="block-post-wrap-item block-post1-wrap-item fleft bsize">
            <section class="block-post-wrap-head sidebar-item-head tf">
                @if ($title)
                    <span><i class="fa fa-tags" aria-hidden="true"></i>{{ $title }}</span>
                @else
                    <span><i class="fa fa-tags" aria-hidden="true"></i>{{ __('Recent posts') }}</span>
                @endif
            </section>
            <section class="block-post-wrap-content">
                @if (!$posts->isEmpty())
                    @foreach($posts as $post)
                        <section class="post1-item fleft">
                            <a class="post1-item-thumb thumb-full item-thumbnail"
                               href="{{ $post->url }}">
                                {!! RvMedia::image($post->image, $post->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                                <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                            </a>
                            <section class="post1-item-info">
                                <h2 class="post1-item-title">
                                    <a class="white-space"
                                       href="{{ $post->url }}">{{ $post->name }}</a>
                                </h2>
                                @if ($loop->first)
                                    <section class="featured-home-post-item-date" style="color: #444343;">
                                        <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($post->created_at) }}</span>
                                        @if (class_exists($post->author_type) && $post->author)
                                            <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                                {{ $post->author->name }}
                                            </span>
                                        @endif
                                    </section>
                                    <section class="post1-item-snippet">
                                        {{ Str::limit($post->description, 80) }}
                                    </section>
                                @endif
                            </section>
                        </section>
                    @endforeach
                @endif
                <section class="cboth"></section>
            </section>
        </section>
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('after-recent-posts', ['style' => 'margin: 10px 0;']) !!}
        @endif
        <section class="cboth"></section>
    </section>
    @if ($primarySidebar)
        {!! $primarySidebar !!}
    @endif
@endif