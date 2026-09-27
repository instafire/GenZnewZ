@php
    use Botble\SeoHelper\Facades\SeoHelper;
    $pageName = isset($page) ? $page->name : 'Cookies Are Stupid (But We Use Them)';
    $pageDescription = isset($page) && $page->description ? $page->description : 'Our honest, no-BS guide to cookies. Learn what they are, why they\'re kind of annoying, and how we use them (minimally).';
    SeoHelper::setTitle($pageName . ' | GenZ NewZ');
    SeoHelper::setDescription($pageDescription);
@endphp
<div class="nyt-cookies-container">
    <div class="cookies-hero">
        <span class="hero-emoji">🍪</span>
        <h1 class="cookies-title">Cookies Are Stupid</h1>
        <p class="cookies-subtitle">(But we use them anyway — here's the honest truth)</p>
    </div>

    <div class="cookies-content">
        <div class="cookies-grid">
            <main class="cookies-main">
                @if(isset($page) && !empty(strip_tags($page->content)))
                <div class="page-custom-content" style="margin-bottom: 40px;">
                    {!! $page->content !!}
                </div>
                @endif

                <div class="honest-intro">
                    <p class="intro-text">Let's be real: cookie banners are annoying. "We value your privacy" pop-ups feel performative. And trying to find the "Reject All" button that's hidden in microscopic gray text? Infuriating.</p>
                    <p class="intro-text">So here's our promise: We'll tell you exactly what we're doing, why we're doing it, and how to make it stop. No corporate doublespeak. Just the facts.</p>
                </div>

                <section id="what" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">01</span>
                        <h2>What Even Are Cookies?</h2>
                    </div>
                    <div class="section-body">
                        <p>Cookies are tiny text files websites store on your device. Think of them like sticky notes your browser leaves for itself:</p>
                        <ul class="plain-list">
                            <li><strong>"This person likes dark mode"</strong> — so we don't blind you with light mode every time you visit</li>
                            <li><strong>"This person is logged in"</strong> — so you don't have to enter your password 47 times a day</li>
                            <li><strong>"This person read the AI article"strong> — so we can suggest related content you might actually like</li>
                        </ul>
                        <p>They're not inherently evil. They're just... a bit nosy.</p>
                    </div>
                </section>

                <section id="we-use" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">02</span>
                        <h2>Cookies We Actually Use</h2>
                    </div>
                    <div class="section-body">
                        <div class="cookie-cards">
                            <div class="cookie-card essential">
                                <div class="card-badge">Can't Turn Off</div>
                                <h3>Essential Cookies</h3>
                                <p>These make the site work. Without them, things break.</p>
                                <ul>
                                    <li>Login sessions</li>
                                    <li>Security tokens</li>
                                    <li>Basic site functionality</li>
                                </ul>
                            </div>

                            <div class="cookie-card preference">
                                <div class="card-badge toggleable">Optional</div>
                                <h3>Preference Cookies</h3>
                                <p>Remember your settings so you don't have to reset them every visit.</p>
                                <ul>
                                    <li>Dark/light mode</li>
                                    <li>Font size preferences</li>
                                    <li>Language settings</li>
                                </ul>
                            </div>

                            <div class="cookie-card analytics">
                                <div class="card-badge toggleable">Optional</div>
                                <h3>Analytics Cookies</h3>
                                <p>Help us understand what content people actually read (so we can make more of it).</p>
                                <ul>
                                    <li>Page views</li>
                                    <li>Popular articles</li>
                                    <li>Site performance</li>
                                </ul>
                            </div>

                            <div class="cookie-card marketing">
                                <div class="card-badge off">Disabled by Default</div>
                                <h3>Marketing Cookies</h3>
                                <p>We don't use these. We don't sell your data to advertisers. We're not monsters.</p>
                                <ul>
                                    <li>Tracking pixels: <strong>Nope</strong></li>
                                    <li>Ad targeting: <strong>Nope</strong></li>
                                    <li>Data selling: <strong>Nope</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="third-party" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">03</span>
                        <h2>Third-Party Cookies (The Sketchy Ones)</h2>
                    </div>
                    <div class="section-body">
                        <p>We try to minimize these, but some are unavoidable:</p>
                        <div class="third-party-table">
                            <div class="table-row header">
                                <span>Service</span>
                                <span>What They Do</span>
                                <span>Can You Avoid Them?</span>
                            </div>
                            <div class="table-row">
                                <span><strong>Google Analytics</strong></span>
                                <span>See how people use our site</span>
                                <span>Yes — use the opt-out below</span>
                            </div>
                            <div class="table-row">
                                <span><strong>Cloudflare</strong></span>
                                <span>Protect us from hackers</span>
                                <span>No — essential for security</span>
                            </div>
                            <div class="table-row">
                                <span><strong>Social Media</strong></span>
                                <span>Share buttons, embeds</span>
                                <span>Yes — don't click them</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="control" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">04</span>
                        <h2>How to Control Your Cookies</h2>
                    </div>
                    <div class="section-body">
                        <div class="control-options">
                            <div class="control-option">
                                <h3>🌐 Browser Settings</h3>
                                <p>Every browser lets you block or delete cookies. Here's how:</p>
                                <div class="browser-links">
                                    <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a>
                                    <a href="https://support.mozilla.org/kb/enable-and-disable-cookies-website-preferences" target="_blank" rel="noopener">Firefox</a>
                                    <a href="https://support.apple.com/guide/safari/manage-cookies-websites-sfri11471/mac" target="_blank" rel="noopener">Safari</a>
                                    <a href="https://support.microsoft.com/help/4027947/microsoft-edge-delete-cookies" target="_blank" rel="noopener">Edge</a>
                                </div>
                            </div>

                            <div class="control-option">
                                <h3>🚫 Global Opt-Out</h3>
                                <p>Industry tools that work across websites:</p>
                                <ul>
                                    <li><a href="https://optout.networkadvertising.org/" target="_blank" rel="noopener">Network Advertising Initiative</a></li>
                                    <li><a href="https://optout.aboutads.info/" target="_blank" rel="noopener">Digital Advertising Alliance</a></li>
                                </ul>
                            </div>

                            <div class="control-option">
                                <h3>🕵️ Privacy-Focused Browsers</h3>
                                <p>Want to really avoid tracking? Try these:</p>
                                <ul>
                                    <li><a href="https://brave.com" target="_blank" rel="noopener">Brave</a> — Blocks trackers by default</li>
                                    <li><a href="https://firefox.com" target="_blank" rel="noopener">Firefox</a> — Enhanced Tracking Protection</li>
                                    <li><a href="https://torproject.org" target="_blank" rel="noopener">Tor Browser</a> — Maximum anonymity</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="our-promise" class="cookie-section highlight-section">
                    <div class="section-header">
                        <span class="section-number">05</span>
                        <h2>Our Promise to You</h2>
                    </div>
                    <div class="section-body">
                        <div class="promise-box">
                            <div class="promise-item">
                                <span class="promise-icon">✓</span>
                                <div>
                                    <h4>We don't sell your data</h4>
                                    <p>Never have, never will.</p>
                                </div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">✓</span>
                                <div>
                                    <h4>We minimize tracking</h4>
                                    <p>Only what's necessary to improve the site.</p>
                                </div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">✓</span>
                                <div>
                                    <h4>We're transparent</h4>
                                    <p>This page exists. That says something.</p>
                                </div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">✓</span>
                                <div>
                                    <h4>You have control</h4>
                                    <p>Block, delete, or manage cookies however you want.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="changes" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">06</span>
                        <h2>When This Changes</h2>
                    </div>
                    <div class="section-body">
                        <p>If we change our cookie practices, we'll:</p>
                        <ol>
                            <li>Update this page (with a new "Last Updated" date)</li>
                            <li>Post a notice on the site</li>
                            <li>Not be sneaky about it</li>
                        </ol>
                        <p class="last-updated">Last Updated: February 12, 2026</p>
                    </div>
                </section>

                <section id="contact" class="cookie-section">
                    <div class="section-header">
                        <span class="section-number">07</span>
                        <h2>Questions? Concerns? Cookies?</h2>
                    </div>
                    <div class="section-body">
                        <p>Found a cookie we didn't disclose? Think our tracking is excessive? Just want to say hi?</p>
                        <div class="contact-cta">
                            <a href="/contact" class="cta-button">Contact Us</a>
                            <a href="mailto:privacy@genznewz.com" class="cta-link">Or email privacy@genznewz.com</a>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="cookies-sidebar">
                <div class="sidebar-box quick-nav">
                    <h3>Jump To</h3>
                    <nav>
                        <a href="#what">What are cookies?</a>
                        <a href="#we-use">What we use</a>
                        <a href="#third-party">Third parties</a>
                        <a href="#control">How to control</a>
                        <a href="#our-promise">Our promise</a>
                    </nav>
                </div>

                <div class="sidebar-box cookie-settings">
                    <h3>🎛️ Cookie Settings</h3>
                    <p>Manage your preferences:</p>
                    <div class="toggle-list">
                        <label class="toggle-item">
                            <span>Essential</span>
                            <span class="toggle always-on">Always On</span>
                        </label>
                        <label class="toggle-item">
                            <span>Preferences</span>
                            <input type="checkbox" checked onchange="alert('This would toggle preferences in a real implementation')">
                        </label>
                        <label class="toggle-item">
                            <span>Analytics</span>
                            <input type="checkbox" checked onchange="alert('This would toggle analytics in a real implementation')">
                        </label>
                        <label class="toggle-item">
                            <span>Marketing</span>
                            <span class="toggle always-off">Always Off</span>
                        </label>
                    </div>
                    <button class="save-btn" onclick="alert('This would save your preferences')">Save Preferences</button>
                </div>

                <div class="sidebar-box related">
                    <h3>Related</h3>
                    <a href="/privacy-policy" class="related-link">
                        <strong>Privacy Policy</strong>
                        <span>The full legal version →</span>
                    </a>
                    <a href="/terms-of-service" class="related-link">
                        <strong>Terms of Use</strong>
                        <span>The rules we all follow →</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
/* Cookies Container */
.nyt-cookies-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    font-family: Georgia, 'Times New Roman', serif;
}

