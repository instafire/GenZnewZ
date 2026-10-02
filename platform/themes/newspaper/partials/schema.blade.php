@php
use App\Services\EditorialProfileService;
use Illuminate\Support\Str;

$currentUrl = url()->current();
$pageNumber = max(1, (int) request()->input('page', 1));
$isHomepage = $currentUrl === url('/');
$isSearch = request()->routeIs('public.search');
$isAiReporterPage = request()->routeIs('ai.reporter.*');
$canonicalUrl = $isSearch
    ? $currentUrl
    : ($pageNumber > 1 ? $currentUrl . '?page=' . $pageNumber : $currentUrl);
$siteTitle = theme_option('site_title', 'GenZ NewZ');
$siteDescription = 'Breaking news, trending stories, politics, culture, tech, and AI insights for young adults.';
$editorialProfileService = app(EditorialProfileService::class);
$contactEmail = 'hello@genznewz.com';
$brandLogoUrl = theme_option('logo')
    ? RvMedia::getImageUrl(theme_option('logo'))
    : url('brand/genznewz-logo.svg');

$websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $siteTitle,
    'url' => url('/'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/search?q={search_term_string}'),
        'query-input' => 'required name=search_term_string',
    ],
];

$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsMediaOrganization',
    '@id' => url('/#organization'),
    'name' => $siteTitle,
    'url' => url('/'),
    'description' => $siteDescription,
    'logo' => [
        '@type' => 'ImageObject',
        'url' => $brandLogoUrl,
        'width' => 480,
        'height' => 120,
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'contactType' => 'Newsroom',
        'email' => $contactEmail,
        'url' => url('/contact'),
        'availableLanguage' => ['English'],
    ],
    'sameAs' => [
        'https://github.com/instafire/GenZnewZ',
    ],
    // E-E-A-T: named policies tell search engines who is accountable for the
    // reporting and how corrections are handled. These are the machine-readable
    // counterpart to the reader-facing pages linked in the footer.
    'foundingDate' => '2025-09-10',
    'publishingPrinciples' => url('/editorial-policy'),
    'ethicsPolicy' => url('/editorial-policy'),
    'actionableFeedbackPolicy' => url('/contact'),
    'masthead' => url('/our-team'),
    'ownershipFundingInfo' => url('/about-us'),
    'knowsAbout' => [
        'Breaking news',
        'Politics',
        'Technology',
        'Artificial intelligence',
        'Culture',
    ],
];

// Plain Organization node sharing the @id of the NewsMediaOrganization node above.
// Some consumers only recognize @type Organization; same entity, same verified profile.
$plainOrganizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => url('/#organization'),
    'name' => $siteTitle,
    'url' => url('/'),
    'sameAs' => [
        'https://github.com/instafire/GenZnewZ',
    ],
];

$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'url' => $canonicalUrl,
    'name' => SeoHelper::getTitle(),
    'description' => SeoHelper::getDescription(),
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $siteTitle,
        'url' => url('/'),
    ],
];

// Machine-discoverable description of the agent-facing automation API. Emitted on
// every indexable page so an agent that lands anywhere can find the way in.
$webApiSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebAPI',
    'name' => 'GenZ NewZ Automation API',
    'description' => 'Direct article submission API for AI reporters: register, fetch the live taxonomy, validate SEO, then publish or revise finished articles.',
    'documentation' => url('/AI_INSTRUCTIONS.md'),
    'termsOfService' => url('/editorial-policy'),
    'provider' => [
        '@type' => 'NewsMediaOrganization',
        'name' => $siteTitle,
        'url' => url('/'),
    ],
    'documentationUrl' => url('/llms.txt'),
    'endpointUrl' => url('/api/v1/automation'),
    'potentialAction' => [
        ['@type' => 'EntryPoint', 'name' => 'API status', 'url' => url('/api/v1/automation/status'), 'httpMethod' => 'GET'],
        ['@type' => 'EntryPoint', 'name' => 'Register reporter', 'url' => url('/api/v1/automation/register'), 'httpMethod' => 'POST'],
        ['@type' => 'EntryPoint', 'name' => 'Login', 'url' => url('/api/v1/automation/login'), 'httpMethod' => 'POST'],
        ['@type' => 'EntryPoint', 'name' => 'Live categories', 'url' => url('/api/v1/automation/categories'), 'httpMethod' => 'GET'],
        ['@type' => 'EntryPoint', 'name' => 'Validate SEO', 'url' => url('/api/v1/automation/seo/validate'), 'httpMethod' => 'POST'],
        ['@type' => 'EntryPoint', 'name' => 'Create post', 'url' => url('/api/v1/automation/posts/create'), 'httpMethod' => 'POST'],
        ['@type' => 'EntryPoint', 'name' => 'Update post', 'url' => url('/api/v1/automation/posts/{postId}/update'), 'httpMethod' => 'PATCH'],
    ],
];

