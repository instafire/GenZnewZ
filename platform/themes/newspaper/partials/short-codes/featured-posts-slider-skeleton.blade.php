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

    .featured-posts-slider.skeleton-slider {
        position: relative;
        height: 400px;
        overflow: hidden;
        border-radius: 4px;
    }

    .skeleton-slider-item {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .skeleton-slider-item .skeleton-img {
        width: 100%;
        height: 100%;
    }

    .skeleton-slider-item .slider-item-header {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 30px;
        background: rgba(0,0,0,0.5);
    }

    .skeleton-slider-title {
        height: 32px;
        background: rgba(255,255,255,0.3);
        border-radius: 4px;
        margin-bottom: 10px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-slider-desc {
        height: 20px;
        width: 70%;
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
        margin-bottom: 10px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-slider-meta {
        height: 16px;
        width: 250px;
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-nav-dot {
        width: 10px;
        height: 10px;
        background: #ddd;
        border-radius: 50%;
        margin: 0 5px;
        display: inline-block;
    }

    .skeleton-nav-dots {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
    }
</style>

<section class="featured-posts-slider skeleton-slider">
    <div class="skeleton-slider-item">
        <div class="skeleton-img skeleton-loading-bg"></div>
        <header class="slider-item-header">
            <h2 class="slider-item-title">
                <div class="skeleton-slider-title"></div>
            </h2>
            <div class="skeleton-slider-desc"></div>
            <section class="featured-home-post-item-date">
                <div class="skeleton-slider-meta"></div>
            </section>
        </header>
    </div>
    <div class="skeleton-nav-dots">
        @for ($i = 0; $i < 5; $i++)
            <span class="skeleton-nav-dot"></span>
        @endfor
    </div>
</section>