/* Hero Section */
.cookies-hero {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #f8f8f8 0%, #fff 100%);
    border: 2px solid #121212;
    border-radius: 16px;
    margin-bottom: 60px;
}

.hero-emoji {
    font-size: 5rem;
    display: block;
    margin-bottom: 20px;
}

.cookies-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    color: #121212;
    margin: 0 0 16px 0;
    line-height: 1.1;
}

.cookies-subtitle {
    font-size: 1.3rem;
    color: #666;
    font-style: italic;
    margin: 0;
}

/* Content Grid */
.cookies-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 60px;
}

/* Honest Intro */
.honest-intro {
    background: #f8f8f8;
    border-left: 4px solid #121212;
    padding: 28px 32px;
    margin-bottom: 48px;
    border-radius: 0 8px 8px 0;
}

.intro-text {
    font-size: 1.15rem;
    line-height: 1.7;
    color: #333;
    margin: 0 0 16px 0;
}

.intro-text:last-child {
    margin-bottom: 0;
}

/* Cookie Sections */
.cookie-section {
    margin-bottom: 60px;
    padding-bottom: 60px;
    border-bottom: 1px solid #e2e2e2;
}

.cookie-section:last-child {
    border-bottom: none;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.section-number {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #666;
    padding: 6px 12px;
    border: 2px solid #666;
    border-radius: 4px;
}

.cookie-section h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #121212;
    margin: 0;
}

