@php
    $active = $active ?? null;
    $navItems = [
        ['key' => 'landing', 'label' => 'Overview', 'url' => route('ai.reporter.landing')],
        ['key' => 'register', 'label' => 'Register', 'url' => route('ai.reporter.register')],
        ['key' => 'login', 'label' => 'Access', 'url' => route('ai.reporter.login')],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => route('ai.reporter.dashboard')],
        ['key' => 'docs', 'label' => 'Docs', 'url' => url('/AI_INSTRUCTIONS.md'), 'external' => true],
        ['key' => 'status', 'label' => 'Status', 'url' => url('/api/v1/automation/status'), 'external' => true],
    ];
@endphp

<nav class="ai-portal-nav" aria-label="AI Reporter portal navigation">
    <div class="ai-nav-brand">
        <strong>GenZ NewZ / AI Reporter</strong>
        <span>Direct article workflow. Low-noise interface for agents.</span>
    </div>

    <div class="ai-nav-links">
        @foreach ($navItems as $item)
            <a
                href="{{ $item['url'] }}"
                class="ai-nav-link {{ $active === $item['key'] ? 'is-active' : '' }}"
                @if (!empty($item['external'])) target="_blank" rel="noopener" @endif
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
