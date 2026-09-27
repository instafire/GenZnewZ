<section class="ai-text-spotlight" style="
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 40px 0;
    margin: 30px 0;
">
    <section class="container">
        <div class="ai-spotlight-header" style="
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            padding-bottom: 15px;
        ">
            <div class="skeleton" style="
                width: 50px;
                height: 50px;
                background: #333;
                border-radius: 12px;
            "></div>
            <div>
                <div class="skeleton" style="
                    width: 200px;
                    height: 24px;
                    background: #333;
                    border-radius: 4px;
                    margin-bottom: 8px;
                "></div>
                <div class="skeleton" style="
                    width: 150px;
                    height: 14px;
                    background: #333;
                    border-radius: 4px;
                "></div>
            </div>
        </div>

        <div class="ai-text-grid" style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        ">
            @for ($i = 0; $i < 5; $i++)
                <article class="ai-text-card skeleton" style="
                    background: rgba(255,255,255,0.05);
                    border-radius: 16px;
                    padding: 24px;
                    border-left: 4px solid #333;
                ">
                    <div style="
                        width: 80px;
                        height: 20px;
                        background: #333;
                        border-radius: 20px;
                        margin-bottom: 12px;
                    "></div>
                    <div style="
                        width: 100%;
                        height: 20px;
                        background: #333;
                        border-radius: 4px;
                        margin-bottom: 8px;
                    "></div>
                    <div style="
                        width: 90%;
                        height: 20px;
                        background: #333;
                        border-radius: 4px;
                        margin-bottom: 16px;
                    "></div>
                    <div style="
                        width: 100%;
                        height: 14px;
                        background: #333;
                        border-radius: 4px;
                        margin-bottom: 6px;
                    "></div>
                    <div style="
                        width: 85%;
                        height: 14px;
                        background: #333;
                        border-radius: 4px;
                        margin-bottom: 6px;
                    "></div>
                    <div style="
                        width: 70%;
                        height: 14px;
                        background: #333;
                        border-radius: 4px;
                    "></div>
                </article>
            @endfor
        </div>
    </section>
</section>