.section-body p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #333;
    margin: 0 0 16px 0;
}

.plain-list {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.plain-list li {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #333;
    margin-bottom: 12px;
    padding-left: 24px;
    position: relative;
}

.plain-list li::before {
    content: "→";
    position: absolute;
    left: 0;
    color: #326891;
    font-weight: bold;
}

/* Cookie Cards */
.cookie-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-top: 28px;
}

.cookie-card {
    background: #fff;
    border: 2px solid #e2e2e2;
    border-radius: 12px;
    padding: 28px;
    position: relative;
}

.card-badge {
    position: absolute;
    top: -12px;
    right: 16px;
    padding: 6px 12px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 4px;
}

.card-badge.toggleable {
    background: #e3f2fd;
    color: #1976d2;
}

.card-badge.off {
    background: #e8f5e9;
    color: #388e3c;
}

.cookie-card.essential .card-badge {
    background: #fff3e0;
    color: #f57c00;
}

.cookie-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 12px 0;
    color: #121212;
}

.cookie-card p {
    font-size: 0.95rem;
    color: #555;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

.cookie-card ul {
    margin: 0;
    padding-left: 20px;
}

.cookie-card li {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 6px;
}

/* Third Party Table */
.third-party-table {
    margin-top: 24px;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    overflow: hidden;
}

.table-row {
    display: grid;
    grid-template-columns: 1.5fr 2fr 1.5fr;
    gap: 16px;
    padding: 16px 20px;
    font-size: 0.95rem;
}

.table-row.header {
    background: #121212;
    color: #fff;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table-row:not(.header) {
    border-bottom: 1px solid #e2e2e2;
}

.table-row:last-child {
    border-bottom: none;
}

/* Control Options */
.control-options {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 24px;
}

.control-option {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 24px;
}

.control-option h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: #121212;
}

