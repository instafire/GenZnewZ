@php
    use Botble\SeoHelper\Facades\SeoHelper;
    $pageName = isset($page) ? $page->name : 'Terms of Use';
    $pageDescription = isset($page) && $page->description ? $page->description : 'Read the Terms of Use for GenZ NewZ. Understand your rights and responsibilities when using our platform.';
    SeoHelper::setTitle($pageName . ' | GenZ NewZ');
    SeoHelper::setDescription($pageDescription);
@endphp
<div class="nyt-legal-container">
    <div class="nyt-legal-header">
        <span class="nyt-legal-label">Legal</span>
        <h1 class="nyt-legal-title">Terms of Use</h1>
        <p class="nyt-legal-updated">Last Updated: February 12, 2026</p>
    </div>

    <div class="nyt-legal-content">
        <div class="legal-grid">
            <main class="legal-main">
                @if(isset($page) && !empty(strip_tags($page->content)))
                <div class="page-custom-content nyt-page-body" style="margin-bottom: 40px;">
                    {!! $page->content !!}
                </div>
                @endif

                <div class="legal-toc">
                    <h2>Table of Contents</h2>
                    <nav class="toc-nav">
                        <a href="#acceptance" class="toc-link">1. Acceptance of Terms</a>
                        <a href="#description" class="toc-link">2. Description of Service</a>
                        <a href="#accounts" class="toc-link">3. User Accounts</a>
                        <a href="#content" class="toc-link">4. Content and Conduct</a>
                        <a href="#intellectual" class="toc-link">5. Intellectual Property</a>
                        <a href="#privacy" class="toc-link">6. Privacy Policy</a>
                        <a href="#termination" class="toc-link">7. Termination</a>
                        <a href="#disclaimer" class="toc-link">8. Disclaimer of Warranties</a>
                        <a href="#limitation" class="toc-link">9. Limitation of Liability</a>
                        <a href="#changes" class="toc-link">10. Changes to Terms</a>
                        <a href="#contact" class="toc-link">11. Contact Information</a>
                    </nav>
                </div>

                <div class="legal-body">
                    <section id="acceptance" class="legal-section">
                        <h2>1. Acceptance of Terms</h2>
                        <p>Welcome to GenZ NewZ. By accessing or using our website, mobile applications, and services (collectively, the "Services"), you agree to be bound by these Terms of Use ("Terms"). If you do not agree to these Terms, please do not use our Services.</p>
                        <p>We reserve the right to modify these Terms at any time. We will notify you of any material changes by posting the updated Terms on this page with a revised "Last Updated" date. Your continued use of the Services after such changes constitutes your acceptance of the new Terms.</p>
                    </section>

                    <section id="description" class="legal-section">
                        <h2>2. Description of Service</h2>
                        <p>GenZ NewZ is a digital news platform that provides news, analysis, opinion pieces, and multimedia content targeted at Generation Z and young millennials. Our Services include:</p>
                        <ul>
                            <li>Access to news articles and editorial content</li>
                            <li>User comments and community features</li>
                            <li>Newsletter subscriptions</li>
                            <li>Social media integration</li>
                            <li>AI-generated content and tools</li>
                            <li>Mobile applications and APIs</li>
                        </ul>
                    </section>

                    <section id="accounts" class="legal-section">
                        <h2>3. User Accounts</h2>
                        <h3>3.1 Registration</h3>
                        <p>To access certain features of our Services, you may need to create an account. You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate, current, and complete.</p>
                        
                        <h3>3.2 Account Security</h3>
                        <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account or any other breach of security.</p>
                        
                        <h3>3.3 Account Termination</h3>
                        <p>We reserve the right to suspend or terminate your account at any time, with or without notice, for any reason, including violation of these Terms.</p>
                    </section>

                    <section id="content" class="legal-section">
                        <h2>4. Content and Conduct</h2>
                        <h3>4.1 User-Generated Content</h3>
                        <p>By submitting content to our Services (including comments, articles, and other materials), you grant GenZ NewZ a non-exclusive, royalty-free, perpetual, irrevocable, and fully sublicensable right to use, reproduce, modify, adapt, publish, translate, create derivative works from, distribute, and display such content.</p>
                        
                        <h3>4.2 Prohibited Conduct</h3>
                        <p>You agree not to use our Services to:</p>
                        <ul>
                            <li>Post or transmit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, or invasive of privacy</li>
                            <li>Impersonate any person or entity or falsely state your affiliation</li>
                            <li>Upload or transmit viruses, malware, or other harmful code</li>
                            <li>Interfere with or disrupt the Services or servers</li>
                            <li>Collect or store personal data about other users without their consent</li>
                            <li>Use the Services for any commercial purpose without our prior written consent</li>
                        </ul>
                    </section>

                    <section id="intellectual" class="legal-section">
                        <h2>5. Intellectual Property</h2>
                        <h3>5.1 Our Content</h3>
                        <p>All content on GenZ NewZ, including text, graphics, logos, images, audio clips, and software, is the property of GenZ NewZ or its content suppliers and is protected by international copyright laws. You may not reproduce, distribute, modify, or create derivative works from our content without express written permission.</p>
                        
                        <h3>5.2 Trademarks</h3>
                        <p>The GenZ NewZ name, logo, and all related names, logos, product and service names, designs, and slogans are trademarks of GenZ NewZ or its affiliates. You may not use such marks without our prior written permission.</p>
                    </section>

                    <section id="privacy" class="legal-section">
                        <h2>6. Privacy Policy</h2>
                        <p>Your privacy is important to us. Please review our <a href="/privacy-policy" class="legal-link">Privacy Policy</a>, which explains how we collect, use, and protect your personal information.</p>
                    </section>

                    <section id="termination" class="legal-section">
                        <h2>7. Termination</h2>
                        <p>We may terminate or suspend your access to the Services immediately, without prior notice or liability, for any reason, including breach of these Terms. Upon termination, your right to use the Services will immediately cease.</p>
                    </section>

                    <section id="disclaimer" class="legal-section">
                        <h2>8. Disclaimer of Warranties</h2>
                        <p>THE SERVICES ARE PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED. TO THE FULLEST EXTENT PERMITTED BY LAW, WE DISCLAIM ALL WARRANTIES, INCLUDING IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>
                        <p>We do not warrant that the Services will be uninterrupted, timely, secure, or error-free, or that any defects will be corrected.</p>
                    </section>

                    <section id="limitation" class="legal-section">
                        <h2>9. Limitation of Liability</h2>
                        <p>TO THE FULLEST EXTENT PERMITTED BY LAW, GENZ NEWZ SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS, DATA, OR USE, ARISING OUT OF OR RELATING TO YOUR USE OF THE SERVICES.</p>
                    </section>

                    <section id="changes" class="legal-section">
                        <h2>10. Changes to Terms</h2>
                        <p>We may modify these Terms at any time. Changes will be effective immediately upon posting. Your continued use of the Services after changes constitutes acceptance of the modified Terms.</p>
                    </section>

                    <section id="contact" class="legal-section">
                        <h2>11. Contact Information</h2>
                        <p>If you have any questions about these Terms, please contact us:</p>
                        <div class="contact-info-block">
                            <p><strong>Email:</strong> <a href="mailto:legal@genznewz.com" class="legal-link">legal@genznewz.com</a></p>
                            <p><strong>Address:</strong> GenZ NewZ Legal Department<br>
                            123 Media Street, Suite 456<br>
                            New York, NY 10001</p>
                        </div>
                    </section>
                </div>
            </main>

            <aside class="legal-sidebar">
                <div class="legal-sidebar-box">
                    <h3>Related Documents</h3>
                    <nav class="legal-nav">
                        <a href="/privacy-policy" class="legal-nav-link">
                            <span class="nav-title">Privacy Policy</span>
                            <span class="nav-desc">How we handle your data</span>
                        </a>
                        <a href="/cookie-policy" class="legal-nav-link">
                            <span class="nav-title">Cookie Policy</span>
                            <span class="nav-desc">Our stance on cookies</span>
                        </a>
                    </nav>
                </div>

                <div class="legal-sidebar-box">
                    <h3>Questions?</h3>
                    <p>If you have questions about these terms, please <a href="/contact" class="legal-link">contact us</a>.</p>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
