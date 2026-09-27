@php
    $primarySidebar = dynamic_sidebar('primary_sidebar');
@endphp

@if ($primarySidebar)
    <aside class="sidebar fright">
        {!! $primarySidebar !!}
    </aside>
    <section class="cboth"></section>
@endif
