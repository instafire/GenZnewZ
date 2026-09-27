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
    
    .gallery-wrap .gallery-item {
        width: 25%;
        float: left;
        position: relative;
        padding: 0 5px;
        margin-bottom: 10px;
    }
    
    .gallery-wrap .gallery-item .skeleton-img-wrap {
        width: 100%;
        padding-bottom: 66.67%;
        position: relative;
        overflow: hidden;
        border-radius: 4px;
    }
    
    .gallery-wrap .gallery-item .skeleton-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .gallery-wrap .gallery-item .skeleton-title {
        height: 20px;
        margin-top: 10px;
        margin-bottom: 5px;
        border-radius: 4px;
    }
    
    .gallery-wrap .gallery-item .skeleton-author {
        height: 16px;
        width: 60%;
        border-radius: 4px;
    }
</style>

<div class="gallery-wrap">
    @for ($i = 0; $i < 8; $i++)
        <div class="gallery-item">
            <div class="skeleton-img-wrap">
                <div class="skeleton-img skeleton-loading-bg"></div>
            </div>
            <div class="gallery-detail">
                <div class="skeleton-title skeleton-loading-bg"></div>
                <div class="skeleton-author skeleton-loading-bg"></div>
            </div>
        </div>
    @endfor
</div>
<div style="clear: both"></div>