/* NYT Legal Container */
.nyt-legal-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    font-family: Georgia, 'Times New Roman', serif;
}

/* Header */
.nyt-legal-header {
    text-align: center;
    padding-bottom: 40px;
    border-bottom: 2px solid #121212;
    margin-bottom: 40px;
}

.nyt-legal-label {
    display: block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #666;
    margin-bottom: 20px;
}

.nyt-legal-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3.5rem;
    font-weight: 700;
    color: #121212;
    margin: 0 0 16px 0;
    line-height: 1.1;
}

.nyt-legal-updated {
    font-size: 0.95rem;
    color: #666;
    font-style: italic;
    margin: 0;
}

/* Content Grid */
.legal-grid {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 60px;
}

/* Table of Contents */
.legal-toc {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 28px;
    margin-bottom: 48px;
}

.legal-toc h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #121212;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.toc-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.toc-link {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    color: #326891;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    transition: all 0.2s;
}

.toc-link:hover {
    background: #fff;
    color: #121212;
}

/* Legal Sections */
.legal-section {
    margin-bottom: 48px;
    padding-bottom: 48px;
    border-bottom: 1px solid #e2e2e2;
}

.legal-section:last-child {
    border-bottom: none;
}

.legal-section h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 600;
    color: #121212;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 3px solid #121212;
    display: inline-block;
}

