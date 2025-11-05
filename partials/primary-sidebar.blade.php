@php
    $primarySidebar = dynamic_sidebar('primary_sidebar');

    if (is_plugin_active('ads')) {
        $primarySidebar = AdsManager::display('top-sidebar', ['class' => 'sidebar-item sidebar-item-ads'])
            . $primarySidebar .
            AdsManager::display('bottom-sidebar', ['class' => 'sidebar-item sidebar-item-ads']);
    }
@endphp

@if ($primarySidebar)
    <aside class="sidebar fright">
        {!! $primarySidebar !!}
        
        <div class="sidebar-item" style="text-align: center;">
            
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8161801058322526"
                    crossorigin="anonymous"></script>

            <div style="margin-bottom: 15px;">
                <ins class="adsbygoogle"
                     style="display:inline-block;width:300px;height:600px"
                     data-ad-client="ca-pub-8161801058322526"
                     data-ad-slot="7875069948"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>

            <div style="margin-bottom: 15px;">
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-format="fluid"
                     data-ad-layout-key="+2t+rl+2h-1m-4u"
                     data-ad-client="ca-pub-8161801058322526"
                     data-ad-slot="2991969424"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>

            <div>
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-format="fluid"
                     data-ad-layout-key="-fb+5w+4e-db+86"
                     data-ad-client="ca-pub-8161801058322526"
                     data-ad-slot="6773224348"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>

        </div>
    </aside>
    <section class="cboth"></section>
@endif