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

    .skeleton-gallery-title {
        height: 24px;
        width: 150px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    .gallery-item.skeleton-gallery {
        margin-bottom: 20px;
    }

    .gallery-item.skeleton-gallery .skeleton-img {
        width: 100%;
        height: 200px;
        border-radius: 4px;
        overflow: hidden;
    }

    .gallery-item.skeleton-gallery .skeleton-name {
        height: 18px;
        background: #f0f0f0;
        border-radius: 4px;
        margin: 10px 0 5px;
        animation: skeleton-loading 1.5s infinite;
    }

    .gallery-item.skeleton-gallery .skeleton-meta {
        height: 14px;
        width: 150px;
        background: #f0f0f0;
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }
</style>

<div class="clearfix"></div>
<section class="block-post-wrap-item block-post1-wrap-item bsize" style="width: 100%;">
    <section class="block-post-wrap-head sidebar-item-head tf">
        <div class="skeleton-gallery-title"></div>
    </section>
    <section class="block-post-wrap-content">
        <div class="gallery-wrap">
            @for ($i = 0; $i < 6; $i++)
                <div class="gallery-item skeleton-gallery">
                    <div class="img-wrap skeleton-img">
                        <div class="skeleton-loading-bg" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
            @endfor
            <div class="cboth"></div>
        </div>
    </section>
</section>
