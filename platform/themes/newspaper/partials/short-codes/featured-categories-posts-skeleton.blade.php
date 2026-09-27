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

    .skeleton-featured-cat-title {
        height: 24px;
        width: 200px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-featured-column {
        width: 50%;
        float: left;
        padding-right: 15px;
    }

    .skeleton-main-post {
        margin-bottom: 20px;
    }

    .skeleton-main-post .skeleton-thumb {
        width: 100%;
        height: 200px;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .skeleton-main-post .skeleton-title {
        height: 20px;
        background: #f0f0f0;
        border-radius: 4px;
        margin-bottom: 10px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-main-post .skeleton-meta {
        height: 14px;
        width: 200px;
        background: #f0f0f0;
        border-radius: 4px;
        margin-bottom: 5px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-main-post .skeleton-desc {
        height: 14px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-horizontal-post {
        margin-bottom: 15px;
        clear: both;
    }

    .skeleton-horizontal-thumb {
        float: left;
        width: 100px;
        height: 70px;
        margin-right: 10px;
        border-radius: 4px;
        overflow: hidden;
    }

    .skeleton-horizontal-info {
        overflow: hidden;
    }

    .skeleton-horizontal-title {
        height: 16px;
        background: #f0f0f0;
        border-radius: 4px;
        margin-bottom: 5px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-horizontal-meta {
        height: 12px;
        width: 120px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }
</style>

<section class="primary fleft">
    <section class="block-post-wrap-item block-post1-wrap-item fleft bsize">
        <section class="block-post-wrap-head sidebar-item-head tf">
            <div class="skeleton-featured-cat-title"></div>
        </section>
        <section class="block-post-wrap-content">
            @for ($col = 0; $col < 2; $col++)
                <section class="skeleton-featured-column">
                    <section class="post1-item skeleton-main-post fleft">
                        <div class="post1-item-thumb thumb-full item-thumbnail skeleton-thumb">
                            <div class="skeleton-loading-bg" style="width: 100%; height: 100%;"></div>
                        </div>
                        <section class="post1-item-info">
                            <h2 class="post1-item-title">
                                <div class="skeleton-title"></div>
                            </h2>
                            <section class="featured-home-post-item-date">
                                <div class="skeleton-meta"></div>
                            </section>
                            <section class="post1-item-snippet">
                                <div class="skeleton-desc"></div>
                                <div class="skeleton-desc" style="width: 80%; margin-top: 5px;"></div>
                            </section>
                        </section>
                    </section>
                    @for ($i = 0; $i < 2; $i++)
                        <section class="skeleton-horizontal-post">
                            <div class="skeleton-horizontal-thumb">
                                <div class="skeleton-loading-bg" style="width: 100%; height: 100%;"></div>
                            </div>
                            <section class="skeleton-horizontal-info">
                                <h3 class="horizontal-post-title">
                                    <div class="skeleton-horizontal-title"></div>
                                </h3>
                                <section class="horizontal-post-date">
                                    <div class="skeleton-horizontal-meta"></div>
                                </section>
                            </section>
                            <div class="cboth"></div>
                        </section>
                    @endfor
                </section>
            @endfor
            <section class="cboth"></section>
        </section>
    </section>
    <section class="cboth"></section>
</section>
