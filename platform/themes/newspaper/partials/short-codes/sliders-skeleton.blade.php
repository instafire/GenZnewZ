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

    .skeleton-slider {
        position: relative;
        width: 100%;
        height: 400px;
        border-radius: 4px;
        overflow: hidden;
    }

    .skeleton-slider-item {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .skeleton-slider-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-slider-nav.prev {
        left: 20px;
    }

    .skeleton-slider-nav.next {
        right: 20px;
    }

    .skeleton-slider-dots {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
    }

    .skeleton-slider-dot {
        width: 10px;
        height: 10px;
        background: rgba(255,255,255,0.5);
        border-radius: 50%;
        animation: skeleton-loading 1.5s infinite;
    }

    @media (max-width: 768px) {
        .skeleton-slider {
            height: 300px;
        }
    }
</style>

<div class="skeleton-slider">
    <div class="skeleton-slider-item skeleton-loading-bg"></div>
    <div class="skeleton-slider-nav prev"></div>
    <div class="skeleton-slider-nav next"></div>
    <div class="skeleton-slider-dots">
        <span class="skeleton-slider-dot"></span>
        <span class="skeleton-slider-dot"></span>
        <span class="skeleton-slider-dot"></span>
        <span class="skeleton-slider-dot"></span>
    </div>
</div>