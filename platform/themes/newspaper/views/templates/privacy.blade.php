@php
    use Botble\SeoHelper\Facades\SeoHelper;
    $pageName = isset($page) ? $page->name : 'Privacy Policy';
    $pageDescription = isset($page) && $page->description ? $page->description : 'Learn how GenZ NewZ collects, uses, and protects your personal information. Your privacy matters to us.';
    SeoHelper::setTitle($pageName . ' | GenZ NewZ');
    SeoHelper::setDescription($pageDescription);
@endphp
<div class="nyt-legal-container">
    <div class="nyt-legal-header">
        <span class="nyt-legal-label">Your Privacy Matters</span>
        <h1 class="nyt-legal-title">Privacy Policy</h1>
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

                <div class="privacy-intro">
                    <p class="nyt-lead">At GenZ NewZ, we take your privacy seriously. This Privacy Policy explains how we collect, use, store, and protect your personal information when you use our website and services. By using GenZ NewZ, you consent to the practices described in this policy.</p>
                </div>

                <div class="legal-toc">
                    <h2>Table of Contents</h2>
                    <nav class="toc-nav">
                        <a href="#collect" class="toc-link">1. Information We Collect</a>
                        <a href="#use" class="toc-link">2. How We Use Your Information</a>
                        <a href="#cookies" class="toc-link">3. Cookies & Tracking</a>
                        <a href="#share" class="toc-link">4. Information Sharing</a>
                        <a href="#security" class="toc-link">5. Data Security</a>
                        <a href="#rights" class="toc-link">6. Your Rights</a>
                        <a href="#retention" class="toc-link">7. Data Retention</a>
                        <a href="#children" class="toc-link">8. Children's Privacy</a>
                        <a href="#changes" class="toc-link">9. Policy Changes</a>
                        <a href="#contact" class="toc-link">10. Contact Us</a>
                    </nav>
                </div>

                <div class="legal-body">
                    <section id="collect" class="legal-section">
                        <h2>1. Information We Collect</h2>
                        
                        <h3>1.1 Information You Provide</h3>
                        <p>We collect information you voluntarily provide when using our Services, including:</p>
                        <ul>
                            <li><strong>Account Information:</strong> Name, email address, username, password, and profile information when you create an account</li>
                            <li><strong>Communications:</strong> Information you provide when contacting us, subscribing to newsletters, or participating in surveys</li>
                            <li><strong>User Content:</strong> Comments, articles, and other content you post on our platform</li>
                            <li><strong>Payment Information:</strong> If you make purchases, we collect payment details (processed securely by third-party payment processors)</li>
                        </ul>

                        <h3>1.2 Information Automatically Collected</h3>
                        <p>When you access our Services, we automatically collect:</p>
                        <ul>
                            <li><strong>Device Information:</strong> IP address, browser type, operating system, device identifiers</li>
                            <li><strong>Usage Data:</strong> Pages visited, time spent, links clicked, articles read, search queries</li>
                            <li><strong>Location Data:</strong> General geographic location based on IP address</li>
                            <li><strong>Log Data:</strong> Access times, referral sources, error logs</li>
                        </ul>
                    </section>

                    <section id="use" class="legal-section">
                        <h2>2. How We Use Your Information</h2>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide, maintain, and improve our Services</li>
                            <li>Personalize your experience and deliver content relevant to your interests</li>
                            <li>Process transactions and send related confirmations</li>
                            <li>Send newsletters, updates, and marketing communications (with your consent)</li>
                            <li>Respond to your comments, questions, and requests</li>
                            <li>Monitor and analyze trends, usage, and activities</li>
                            <li>Detect, investigate, and prevent fraudulent transactions and illegal activities</li>
                            <li>Protect the rights, property, and safety of GenZ NewZ and our users</li>
                        </ul>
                    </section>

                    <section id="cookies" class="legal-section">
                        <h2>3. Cookies & Tracking Technologies</h2>
                        
                        <h3>3.1 What Are Cookies</h3>
                        <p>Cookies are small text files stored on your device that help us provide and improve our Services. We use cookies to remember your preferences, understand how you interact with our content, and deliver personalized experiences.</p>

                        <h3>3.2 Types of Cookies We Use</h3>
                        <div class="cookie-types">
                            <div class="cookie-type">
                                <h4>Essential Cookies</h4>
                                <p>Necessary for the website to function properly. These cannot be disabled.</p>
                            </div>
                            <div class="cookie-type">
                                <h4>Analytics Cookies</h4>
                                <p>Help us understand how visitors interact with our website.</p>
                            </div>
                            <div class="cookie-type">
                                <h4>Preference Cookies</h4>
                                <p>Remember your settings and preferences (like dark mode).</p>
                            </div>
                            <div class="cookie-type">
                                <h4>Marketing Cookies</h4>
                                <p>Used to deliver relevant advertisements and track their performance.</p>
                            </div>
                        </div>

                        <h3>3.3 Your Cookie Choices</h3>
                        <p>You can manage your cookie preferences through your browser settings. Note that disabling certain cookies may affect the functionality of our Services. Learn more in our <a href="/cookie-policy" class="legal-link">Cookie Policy</a>.</p>
                    </section>

                    <section id="share" class="legal-section">
                        <h2>4. Information Sharing & Disclosure</h2>
                        <p>We do not sell your personal information. We may share your information in the following circumstances:</p>
                        
                        <h3>4.1 Service Providers</h3>
                        <p>We engage third-party companies to perform services on our behalf, such as hosting, analytics, payment processing, and customer support. These providers have access to your information only to perform these tasks.</p>

                        <h3>4.2 Legal Requirements</h3>
                        <p>We may disclose your information if required by law, regulation, legal process, or governmental request.</p>

                        <h3>4.3 Business Transfers</h3>
                        <p>If GenZ NewZ is involved in a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.</p>

                        <h3>4.4 With Your Consent</h3>
                        <p>We may share your information with third parties when you have given us your consent to do so.</p>
                    </section>

                    <section id="security" class="legal-section">
                        <h2>5. Data Security</h2>
                        <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:</p>
                        <ul>
                            <li>Encryption of data in transit using SSL/TLS technology</li>
                            <li>Regular security assessments and monitoring</li>
                            <li>Access controls and authentication requirements</li>
                            <li>Secure data storage with industry-standard protections</li>
                        </ul>
                        <p>However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.</p>
                    </section>

                    <section id="rights" class="legal-section">
                        <h2>6. Your Privacy Rights</h2>
                        <p>Depending on your location, you may have the following rights regarding your personal information:</p>
                        
                        <div class="rights-grid">
                            <div class="right-item">
                                <h4>Access</h4>
                                <p>Request a copy of the personal information we hold about you.</p>
                            </div>
                            <div class="right-item">
                                <h4>Correction</h4>
                                <p>Request that we correct inaccurate or incomplete information.</p>
                            </div>
                            <div class="right-item">
                                <h4>Deletion</h4>
                                <p>Request that we delete your personal information in certain circumstances.</p>
                            </div>
                            <div class="right-item">
                                <h4>Restriction</h4>
                                <p>Request that we limit how we use your information.</p>
                            </div>
                            <div class="right-item">
                                <h4>Portability</h4>
                                <p>Request a transfer of your information to another service.</p>
                            </div>
                            <div class="right-item">
                                <h4>Objection</h4>
                                <p>Object to certain types of processing, such as direct marketing.</p>
                            </div>
                        </div>

                        <p>To exercise these rights, please contact us using the information in the Contact section below.</p>
                    </section>

                    <section id="retention" class="legal-section">
                        <h2>7. Data Retention</h2>
                        <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required or permitted by law. When determining retention periods, we consider:</p>
                        <ul>
                            <li>The amount, nature, and sensitivity of the information</li>
                            <li>The potential risk of harm from unauthorized use or disclosure</li>
                            <li>The purposes for which we process the information</li>
                            <li>Legal, regulatory, and contractual requirements</li>
                        </ul>
                    </section>

                    <section id="children" class="legal-section">
                        <h2>8. Children's Privacy</h2>
                        <p>Our Services are not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If we become aware that we have collected personal information from a child under 13, we will take steps to delete that information.</p>
                        <p>If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.</p>
                    </section>

                    <section id="changes" class="legal-section">
                        <h2>9. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the new policy on this page with a revised "Last Updated" date. We encourage you to review this policy periodically.</p>
                    </section>

                    <section id="contact" class="legal-section">
                        <h2>10. Contact Us</h2>
                        <p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                        <div class="contact-info-block">
                            <p><strong>Email:</strong> <a href="mailto:privacy@genznewz.com" class="legal-link">privacy@genznewz.com</a></p>
                            <p><strong>Address:</strong><br>
                            GenZ NewZ Privacy Office<br>
                            123 Media Street, Suite 456<br>
                            New York, NY 10001<br>
                            United States</p>
                        </div>
                        <p>We will respond to your inquiry within 30 days.</p>
                    </section>
                </div>
            </main>

            <aside class="legal-sidebar">
                <div class="legal-sidebar-box highlight-box">
                    <h3>🔒 Your Data, Your Control</h3>
                    <p>We believe in transparency. You can request a copy of your data or ask us to delete it at any time.</p>
                    <a href="mailto:privacy@genznewz.com" class="sidebar-cta">Request Your Data</a>
                </div>

                <div class="legal-sidebar-box">
                    <h3>Related Documents</h3>
                    <nav class="legal-nav">
                        <a href="/terms-of-service" class="legal-nav-link">
                            <span class="nav-title">Terms of Use</span>
                            <span class="nav-desc">Rules for using our platform</span>
                        </a>
                        <a href="/cookie-policy" class="legal-nav-link">
                            <span class="nav-title">Cookie Policy</span>
                            <span class="nav-desc">How we use cookies</span>
                        </a>
                    </nav>
                </div>

                <div class="legal-sidebar-box">
                    <h3>Quick Links</h3>
                    <ul class="quick-links">
                        <li><a href="#rights" class="legal-link">Your Rights</a></li>
                        <li><a href="#cookies" class="legal-link">Cookie Settings</a></li>
                        <li><a href="#security" class="legal-link">Security Practices</a></li>
                        <li><a href="/contact" class="legal-link">Contact Privacy Team</a></li>
                    </ul>
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

