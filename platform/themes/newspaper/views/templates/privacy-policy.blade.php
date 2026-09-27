@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Privacy Policy | GenZ NewZ');
    SeoHelper::setDescription('Read the GenZ NewZ privacy policy to understand what information we collect, how we use it, and your available privacy rights.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Policy</span>
            <h1 class="gzn-static-title">Privacy Policy</h1>
            <p class="gzn-static-subtitle">This privacy policy explains how GenZ NewZ collects, uses, stores, and protects personal information when you use our website and services.</p>
            <p class="gzn-static-updated">Last updated: March 9, 2026</p>
        </header>

        <div class="gzn-static-body">
            <p>This policy applies to <a href="https://genznewz.com">genznewz.com</a> and related editorial tools operated by GenZ NewZ.</p>

            <h2>Information We Collect</h2>
            <p>We collect information you provide directly, such as contact form submissions, email preferences, account details, and support requests. We also collect technical data like IP address, browser and device type, page views, and referral source to operate and improve the site.</p>

            <h2>How We Use Information</h2>
            <p>We use information to publish content, maintain accounts and sessions, respond to messages, analyze site performance, improve product reliability, prevent abuse, and comply with legal obligations.</p>

            <h2>Cookies and Analytics</h2>
            <p>We use cookies and similar technologies for session handling, consent controls, performance measurement, and analytics. You can manage cookies through your browser settings, though some features may not work correctly if disabled.</p>
            <p>We use Microsoft Clarity to understand how readers use and interact with GenZ NewZ through behavioral metrics, heatmaps, and session replay. Clarity uses cookies and similar technologies to help us measure engagement, improve site performance, refine page layouts, and support security and fraud monitoring. More information about how Microsoft collects and uses data is available in the <a href="https://privacy.microsoft.com/en-us/privacystatement" target="_blank" rel="noopener noreferrer">Microsoft Privacy Statement</a>.</p>

            <h2>Third-Party Services</h2>
            <p>Some site features rely on third-party providers such as analytics, media hosting, advertising, and email infrastructure. These services process data under their own privacy policies and security practices.</p>
            <p>When Microsoft Clarity is active, Microsoft may process usage and device data generated through those interactions under its own privacy terms in addition to this policy.</p>

            <h2>Your Rights</h2>
            <p>Depending on your location, you may have rights to request access, correction, deletion, or restriction of personal information. To submit a privacy request, use the <a href="{{ url('/contact') }}">contact page</a> or email <a href="mailto:privacy@genznewz.com">privacy@genznewz.com</a>.</p>

            <h2>Children's Privacy</h2>
            <p>GenZ NewZ is not intended for children under 13. We do not knowingly collect personal information from children under 13 without an appropriate legal basis.</p>

            <h2>Policy Updates</h2>
            <p>We may update this policy when our systems, legal obligations, or data practices change. Material updates will be reflected on this page with a revised date.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
