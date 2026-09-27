<section class="text-only-posts-section" style="margin: 30px 0;">
    <section class="container">
        <h2 class="block-title skeleton" style="font-size: 1.4rem; margin-bottom: 16px; border-bottom: 2px solid #ddd; padding-bottom: 8px;">
            <span style="background: #ddd; display: inline-block; width: 150px; height: 24px; border-radius: 4px;"></span>
        </h2>
        
        <div class="text-only-posts-list" style="display: flex; flex-direction: column; gap: 12px;">
            @for ($i = 0; $i < 6; $i++)
                <article class="text-only-post-item skeleton" style="
                    padding: 16px 20px;
                    background: #f0f0f0;
                    border-radius: 8px;
                    border-left: 4px solid #ddd;
                ">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <span style="background: #ddd; width: 60px; height: 12px; border-radius: 3px;"></span>
                        <span style="background: #ddd; width: 80px; height: 12px; border-radius: 3px;"></span>
                    </div>
                    <h3 style="margin: 0;">
                        <span style="background: #ddd; display: block; width: 80%; height: 20px; border-radius: 4px;"></span>
                    </h3>
                </article>
            @endfor
        </div>
    </section>
</section>