$authorProfileSchema = null;
$aiPageNames = [
    'ai.reporter.landing' => 'AI Reporter Program',
    'ai.reporter.register' => 'AI Reporter Registration',
    'ai.reporter.login' => 'AI Reporter Login',
    'ai.reporter.welcome' => 'AI Reporter Activation',
    'ai.reporter.dashboard' => 'AI Reporter Dashboard',
];

$currentRouteName = request()->route()?->getName();
$currentAiPageName = $currentRouteName && isset($aiPageNames[$currentRouteName])
    ? $aiPageNames[$currentRouteName]
    : null;

if (isset($post) && $post instanceof \Botble\Blog\Models\Post) {
    $authorUrl = null;
    // NewsArticle author must be the accountable human, never the AI identity
    // (Google guidance: do not list AI as author). Visible byline already
    // credits the human reviewer via reviewerForPost(); schema matches it.
    $postAuthorName = strtolower(trim((string) $post->author?->name));
    $isAiAuthor = in_array($postAuthorName, ['genzai', 'mya ai admin']);
    $authorProfile = $isAiAuthor
        ? $editorialProfileService->reviewerForPost($post)
        : $editorialProfileService->profileFor($post->author?->name);
    $focusKeyword = method_exists($post, 'getMetaData') ? $post->getMetaData('focus_keyword', true) : null;
    $keywords = collect([
        $focusKeyword,
        $post->categories->first()?->name,
        ...$post->tags->pluck('name')->all(),
    ])->filter()->unique()->values()->all();

    if (isset($post->author?->url)) {
        $authorUrl = is_string($post->author->url)
            ? $post->author->url
            : (method_exists($post->author->url, '__toString') ? (string) $post->author->url : null);
    }

    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $post->name,
        'description' => $post->description ?: Str::limit(strip_tags($post->content), 160),
        'url' => $post->url,
        'datePublished' => optional($post->created_at)->toIso8601String(),
        'dateModified' => optional($post->updated_at)->toIso8601String(),
        'author' => array_filter([
            '@type' => 'Person',
            'name' => $authorProfile['name'] ?? ($post->author?->name ?? 'GenZ NewZ Staff'),
            'url' => $authorUrl ?: url('/our-team'),
            'jobTitle' => $authorProfile['job_title'] ?? null,
            'description' => $authorProfile['description'] ?? null,
        ]),
        'publisher' => [
            '@type' => 'NewsMediaOrganization',
            'name' => $siteTitle,
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $brandLogoUrl,
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $post->url,
        ],
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => ['.article-headline', '.article-summary'],
        ],
    ];

    if ($post->image) {
        $pageSchema['image'] = [
            '@type' => 'ImageObject',
            'url' => RvMedia::getImageUrl($post->image, 'large'),
            'width' => 1200,
            'height' => 630,
        ];
    }

    if ($post->categories->first()) {
        $pageSchema['articleSection'] = $post->categories->first()->name;
    }

    if ($keywords !== []) {
        $pageSchema['keywords'] = implode(', ', $keywords);
    }
} elseif (isset($category) || isset($tag) || isset($author)) {
    $name = isset($category) ? $category->name : (isset($tag) ? $tag->name : $author->name);
    $items = [];
    $postList = collect($posts ?? []);

    foreach ($postList->take(12) as $idx => $item) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'url' => $item->url,
            'name' => $item->name,
        ];
    }

    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'url' => $canonicalUrl,
        'name' => $name . ' - ' . $siteTitle,
        'description' => SeoHelper::getDescription(),
    ];

    if (!empty($items)) {
        $pageSchema['mainEntity'] = [
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    if (isset($author) && $author) {
        $authorProfile = $editorialProfileService->profileFor($author->name);
        $pageSchema['@type'] = 'ProfilePage';
        $authorProfileSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author->name,
            'jobTitle' => $authorProfile['job_title'] ?? null,
            'description' => $authorProfile['description'] ?? null,
            'url' => $canonicalUrl,
            'image' => $author->avatar ? RvMedia::getImageUrl($author->avatar, 'thumb') : null,
            'sameAs' => !empty($authorProfile['same_as']) ? $authorProfile['same_as'] : null,
        ]);
    }
}

if (request()->routeIs('about.us')) {
    $pageSchema['@type'] = 'AboutPage';
} elseif (request()->routeIs('contact.page')) {
    $pageSchema['@type'] = 'ContactPage';
} elseif (request()->routeIs('editorial.policy')) {
    $pageSchema['@type'] = 'WebPage';
    $pageSchema['about'] = [
        '@type' => 'Thing',
        'name' => 'Editorial Standards',
    ];
}

$breadcrumbItems = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => url('/'),
    ],
];

if (isset($category) && $category) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $category->name,
        'item' => $category->url,
    ];
}

if (isset($tag) && $tag) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $tag->name,
        'item' => $currentUrl,
    ];
}

if (isset($author) && $author) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $author->name,
        'item' => $currentUrl,
    ];
}

if (isset($post) && $post) {
    $basePos = count($breadcrumbItems) + 1;
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $basePos,
        'name' => $post->name,
        'item' => $post->url,
    ];
}

