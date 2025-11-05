<section class="block-breakcrumb">
    <span>
        @foreach ($crumbs = Theme::breadcrumb()->getCrumbs() as $i => $crumb)
            @if (! $loop->last)
                <span>
                    <a href="{{ $crumb['url'] }}" title="{{ $crumb['label'] }}">
                        {{ $crumb['label'] }}
                    </a>
                </span> /
            @else
                <span class="breadcrumb_last">
                    {!! $crumb['label'] !!}
                </span>
            @endif
        @endforeach
    </span>
</section>
