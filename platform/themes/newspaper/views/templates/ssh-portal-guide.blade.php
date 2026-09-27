@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('GenZ NewZ SSH Portal Guide - Read News via Terminal');
    SeoHelper::setDescription('Learn how to access the GenZ NewZ SSH portal, browse categories, search stories, and read articles directly from terminal.');
@endphp

<section class="ssh-guide-page">
    <div class="ssh-shell">
        <header class="ssh-hero">
            <p class="ssh-eyebrow">Terminal Access</p>
            <h1>How To Use The GenZ NewZ SSH Portal</h1>
            <p class="ssh-lede">
                Connect by SSH, open the interactive news menu, browse categories, search for keywords,
                and read full articles directly in your terminal.
            </p>
            <div class="ssh-hero-actions">
                <a href="{{ route('public.single') }}" class="btn-light">Back to Homepage</a>
            </div>
        </header>

        <section class="ssh-panel">
            <h2>1. Connect To SSH Portal</h2>
            <p>Open terminal (or PowerShell on Windows) and run:</p>
            <pre><code>ssh sshnews@genznewz.com</code></pre>
            <p>If prompted for password, press <strong>Enter</strong>.</p>
        </section>

        <section class="ssh-panel">
            <h2>2. Portal Menu</h2>
            <p>After login, you will see options:</p>
            <ul>
                <li>Latest headlines</li>
                <li>Featured stories</li>
                <li>Browse by category</li>
                <li>Search articles</li>
                <li>Exit</li>
            </ul>
            <p>Type the menu number and press Enter.</p>
        </section>

        <section class="ssh-panel">
            <h2>3. Read Articles</h2>
            <p>
                Select an article from the list to open it. The portal shows publication date, categories,
                article URL, summary, and paragraph-formatted content.
            </p>
        </section>

        <section class="ssh-panel">
            <h2>4. Troubleshooting</h2>
            <ul>
                <li>If connection times out, check network/firewall rules for SSH access.</li>
                <li>If your terminal displays old data, reconnect and retry.</li>
                <li>If connection fails, verify network allows outbound SSH.</li>
            </ul>
            <pre><code>Test-NetConnection genznewz.com -Port 22</code></pre>
        </section>
    </div>
</section>

<style>
.ssh-guide-page {
    background: #f7f7f3;
    padding: 32px 16px 72px;
    color: #101828;
}

.ssh-shell {
    max-width: 980px;
    margin: 0 auto;
}

.ssh-hero {
    background: linear-gradient(160deg, #0b1220 0%, #111827 75%, #172033 100%);
    color: #f8fafc;
    border: 1px solid #1f2937;
    border-radius: 14px;
    padding: 30px;
}

.ssh-eyebrow {
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1.4px;
    opacity: 0.9;
    margin: 0 0 8px;
}

.ssh-hero h1 {
    margin: 0 0 10px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 3vw, 2.5rem);
    line-height: 1.15;
}

.ssh-lede {
    margin: 0;
    color: #dbe2ea;
    max-width: 760px;
}

.ssh-hero-actions {
    margin-top: 16px;
}

.btn-light {
    display: inline-block;
    text-decoration: none;
    background: #f97316;
    color: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-light:hover {
    background: #ea580c;
}

.ssh-panel {
    margin-top: 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
}

.ssh-panel h2 {
    margin: 0 0 10px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
}

.ssh-panel p {
    margin: 0 0 10px;
    line-height: 1.55;
}

.ssh-panel ul {
    margin: 0 0 10px 18px;
}

.ssh-panel li {
    margin-bottom: 6px;
}

.ssh-panel pre {
    margin: 8px 0 0;
    background: #0b1220;
    color: #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    overflow-x: auto;
}

.ssh-panel code {
    font-family: 'JetBrains Mono', Consolas, monospace;
    font-size: 0.92rem;
}

@media (max-width: 768px) {
    .ssh-hero {
        padding: 22px;
    }

    .ssh-panel {
        padding: 16px;
    }
}
</style>