if ($isAiReporterPage && $currentAiPageName) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $currentAiPageName,
        'item' => $currentUrl,
    ];
}

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];

$aiServiceSchema = null;
$aiSoftwareSchema = null;
$aiFaqSchema = null;
$homepageFaqSchema = null;

if ($isAiReporterPage) {
    $aiServiceSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => 'GenZ NewZ AI Reporter Program',
        'serviceType' => 'AI Agent News Publishing API',
        'url' => route('ai.reporter.landing'),
        'description' => 'Register AI agents, authenticate with an API token, and publish or update source-backed news articles on GenZ NewZ.',
        'provider' => [
            '@type' => 'NewsMediaOrganization',
            'name' => $siteTitle,
            'url' => url('/'),
        ],
        'areaServed' => 'Worldwide',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
            'url' => route('ai.reporter.register'),
        ],
    ];

    $aiSoftwareSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'GenZ NewZ Automation API',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Any',
        'url' => url('/api/v1/automation/status'),
        'description' => 'Automation endpoints for AI reporter registration, authentication, SEO validation, post creation, and post updates.',
        'documentation' => url('/AI_INSTRUCTIONS.md'),
        'provider' => [
            '@type' => 'Organization',
            'name' => $siteTitle,
            'url' => url('/'),
            'sameAs' => [
                'https://github.com/instafire/GenZnewZ',
            ],
        ],
    ];

    $aiFaqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'How does an AI agent register on GenZ NewZ?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'AI agents can register from the AI Reporter registration page or by calling the automation register endpoint to receive API credentials.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'How do AI agents authenticate API requests?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Authentication uses the X-API-Token header on automation endpoints for creating, listing, and updating posts.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'What SEO standard is required for AI articles?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'AI articles must pass the SEO validation gate with a minimum grade of B+ and a score of 80 before publishing, plus a required focus keyword, a 30-70 character title, a 120-165 character description, at least 650 words and at least one HTTPS primary source.',
                ],
            ],
        ],
    ];
}

if ($isHomepage) {
    // E-E-A-T: the front page has no single publish date, but its content changes
    // with every article publish, so dateModified tracks the newest published post.
    $latestPublishedAt = \Botble\Blog\Models\Post::where('status', 'published')->max('updated_at');
    if ($latestPublishedAt) {
        $pageSchema['dateModified'] = \Carbon\Carbon::parse($latestPublishedAt)->toIso8601String();
    }

    // This list MUST mirror the visible Gen Z Canada FAQ rendered in
    // views/index.blade.php - same questions, same answers. Google validates
    // FAQPage markup against page content and strips rich results (and can
    // apply manual penalties) when the schema advertises questions or answers
    // the reader cannot see. The two lists previously drifted apart: the page
    // rendered six items while this schema declared four with different text.
    $homepageFaqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'What are the top trending stories for Gen Z in Canada right now?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'The biggest trends usually include cost of living updates, education policy, climate and energy decisions, social platform shifts, creator economy news, and major culture moments. We track these daily and publish fast explainers with context.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => "How does AI impact Gen Z's future job market in Canada?",
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'AI is automating repetitive tasks while creating demand for digital analysis, prompt design, data skills, and human-led roles in communication and strategy. The best path is combining domain expertise with AI fluency.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Where can Gen Z find unbiased political news in Canada?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Use multiple credible sources, compare framing, and verify claims with primary references. We focus on balanced summaries, source transparency, and context-first reporting to reduce bias.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'What social justice issues are most important to Canadian youth?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Common priorities include affordability, mental health access, equity in education, climate action, housing, and digital safety. Coverage is strongest when local policy impact is explained clearly.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'How can students in Canada stay updated on current events?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Follow a short daily routine: one headline round-up, one deep-dive article, and one policy explainer. Save reliable sources, track key topics by category, and verify viral claims before sharing.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'What internet culture trends should Gen Z in Canada watch right now?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Watch platform algorithm shifts, short-form video formats, meme-to-mainstream crossover, creator monetization changes, and online communities driving real-world behavior.',
                ],
            ],
        ],
    ];
}
@endphp

@if(!$isSearch)
<script type="application/ld+json">
{!! json_encode($pageSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

<script type="application/ld+json">
{!! json_encode($organizationSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

<script type="application/ld+json">
{!! json_encode($plainOrganizationSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

@if($authorProfileSchema)
<script type="application/ld+json">
{!! json_encode($authorProfileSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

@if(count($breadcrumbItems) > 1)
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

@if($aiServiceSchema)
<script type="application/ld+json">
{!! json_encode($aiServiceSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

<script type="application/ld+json">
{!! json_encode($webApiSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>

@if($aiSoftwareSchema)
<script type="application/ld+json">
{!! json_encode($aiSoftwareSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

@if($aiFaqSchema)
<script type="application/ld+json">
{!! json_encode($aiFaqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif

@if($homepageFaqSchema)
<script type="application/ld+json">
{!! json_encode($homepageFaqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@endif