/* Intro */
.privacy-intro {
    margin-bottom: 40px;
}

.nyt-lead {
    font-size: 1.25rem;
    line-height: 1.7;
    color: #333;
    margin: 0;
    padding-bottom: 30px;
    border-bottom: 1px solid #e2e2e2;
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

.legal-section h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.05rem;
    font-weight: 600;
    color: #444;
    margin: 24px 0 12px 0;
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

/* Cookie Types */
.cookie-types {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 24px 0;
}

.cookie-type {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 20px;
}

.cookie-type h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #121212;
}

.cookie-type p {
    font-size: 0.95rem;
    color: #555;
    margin: 0;
    line-height: 1.5;
}

/* Rights Grid */
.rights-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin: 24px 0;
}

.right-item {
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.right-item h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #121212;
}

.right-item p {
    font-size: 0.9rem;
    color: #555;
    margin: 0;
    line-height: 1.5;
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
    margin: 0 0 16px 0;
}

.highlight-box {
    background: #121212;
    border-color: #121212;
    color: #fff;
}

.highlight-box h3 {
    color: #fff;
}

.highlight-box p {
    color: #ccc;
}

.sidebar-cta {
    display: inline-block;
    padding: 10px 20px;
    background: #fff;
    color: #121212;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
}

.sidebar-cta:hover {
    background: #f0f0f0;
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

.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-links li {
    margin-bottom: 10px;
}

.quick-links a {
    font-size: 0.95rem;
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

body.dark-mode .nyt-lead {
    color: #ccc;
    border-bottom-color: #444;
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

body.dark-mode .legal-section h3,
body.dark-mode .legal-section h4 {
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

body.dark-mode .cookie-type,
body.dark-mode .right-item {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .cookie-type h4,
body.dark-mode .right-item h4 {
    color: #f0f0f0;
}

body.dark-mode .cookie-type p,
body.dark-mode .right-item p {
    color: #aaa;
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

body.dark-mode .highlight-box {
    background: #f0f0f0;
    border-color: #f0f0f0;
}

body.dark-mode .highlight-box h3 {
    color: #121212;
}

body.dark-mode .highlight-box p {
    color: #333;
}

body.dark-mode .sidebar-cta {
    background: #121212;
    color: #f0f0f0;
}

body.dark-mode .sidebar-cta:hover {
    background: #2a2a2a;
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
    
    .cookie-types {
        grid-template-columns: 1fr;
    }
    
    .rights-grid {
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
    
    .rights-grid {
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
