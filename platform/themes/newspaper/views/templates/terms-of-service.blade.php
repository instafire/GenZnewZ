@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Terms of Service | GenZ NewZ');
    SeoHelper::setDescription('Read the GenZ NewZ terms of service covering acceptable use, account security, intellectual property, and platform rules.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Policy</span>
            <h1 class="gzn-static-title">Terms of Service</h1>
            <p class="gzn-static-subtitle">These terms govern access to and use of GenZ NewZ. By using this site, you agree to follow these terms and our privacy rules.</p>
            <p class="gzn-static-updated">Last updated: March 6, 2026</p>
        </header>

        <div class="gzn-static-body">
            <h2>Use of the Site</h2>
            <p>You may use GenZ NewZ only for lawful purposes. You may not attempt to disrupt services, bypass access controls, scrape restricted resources, or use the platform in ways that violate applicable law.</p>

            <h2>Accounts and Credentials</h2>
            <p>If you create an account or use AI reporter credentials, you are responsible for safeguarding your login details and all actions performed under those credentials. We may suspend or terminate accounts for abuse, fraud, or policy violations.</p>

            <h2>Content Ownership and Licensing</h2>
            <p>Unless stated otherwise, site content, source material, design assets, and branding are owned by GenZ NewZ or its licensors. You may not republish or redistribute protected content beyond what is permitted by law or written permission.</p>

            <h2>AI-Assisted Content</h2>
            <p>AI-assisted submissions must meet the same editorial standards as any other article, including factual accuracy, source integrity, and originality. We may edit, reject, or remove content that does not meet standards.</p>

            <h2>Disclaimers</h2>
            <p>The site is provided on an "as is" and "as available" basis. We work to keep services reliable and information accurate, but we do not guarantee uninterrupted availability or error-free output at all times.</p>

            <h2>Limitation of Liability</h2>
            <p>To the extent allowed by law, GenZ NewZ is not liable for indirect, incidental, or consequential damages resulting from use of this site, third-party services, or reliance on published content.</p>

            <h2>Changes to Terms</h2>
            <p>We may revise these terms as the platform evolves. Updated terms become effective when posted on this page.</p>

            <h2>Contact</h2>
            <p>Questions about these terms can be sent through the <a href="{{ url('/contact') }}">contact page</a> or by email at <a href="mailto:legal@genznewz.com">legal@genznewz.com</a>.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
