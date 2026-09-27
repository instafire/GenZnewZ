@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('About GenZ NewZ | GenZ NewZ');
    SeoHelper::setDescription('Learn how GenZ NewZ covers breaking news, AI, culture, politics, and trending stories for the next generation with editorial oversight.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">About</span>
            <h1 class="gzn-static-title">About GenZ NewZ</h1>
            <p class="gzn-static-subtitle">GenZ NewZ is a digital newsroom built for readers who want fast, clear reporting on the stories shaping politics, technology, culture, money, science, and everyday life.</p>
        </header>

        <div class="gzn-static-body">
            <p><strong>GenZ NewZ publishes breaking news and explainers for the next generation.</strong> We focus on stories that matter to younger readers without burying facts under noise, jargon, or stale newsroom habits.</p>

            <h2>What We Cover</h2>
            <p>Our coverage spans AI and technology, business and crypto, politics and world affairs, culture and entertainment, sports, science, and climate. We aim to explain why a story matters, not just repeat what happened.</p>

            <h2>How We Work</h2>
            <p>GenZ NewZ combines automation, newsroom systems, and editorial review to publish quickly while protecting quality standards. AI-assisted stories are labeled clearly and reviewed before publication.</p>

            <h2>Editorial Standards</h2>
            <p>We prioritize factual accuracy, clear sourcing, strong headlines, and useful context. When information changes, we update stories. When something is wrong, we correct it. The goal is trustworthy reporting that stays readable.</p>

            <h2>Why Gen Z</h2>
            <p>Younger readers deserve news that respects their attention and intelligence. That means fewer empty talking points, more practical context, and coverage that treats technology, work, identity, entertainment, and global events as connected parts of modern life.</p>

            <h2>Contact the Team</h2>
            <p>If you have a story tip, correction, partnership idea, or press inquiry, visit the <a href="{{ url('/contact') }}">contact page</a>. You can also learn more about contributors on the <a href="{{ url('/our-team') }}">team page</a> and review our standards on the <a href="{{ url('/editorial-policy') }}">editorial policy page</a>.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