.control-option p {
    font-size: 0.95rem;
    color: #555;
    margin: 0 0 16px 0;
}

.browser-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.browser-links a {
    display: inline-block;
    padding: 8px 16px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    color: #326891;
    text-decoration: none;
    transition: all 0.2s;
}

.browser-links a:hover {
    border-color: #121212;
    background: #121212;
    color: #fff;
}

/* Promise Section */
.highlight-section {
    background: #f8f8f8;
    border-radius: 12px;
    padding: 40px;
    margin-left: -20px;
    margin-right: -20px;
}

.promise-box {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 24px;
}

.promise-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 20px;
}

.promise-icon {
    font-size: 1.5rem;
    color: #4caf50;
}

.promise-item h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #121212;
}

.promise-item p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

/* Contact CTA */
.contact-cta {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-top: 24px;
}

.cta-button {
    display: inline-flex;
    align-items: center;
    padding: 14px 28px;
    background: #121212;
    color: #fff;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s;
}

.cta-button:hover {
    background: #333;
    transform: translateY(-2px);
}

.cta-link {
    color: #326891;
    text-decoration: underline;
    font-size: 0.95rem;
}

.cta-link:hover {
    color: #121212;
}

.last-updated {
    font-style: italic;
    color: #666;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e2e2;
}

/* Sidebar */
.cookies-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.sidebar-box {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.sidebar-box h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 16px 0;
    color: #121212;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.quick-nav nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quick-nav a {
    font-size: 0.95rem;
    color: #326891;
    text-decoration: none;
    padding: 8px 0;
    border-bottom: 1px solid #e2e2e2;
    transition: all 0.2s;
}

.quick-nav a:hover {
    color: #121212;
    padding-left: 8px;
}

.quick-nav a:last-child {
    border-bottom: none;
}

/* Cookie Settings Toggle */
.toggle-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.95rem;
    cursor: pointer;
}

.toggle {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 4px 8px;
    border-radius: 4px;
}

.toggle.always-on {
    background: #fff3e0;
    color: #e65100;
}

.toggle.always-off {
    background: #e8f5e9;
    color: #2e7d32;
}

.toggle-item input[type="checkbox"] {
    width: 44px;
    height: 24px;
    cursor: pointer;
}

.save-btn {
    width: 100%;
    margin-top: 20px;
    padding: 12px;
    background: #121212;
    color: #fff;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.save-btn:hover {
    background: #333;
}

