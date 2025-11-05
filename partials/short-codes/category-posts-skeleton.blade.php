<style>
    @keyframes skeleton-loading {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    .skeleton-loading-bg {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
    }

    .block-post-wrap-item.skeleton-block {
        margin-bottom: 30px;
    }

    .post1-item.skeleton-post {
        margin-bottom: 20px;
    }

    .post1-item.skeleton-post .skeleton-thumb {
        width: 100%;
        height: 200px;
        border-radius: 4px;
        overflow: hidden;
    }

    .sidebar-item-head {
        background: #f0f0f0;
    }

    .post1-item.skeleton-post .skeleton-title {
        height: 20px;
        background: #f0f0f0;
        border-radius: 4px;
        margin: 10px 0;
        animation: skeleton-loading 1.5s infinite;
    }

    .post1-item.skeleton-post .skeleton-meta {
        height: 14px;
        width: 200px;
        background: #f0f0f0;
        border-radius: 4px;
        margin-bottom: 5px;
        animation: skeleton-loading 1.5s infinite;
    }

    .post1-item.skeleton-post .skeleton-desc {
        height: 14px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-desc.w-80 {
        width: 80%;
    }
</style>

<section class="primary fleft">
    @for ($c = 0; $c < 2; $c++)
        <section class="block-post-wrap-item skeleton-block block-post1-wrap-item fleft bsize">
            <section class="block-post-wrap-head sidebar-item-head tf">
            </section>
            <section class="block-post-wrap-content">
                @for ($i = 0; $i < 3; $i++)
                    <section class="post1-item skeleton-post fleft">
                        <div class="post1-item-thumb thumb-full item-thumbnail skeleton-thumb">
                            <div class="skeleton-loading-bg" style="width: 100%; height: 100%;"></div>
                        </div>
                        <section class="post1-item-info">
                            <h2 class="post1-item-title">
                                <div class="skeleton-title"></div>
                            </h2>
                            @if ($i === 0)
                                <section class="featured-home-post-item-date">
                                    <div class="skeleton-meta"></div>
                                </section>
                                <section class="post1-item-snippet">
                                    <div class="skeleton-desc"></div>
                                    <div class="skeleton-desc w-80" style="margin-top: 5px;"></div>
                                </section>
                            @endif
                        </section>
                    </section>
                @endfor
                <section class="cboth"></section>
            </section>
        </section>
    @endfor
    <section class="cboth"></section>
</section>
