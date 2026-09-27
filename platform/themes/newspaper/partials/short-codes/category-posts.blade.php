@if (is_plugin_active('blog'))
    @php
        $primarySidebar = Theme::partial('primary-sidebar');
    @endphp
    <section @if ($primarySidebar) class="primary fleft" @endif>
        @foreach ($categories as $category)
            @php
                $allRelatedCategoryIds = array_unique(array_merge(app(\Botble\Blog\Repositories\Interfaces\CategoryInterface::class)->getAllRelatedChildrenIds($category), [$category->id]));

                $postCategories = app(\Botble\Blog\Repositories\Interfaces\PostInterface::class)->getByCategory($allRelatedCategoryIds, 0, 6);
            @endphp
            @if (count($postCategories) > 0)
                    <section class="block-post-wrap-item block-post1-wrap-item fleft bsize">
                        <section class="block-post-wrap-head sidebar-item-head tf">
                            <a class="white-space" href="{{ $category->url }}">
                                <span><i class="fa fa-tags" aria-hidden="true"></i>{!! BaseHelper::clean($category->name) !!}</span>
                            </a>
                        </section>
                        <section class="block-post-wrap-content">
                            @foreach($postCategories as $postCategory)
                                @if ($loop->index < 3)
                                    <section class="post1-item fleft {{ !$postCategory->image ? 'text-only-item' : '' }}">
                                        @if ($postCategory->image)
                                            <a class="post1-item-thumb thumb-full item-thumbnail"
                                               href="{{ $postCategory->url }}">
                                                {!! RvMedia::image($postCategory->image, $postCategory->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                                                <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                                <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                                            </a>
                                        @else
                                            {{-- Text-only post styling --}}
                                            <a class="post1-item-thumb thumb-full item-thumbnail text-only-thumb"
                                               href="{{ $postCategory->url }}"
                                               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;">
                                                <div style="
                                                    width: 100%;
                                                    height: 100%;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    color: white;
                                                    font-size: 2rem;
                                                ">
                                                    <i class="fa fa-align-left" aria-hidden="true"></i>
                                                </div>
                                                <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                                <div class="thumbnail-hoverlay-icon"><i class="fa fa-arrow-right"></i></div>
                                            </a>
                                        @endif
                                        <section class="post1-item-info">
                                            @if ($postCategory->format_type === 'text-only' && !$postCategory->image)
                                                <span class="format-badge" style="
                                                    display: inline-block;
                                                    padding: 2px 6px;
                                                    background: #007bff;
                                                    color: white;
                                                    font-size: 0.65rem;
                                                    border-radius: 3px;
                                                    margin-bottom: 4px;
                                                    text-transform: uppercase;
                                                ">
                                                    {{ __('Quick Read') }}
                                                </span>
                                            @endif
                                            <h2 class="post1-item-title">
                                                <a class="white-space"
                                                   href="{{ $postCategory->url }}">{{ $postCategory->name }}</a>
                                            </h2>
                                            @if ($loop->first)
                                                <section class="featured-home-post-item-date" style="color: #444343;">
                                                    <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($postCategory->created_at) }}</span>
                                                    @if (class_exists($postCategory->author_type) && $postCategory->author)
                                                        <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                                            @if ($postCategory->author->url)
                                                                <a href="{{ $postCategory->author->url }}" class="author-link-name">{{ $postCategory->author->name }}</a>
                                                            @else
                                                                {{ $postCategory->author->name }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </section>
                                            @endif
                                            <section class="post1-item-des">
                                                {{ $postCategory->description }}
                                            </section>
                                        </section>
                                    </section>
                                @endif
                            @endforeach
                            <section class="cboth post1-item-bottom"></section>
                            @foreach($postCategories as $postCategory)
                                @if ($loop->index >= 3)
                                    <h2 class="post1-item-list">
                                        <a class="white-space"
                                           href="{{ $postCategory->url }}"><i
                                                class="fa fa-caret-right" aria-hidden="true"></i>{{ $postCategory->name }}</a>
                                    </h2>
                                @endif
                            @endforeach
                        </section>
                    </section>
            @endif
        @endforeach
    </section>
    {!! $primarySidebar !!}
    <section class="cboth"></section>
@endif
