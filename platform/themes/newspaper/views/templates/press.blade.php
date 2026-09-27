@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Press Kit | GenZ NewZ');
    SeoHelper::setDescription('GenZ NewZ press kit: newsroom boilerplate, fast facts, logo and brand assets, interview requests, and how to cite our reporting.');
@endphp

<section class="gzn-static-page">
    <article class="gzn-static-shell">
        <header class="gzn-static-header">
            <span class="gzn-static-kicker">Press</span>
            <h1 class="gzn-static-title">Press Kit</h1>
            <p class="gzn-static-subtitle">Everything journalists, researchers, and partners need to cover or cite GenZ NewZ.</p>
        </header>

        <div class="gzn-static-body">
            <h2>Newsroom Boilerplate</h2>
            <p><strong>GenZ NewZ</strong> (genznewz.com) is an AI-native digital newsroom publishing fast, readable reporting for the next generation of news consumers. Stories are produced with AI-assisted reporting systems under editorial oversight. Coverage spans technology, AI, culture, politics, business, science, and world affairs, with a focus on clear context and why-it-matters framing.</p>

            <h2>Fast Facts</h2>
            <ul>
                <li><strong>Founded:</strong> 2025</li>
                <li><strong>Newsroom model:</strong> AI-assisted reporting with editorial oversight</li>
                <li><strong>Output:</strong> Dozens of original stories published daily across 40+ topic desks</li>
                <li><strong>Editor-in-Chief:</strong> Aman</li>
                <li><strong>Headquarters:</strong> Toronto, Canada</li>
                <li><strong>Website:</strong> <a href="{{ url('/') }}">genznewz.com</a></li>
            </ul>

            <h2>How to Cite Us</h2>
            <p>Please credit as <strong>GenZ NewZ</strong> with a link to the original story on genznewz.com. For data or figures first reported by our newsroom, attribution as "according to GenZ NewZ reporting" is appreciated.</p>

            <h2>Interviews and Commentary</h2>
            <p>Our editors are available to discuss AI in newsrooms, Gen Z media consumption, and the future of automated reporting. Send interview requests to <a href="mailto:partnerships@genznewz.com">partnerships@genznewz.com</a> with "Press Inquiry" in the subject line.</p>

            <h2>Brand Assets</h2>
            <p>For logo files and brand guidelines, contact <a href="mailto:partnerships@genznewz.com">partnerships@genznewz.com</a>.</p>

            <h2>Corrections</h2>
            <p>We correct errors promptly and transparently. See our <a href="{{ url('/editorial-policy') }}">editorial policy</a> for the corrections process, or write to <a href="mailto:hello@genznewz.com">hello@genznewz.com</a>.</p>
        </div>
    </article>
</section>

@include('theme::partials.static-page-styles')
