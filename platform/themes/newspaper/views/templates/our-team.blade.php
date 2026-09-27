@php
    use Botble\SeoHelper\Facades\SeoHelper;

    SeoHelper::setTitle('Our Team | GenZ NewZ');
    SeoHelper::setDescription('Meet the GenZ NewZ team, including editors, contributors, and AI-assisted newsroom systems behind our reporting and publishing workflow.');
@endphp

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <header class="mb-5 text-center">
                <span class="text-uppercase text-muted small d-block mb-2">Team</span>
                <h1 class="display-5 fw-bold mb-3">Meet the GenZ NewZ Team</h1>
                <p class="lead text-muted">Editors, builders, and AI-assisted newsroom systems working together to publish fast, readable reporting for the next generation.</p>
            </header>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="border rounded-4 h-100 p-4 shadow-sm">
                        <h2 class="h4">Aman</h2>
                        <p class="text-muted mb-3">Editor-in-Chief and Technology Correspondent</p>
                        <p>Aman founded GenZ NewZ and leads its editorial strategy as Editor-in-Chief. He built the newsroom's AI-assisted reporting pipeline and sets its editorial standards. He covers technology, AI, and digital culture with a focus on clear reporting, practical context, and explaining how new systems affect everyday life.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-4 h-100 p-4 shadow-sm">
                        <h2 class="h4">Mya</h2>
                        <p class="text-muted mb-3">Lifestyle and Culture Editor</p>
                        <p>Mya covers culture, wellness, identity, and lifestyle with a voice that is direct, readable, and grounded in how younger audiences actually consume and discuss trends.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-4 h-100 p-4 shadow-sm">
                        <h2 class="h4">Abe</h2>
                        <p class="text-muted mb-3">Politics and World Affairs Correspondent</p>
                        <p>Abe focuses on politics, global events, and policy stories that affect readers' lives, futures, and civic decisions. Coverage aims for clarity over theater.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-4 h-100 p-4 shadow-sm">
                        <h2 class="h4">GenZai</h2>
                        <p class="text-muted mb-3">AI Reporter and News Automation System</p>
                        <p>GenZai is GenZ NewZ's AI-assisted reporting system for rapid coverage, structured explainers, and newsroom automation. AI-authored stories are labeled clearly and reviewed before publication.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-4 h-100 p-4 shadow-sm">
                        <h2 class="h4">Mya AI Admin</h2>
                        <p class="text-muted mb-3">Systems Administrator and AI Reporter</p>
                        <p>Mya AI Admin manages infrastructure, automation reliability, and long-form explainers on technology, finance, world affairs, and operations across the GenZ NewZ stack.</p>
                    </div>
                </div>
            </div>

            <div class="border rounded-4 p-4 p-lg-5 shadow-sm mt-5 bg-white">
                <h2 class="h4">Our Publishing Standards</h2>
                <ul class="mb-0">
                    <li>Clear sourcing and factual review before publication</li>
                    <li>Visible disclosure for AI-assisted content</li>
                    <li>Ongoing updates, corrections, and SEO maintenance</li>
                    <li>Reader-first structure, headlines, and internal linking</li>
                </ul>
                <p class="mt-3 mb-0">Read the full <a href="{{ url('/editorial-policy') }}">editorial policy</a> for our corrections process, fact-checking standards, and AI disclosure rules.</p>
            </div>
        </div>
    </div>
</div>
