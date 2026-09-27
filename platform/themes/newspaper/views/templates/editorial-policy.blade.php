@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Editorial Policy | GenZ NewZ');
    SeoHelper::setDescription('Read the GenZ NewZ editorial policy, including fact-checking, corrections, AI disclosure, sourcing standards, and editorial independence.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Standards</span>
            <h1 class="gzn-static-title">Editorial Policy</h1>
            <p class="gzn-static-subtitle">How GenZ NewZ reports, reviews, corrects, and labels stories across human-led and AI-assisted publishing workflows.</p>
        </header>

        <div class="gzn-static-body">
            <p><strong>GenZ NewZ is committed to accurate, clearly sourced, and accountable reporting.</strong> This page explains how stories are produced, reviewed, corrected, and disclosed so readers understand the standards behind our newsroom.</p>

            <h2>Fact-Checking Process</h2>
            <p>We prioritize primary sources, direct statements, official documents, and credible reporting from established outlets. Editors review headlines, claims, dates, names, and links before publication. When facts are still developing, we label uncertainty clearly instead of overstating what is known.</p>

            <h2>Corrections Policy</h2>
            <p>If we publish incorrect information, we correct it as quickly as possible and update the article. Material corrections should clarify what changed and why. Readers can flag potential issues through the <a href="{{ url('/contact') }}">contact page</a> or by emailing <a href="mailto:hello@genznewz.com">hello@genznewz.com</a>.</p>

            <h2>Sourcing Standards</h2>
            <p>Every reported story should include clear attribution and at least one direct source when available. We avoid unsupported claims, vague sourcing, and headline language that is stronger than the evidence in the article body.</p>

            <h2>AI Use Disclosure</h2>
            <p>GenZ NewZ uses AI-assisted systems for drafting, structuring, summarization, and workflow automation. AI-assisted stories are labeled clearly, reviewed by a named editor before publication, and updated when new information changes the story. AI systems do not replace editorial accountability.</p>

            <h2>Editorial Independence</h2>
            <p>Editorial decisions are made independently from advertisers, sponsors, or outside partners. Sponsored or promotional material should be labeled separately from newsroom reporting.</p>

            <h2>Original Reporting and Aggregation</h2>
            <p>We aim to go beyond thin aggregation by adding context, comparisons, source links, and direct explanation of why a story matters. When a story is primarily based on external reporting, we credit the original source clearly.</p>

            <h2>Updates and Timestamps</h2>
            <p>Stories may be updated as events develop. We keep publication and modification dates visible so readers and search engines can understand when an article first appeared and when it was revised.</p>

            <h2>Contact the Newsroom</h2>
            <p>Questions about standards, corrections, or AI disclosures can be sent to <a href="mailto:hello@genznewz.com">hello@genznewz.com</a> or through the <a href="{{ url('/contact') }}">contact page</a>. You can also learn more about our contributors on the <a href="{{ url('/our-team') }}">team page</a>.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
