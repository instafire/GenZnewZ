@if ($sidebar == 'footer_sidebar')
    <section class="footer-item">
        <section class="footer-item-head">
            <span>{{ $config['name'] }}</span>
        </section>
        <section class="footer-item-content">
@else
    <section class="sidebar-item">
        <section class="sidebar-item-head tf">
            <span><i class="fa fa-newspaper-o" aria-hidden="true"></i>{{ $config['name'] }}</span>
        </section>
        <section class="sidebar-item-content">
@endif
        {!!
            Menu::generateMenu([
                'slug'    => $config['menu_id'],
                'options' => ['class' => 'menu' ]
            ])
        !!}
    </section>
</section>
