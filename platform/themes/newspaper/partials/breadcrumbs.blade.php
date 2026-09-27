@php
// Build breadcrumb trail
$breadcrumbs = [];

// Always add home
$breadcrumbs[] = [
    'name' => 'Home',
    'url' => url('/'),
    'active' => false
];

// Add category if exists
if (isset($category) && $category) {
    $breadcrumbs[] = [
        'name' => $category->name,
        'url' => $category->url,
        'active' => !isset($post)
    ];
}

// Add tag if on tag page
if (isset($tag) && $tag) {
    $breadcrumbs[] = [
        'name' => $tag->name,
        'url' => $tag->url,
        'active' => true
    ];
}

// Add author if on author page
if (isset($author) && $author) {
    $breadcrumbs[] = [
        'name' => $author->name,
        'url' => $author->url,
        'active' => true
    ];
}

// Add current page/post
if (isset($post) && $post) {
    if ($post->categories->first() && !isset($category)) {
        $breadcrumbs[] = [
            'name' => $post->categories->first()->name,
            'url' => $post->categories->first()->url,
            'active' => false
        ];
    }
    
    $breadcrumbs[] = [
        'name' => Str::limit($post->name, 50),
        'url' => $post->url,
        'active' => true
    ];
}

if (isset($page) && $page) {
    $breadcrumbs[] = [
        'name' => Str::limit($page->name, 50),
        'url' => $page->url ?? url()->current(),
        'active' => true
    ];
}

// Don't show breadcrumbs on homepage
$showBreadcrumbs = count($breadcrumbs) > 1;
@endphp

@if($showBreadcrumbs)
<nav class="breadcrumb-nav" aria-label="Breadcrumb">
    <div class="breadcrumb-container">
        <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
            @foreach($breadcrumbs as $index => $crumb)
            <li class="breadcrumb-item {{ $crumb['active'] ? 'active' : '' }}" 
                itemprop="itemListElement" 
                itemscope 
                itemtype="https://schema.org/ListItem">
                
                @if(!$crumb['active'])
                <a href="{{ $crumb['url'] }}" itemprop="item">
                    <span itemprop="name">{{ $crumb['name'] }}</span>
                </a>
                <meta itemprop="position" content="{{ $index + 1 }}" />
                @else
                <span itemprop="name">{{ $crumb['name'] }}</span>
                <meta itemprop="position" content="{{ $index + 1 }}" />
                @endif
            </li>
            @endforeach
        </ol>
    </div>
</nav>
@endif
