@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Cookie Policy | GenZ NewZ');
    SeoHelper::setDescription('Read the GenZ NewZ cookie policy to learn how cookies, analytics tools, and consent preferences are used on this website.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Policy</span>
            <h1 class="gzn-static-title">Cookie Policy</h1>
            <p class="gzn-static-subtitle">This policy explains how GenZ NewZ uses cookies, local storage, and similar technologies to operate the website and improve performance.</p>
            <p class="gzn-static-updated">Last updated: March 9, 2026</p>
        </header>

        <div class="gzn-static-body">
            <h2>What Cookies Do</h2>
            <p>Cookies help us remember preferences, maintain sessions, secure sign-in workflows, measure engagement, and support consent management.</p>

            <h2>Types of Cookies We Use</h2>
            <ul>
                <li><strong>Essential cookies:</strong> Required for account access, security checks, and core site functions.</li>
                <li><strong>Analytics cookies:</strong> Used to understand traffic patterns, page performance, and reader engagement.</li>
                <li><strong>Advertising cookies:</strong> Used by ad and campaign systems where applicable.</li>
                <li><strong>Preference cookies:</strong> Store user settings such as consent and interface preferences.</li>
            </ul>

            <h2>Microsoft Clarity</h2>
            <p>GenZ NewZ uses Microsoft Clarity to capture how visitors use and interact with the website through behavioral metrics, heatmaps, and session replay. Clarity may use first-party and third-party cookies, local storage, and similar technologies to measure engagement, identify usability issues, improve performance, and support fraud and security monitoring.</p>
            <p>For details about how Microsoft collects and uses this data, review the <a href="https://privacy.microsoft.com/en-us/privacystatement" target="_blank" rel="noopener noreferrer">Microsoft Privacy Statement</a>.</p>

            <h2>Managing Cookies</h2>
            <p>You can clear or block cookies in your browser settings. Blocking all cookies may impact login sessions, saved preferences, and other site features.</p>

            <h2>Third-Party Technologies</h2>
            <p>Some technologies are provided by third-party services such as analytics, media players, and ad delivery platforms. These services operate under their own privacy and cookie policies.</p>

            <h2>Contact</h2>
            <p>Questions about cookies or consent controls can be sent through the <a href="{{ url('/contact') }}">contact page</a> or by email at <a href="mailto:privacy@genznewz.com">privacy@genznewz.com</a>.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
