@if (is_plugin_active('blog'))
    @php
        $primarySidebar = Theme::partial('primary-sidebar');
    @endphp
    <section @if ($primarySidebar) class="primary fleft" @endif>
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('before-category-posts', ['style' => 'margin: 10px 0;']) !!}
        @endif
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
                                    <section class="post1-item fleft">
                                        <a class="post1-item-thumb thumb-full item-thumbnail"
                                           href="{{ $postCategory->url }}">
                                            {!! RvMedia::image($postCategory->image, $postCategory->name, attributes: ['class' => 'attachment-full size-full wp-post-image']) !!}
                                            <div class="thumbnail-hoverlay main-color-1-bg"></div>
                                            <div class="thumbnail-hoverlay-icon"><i class="fa fa-search"></i></div>
                                        </a>
                                        <section class="post1-item-info">
                                            <h2 class="post1-item-title">
                                                <a class="white-space"
                                                   href="{{ $postCategory->url }}">{{ $postCategory->name }}</a>
                                            </h2>
                                            @if ($loop->first)
                                                <section class="featured-home-post-item-date" style="color: #444343;">
                                                    <span><i class="fa fa-calendar" aria-hidden="true"></i>{{ Theme::formatDate($postCategory->created_at) }}</span>
                                                    @if (class_exists($postCategory->author_type) && $postCategory->author)
                                                        <span><i class="fa fa-user-secret" aria-hidden="true"></i>
                                                            {{ $postCategory->author->name }}
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
        @if (is_plugin_active('ads'))
            {!! AdsManager::display('after-category-posts', ['style' => 'margin: 10px 0;']) !!}
        @endif
    </section>
    {!! $primarySidebar !!}
    <section class="cboth"></section>
@endif
