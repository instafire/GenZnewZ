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

    .featured-home-post-item.skeleton-item {
        position: relative;
        height: 400px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .featured-home-post-item.skeleton-item .skeleton-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .featured-home-post-item.skeleton-item .featured-home-post-item-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20px;
        background: rgba(0,0,0,0.15);
    }

    .skeleton-title {
        height: 32px;
        background: rgba(255,255,255,0.3);
        border-radius: 4px;
        margin-bottom: 15px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-meta {
        height: 16px;
        width: 250px;
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
        margin-bottom: 10px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-desc {
        height: 16px;
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    @media (max-width: 768px) {
        .featured-home-post-item.skeleton-item {
            height: 300px;
        }
    }
</style>

<section class="featured-home-post">
    <section class="container">
        @for ($i = 0; $i < 7; $i++)
            <section class="featured-home-post-item skeleton-item thumb-full fleft">
                <div class="skeleton-img skeleton-loading-bg"></div>
                <section class="featured-home-post-item-info bsize">
                    <h2 class="featured-home-post-item-title">
                        <div class="skeleton-title"></div>
                    </h2>
                    <section class="featured-home-post-item-date">
                        <div class="skeleton-meta"></div>
                    </section>
                    <section class="featured-home-post-item-des">
                        <div class="skeleton-desc"></div>
                    </section>
                </section>
            </section>
        @endfor
        <section class="cboth"></section>
    </section>
</section>