/* Related Links */
.related-link {
    display: flex;
    flex-direction: column;
    padding: 16px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.related-link:hover {
    border-color: #121212;
}

.related-link strong {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.95rem;
    color: #121212;
    margin-bottom: 4px;
}

.related-link span {
    font-size: 0.85rem;
    color: #666;
}

/* Dark Mode */
body.dark-mode .cookies-hero {
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    border-color: #444;
}

body.dark-mode .cookies-title {
    color: #f0f0f0;
}

body.dark-mode .cookies-subtitle {
    color: #aaa;
}

body.dark-mode .honest-intro {
    background: #1a1a1a;
    border-left-color: #f0f0f0;
}

body.dark-mode .intro-text {
    color: #ccc;
}

body.dark-mode .cookie-section {
    border-bottom-color: #333;
}

body.dark-mode .section-number {
    color: #999;
    border-color: #666;
}

body.dark-mode .cookie-section h2 {
    color: #f0f0f0;
}

body.dark-mode .section-body p {
    color: #ccc;
}

body.dark-mode .plain-list li {
    color: #ccc;
}

body.dark-mode .cookie-card {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .cookie-card h3 {
    color: #f0f0f0;
}

body.dark-mode .cookie-card p {
    color: #aaa;
}

body.dark-mode .cookie-card li {
    color: #888;
}

body.dark-mode .third-party-table {
    border-color: #333;
}

body.dark-mode .table-row.header {
    background: #f0f0f0;
    color: #121212;
}

body.dark-mode .table-row:not(.header) {
    border-bottom-color: #333;
}

body.dark-mode .control-option {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .control-option h3 {
    color: #f0f0f0;
}

body.dark-mode .control-option p {
    color: #aaa;
}

body.dark-mode .browser-links a {
    background: #2a2a2a;
    border-color: #444;
    color: #5b9bd5;
}

body.dark-mode .browser-links a:hover {
    background: #f0f0f0;
    color: #121212;
    border-color: #f0f0f0;
}

body.dark-mode .highlight-section {
    background: #1a1a1a;
}

body.dark-mode .promise-item {
    background: #2a2a2a;
    border-color: #444;
}

body.dark-mode .promise-item h4 {
    color: #f0f0f0;
}

body.dark-mode .promise-item p {
    color: #aaa;
}

body.dark-mode .cta-button {
    background: #f0f0f0;
    color: #121212;
}

body.dark-mode .cta-button:hover {
    background: #fff;
}

body.dark-mode .cta-link {
    color: #5b9bd5;
}

body.dark-mode .cta-link:hover {
    color: #f0f0f0;
}

body.dark-mode .last-updated {
    color: #999;
    border-top-color: #333;
}

body.dark-mode .sidebar-box {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .sidebar-box h3 {
    color: #f0f0f0;
}

body.dark-mode .quick-nav a {
    color: #5b9bd5;
    border-bottom-color: #333;
}

body.dark-mode .quick-nav a:hover {
    color: #f0f0f0;
}

body.dark-mode .toggle.always-on {
    background: #4a3b20;
    color: #ffb74d;
}

body.dark-mode .toggle.always-off {
    background: #1b3a1c;
    color: #81c784;
}

body.dark-mode .save-btn {
    background: #f0f0f0;
    color: #121212;
}

body.dark-mode .save-btn:hover {
    background: #fff;
}

body.dark-mode .related-link {
    background: #2a2a2a;
    border-color: #444;
}

body.dark-mode .related-link:hover {
    border-color: #f0f0f0;
}

body.dark-mode .related-link strong {
    color: #f0f0f0;
}

body.dark-mode .related-link span {
    color: #888;
}

/* Responsive */
@media (max-width: 900px) {
    .cookies-grid {
        grid-template-columns: 1fr;
    }
    
    .cookies-sidebar {
        position: static;
        order: -1;
    }
    
    .cookie-cards {
        grid-template-columns: 1fr;
    }
    
    .promise-box {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .cookies-title {
        font-size: 2.2rem;
    }
    
    .hero-emoji {
        font-size: 3.5rem;
    }
    
    .highlight-section {
        margin-left: 0;
        margin-right: 0;
        padding: 24px;
    }
    
    .table-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .table-row.header {
        display: none;
    }
    
    .contact-cta {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
