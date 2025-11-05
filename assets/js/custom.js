$(document).ready(function () {
    // Collapse Main NAv.
    $('.collap-main-nav, .close-nav').on('click', function () {
        $('.main-nav').toggleClass('main-nav-active');
    });

    // Toggle Icon.
    $(window).scroll(function () {
        if ($(this).scrollTop() > 200) {
            $('.icon-back-top').addClass('icon-back-top-active');
        } else {
            $('.icon-back-top').removeClass('icon-back-top-active');
        }
    });

    // Back Top.
    $('.icon-back-top').click(function () {
        $('body,html').animate({scrollTop: 0}, 'slow');
    });

    $('.form-popup').fancybox({
        maxWidth: 800,
        maxHeight: 600,
        fitToView: false,
        width: '70%',
        height: '70%',
        autoSize: false,
        closeClick: false,
        openEffect: 'none',
        closeEffect: 'none'
    });

    let superSearch = $('.super-search');
    let buttonSearch = $('.search-btn');

    buttonSearch.on('click', event => {
        event.preventDefault();
        if (buttonSearch.hasClass('active')) {
            superSearch.removeClass('active');
            buttonSearch.removeClass('active');
            $('body').removeClass('overflow');
            $('.quick-search > .form-control').focus();
        } else {
            superSearch.addClass('active');
            $('body').addClass('overflow');
            buttonSearch.addClass('active');
        }
    });
});



