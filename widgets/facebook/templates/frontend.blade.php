@if (!empty($config['facebook_name']) && !empty($config['facebook_url']))
    @if ($sidebar == 'footer_sidebar')
        <section class="footer-item">
            <section class="footer-item-head">
                <span>{{ $config['name'] }}</span>
            </section><!-- end .footer-item-head -->
            <section class="footer-item-content">
    @else
        <section class="sidebar-item">
            <section class="sidebar-item-head tf">
                <span><i class="fa fa-newspaper-o" aria-hidden="true"></i>{{ $config['name'] }}</span>
            </section><!-- end .sidebar-item-head -->
            <section class="sidebar-item-content">
        @endif
        <div class="fb-page" data-href="{{ $config['facebook_url'] }}" data-tabs="{{ $config['facebook_tabs'] }}"
             data-width="" data-height="" data-small-header="false"
             data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true">
            <blockquote cite="{{ $config['facebook_url'] }}"
                        class="fb-xfbml-parse-ignore"><a
                    href="{{ $config['facebook_url'] }}">{{ $config['facebook_name'] }}</a></blockquote>
        </div>
    </section>
</section>
@endif

<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0"></script>
