<footer class="footer">
    @if($sidebarContent = dynamic_sidebar('footer_sidebar'))
        <section class="footer-top">
            <section class="container">
                {!! $sidebarContent !!}
                <section class="cboth"></section>
            </section>
        </section>
    @endif
    <section class="footer-bottom">
        <section class="container">
            <section class="footer-bottom-left fleft">
                {!! BaseHelper::clean(nl2br(theme_option('copyright'))) !!}
            </section>
            <section class="footer-bottom-right fright">
                {!!
                    Menu::renderMenuLocation('footer-menu', [
                        'options' => ['id' => 'menu-footer-right-menu', 'class' => 'menu'],
                        'theme' => true,
                    ])
                !!}
            </section>
            <section class="cboth"></section>
        </section>
    </section>
</footer>

{!! Theme::footer() !!}

</body>

</html>
