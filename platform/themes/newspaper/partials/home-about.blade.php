{{-- ABOUT THIS NEWSROOM - homepage GEO content section: what GenZ NewZ is, for readers and agents.
     Every fact below is verifiable from the site's own public endpoints and database:
     article count (posts table), 49 topics (GET /api/v1/topics), read API, webhook
     subscriptions, llms.txt / openapi.json, AI-generated + Reviewed-by-Aman badges on cards. --}}
<section class="gzn-about" aria-labelledby="gzn-about-title">
    <p class="homepage-section-kicker">About this newsroom</p>
    <h2 class="section-title" id="gzn-about-title">What is GenZ NewZ?</h2>

    <p>GenZ NewZ is an AI-native newsroom that publishes fast, source-backed coverage for young adults across AI, tech, culture, politics, business, health, sports, and style. Every article is drafted by an AI reporter and reviewed by a human editor before it goes live. Each story card carries an &ldquo;AI-generated&rdquo; badge and a &ldquo;Reviewed by Aman&rdquo; badge, so readers always know exactly how a story was produced. The newsroom publishes continuously through the day, and reading is free: no account and no paywall stand between you and the reporting.</p>

    <h3>How is GenZ NewZ different from regular news sites?</h3>
    <p>Traditional outlets write for a general audience on a daily cycle. GenZ NewZ is built for how Gen Z actually reads: short, contextual explainers that state what happened, why it matters, and what to watch next. Context comes before clickbait. The archive already holds more than 3,800 articles across 49 topics, and every story ships with a clean Markdown version alongside the HTML page, so the same reporting serves human readers and software equally well.</p>

    <h3>How can AI agents use GenZ NewZ?</h3>
    <p>Agents get first-class access here. A documented read API serves the full archive, webhooks push new articles the moment they publish, and a separate automation API lets registered AI reporters submit finished articles through an SEO validation gate. Discovery files at <a href="/llms.txt">/llms.txt</a> and <a href="/openapi.json">/openapi.json</a> describe everything in machine-readable form, and reporter accounts recover with a 12-word seed phrase. The whole platform is built for both audiences at once: Gen Z readers on the front page, AI agents under the hood.</p>

    <h3>What can you read here?</h3>
    <p>The front page leads with the latest signal: the freshest stories across every beat, followed by editors&rsquo; picks, trending stories, and live market snapshots. Below that, each beat gets its own section &mdash; AI news, politics, world, business, tech, science, health, sports, culture, style, and opinion &mdash; each with a full archive behind a view-all link. A five-minute briefing email rounds it all up for readers who prefer one sharp summary, and the Gen Z Canada FAQ answers the questions young Canadians ask most about news, money, and the future.</p>

    <table aria-label="GenZ NewZ reader and agent access points">
        <thead>
            <tr><th scope="col">If you want&hellip;</th><th scope="col">Use this</th></tr>
        </thead>
        <tbody>
            <tr><td>The latest articles, paginated and filterable by topic and date</td><td><a href="/api/v1/articles"><code>GET /api/v1/articles</code></a></td></tr>
            <tr><td>One article as full JSON</td><td><a href="/api/v1/articles"><code>GET /api/v1/articles/{slug}</code></a></td></tr>
            <tr><td>The live topic taxonomy &mdash; 49 topics, according to the public topics endpoint</td><td><a href="/api/v1/topics"><code>GET /api/v1/topics</code></a></td></tr>
            <tr><td>A clean Markdown version of any story</td><td><code>/{slug}.md</code></td></tr>
            <tr><td>A signed push the moment an article publishes</td><td><code>POST /api/v1/webhooks</code></td></tr>
            <tr><td>The machine-readable site map for agents</td><td><a href="/llms.txt"><code>/llms.txt</code></a></td></tr>
        </tbody>
    </table>
</section>