.legal-section h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 32px 0 16px 0;
}

.legal-section p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #333;
    margin: 0 0 16px 0;
}

.legal-section ul {
    margin: 16px 0;
    padding-left: 28px;
}

.legal-section li {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #333;
    margin-bottom: 8px;
}

.legal-link {
    color: #326891;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.legal-link:hover {
    color: #121212;
}

.contact-info-block {
    background: #f8f8f8;
    border-left: 4px solid #121212;
    padding: 20px 24px;
    margin-top: 20px;
}

.contact-info-block p {
    margin-bottom: 8px;
}

.contact-info-block p:last-child {
    margin-bottom: 0;
}

/* Sidebar */
.legal-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.legal-sidebar-box {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.legal-sidebar-box h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 16px 0;
    color: #121212;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.legal-sidebar-box p {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #555;
    margin: 0;
}

.legal-nav {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.legal-nav-link {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
}

.legal-nav-link:hover {
    border-color: #121212;
    background: #fff;
}

.nav-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #121212;
    margin-bottom: 4px;
}

.nav-desc {
    font-size: 0.8rem;
    color: #666;
}

/* Dark Mode */
body.dark-mode .nyt-legal-header {
    border-bottom-color: #f0f0f0;
}

body.dark-mode .nyt-legal-label {
    color: #999;
}

body.dark-mode .nyt-legal-title {
    color: #f0f0f0;
}

body.dark-mode .nyt-legal-updated {
    color: #aaa;
}

body.dark-mode .legal-toc {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .legal-toc h2 {
    color: #f0f0f0;
}

body.dark-mode .toc-link:hover {
    background: #2a2a2a;
    color: #f0f0f0;
}

body.dark-mode .legal-section {
    border-bottom-color: #333;
}

body.dark-mode .legal-section h2 {
    color: #f0f0f0;
    border-bottom-color: #f0f0f0;
}

body.dark-mode .legal-section h3 {
    color: #ccc;
}

body.dark-mode .legal-section p,
body.dark-mode .legal-section li {
    color: #ccc;
}

body.dark-mode .legal-link {
    color: #5b9bd5;
}

body.dark-mode .legal-link:hover {
    color: #f0f0f0;
}

body.dark-mode .contact-info-block {
    background: #1a1a1a;
    border-left-color: #f0f0f0;
}

body.dark-mode .legal-sidebar-box {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .legal-sidebar-box h3 {
    color: #f0f0f0;
}

body.dark-mode .legal-sidebar-box p {
    color: #aaa;
}

body.dark-mode .legal-nav-link {
    background: #2a2a2a;
    border-color: #444;
}

body.dark-mode .legal-nav-link:hover {
    border-color: #f0f0f0;
    background: #2a2a2a;
}

body.dark-mode .nav-title {
    color: #f0f0f0;
}

body.dark-mode .nav-desc {
    color: #888;
}

/* Responsive */
@media (max-width: 900px) {
    .legal-grid {
        grid-template-columns: 1fr;
    }
    
    .legal-sidebar {
        position: static;
        order: -1;
    }
    
    .legal-toc {
        margin-bottom: 32px;
    }
    
    .toc-nav {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .nyt-legal-title {
        font-size: 2.2rem;
    }
    
    .toc-nav {
        grid-template-columns: 1fr;
    }
    
    .legal-section h2 {
        font-size: 1.3rem;
    }
    
    .legal-section h3 {
        font-size: 1.1rem;
    }
}
</style>
