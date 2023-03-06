
$('div.left_menu>ul>li>a').on('click', function () {
    url = $(this).attr('href')
    if (!url.startsWith('/admin')) {
        return false
    } else if (window.history && history.pushState) {
        var tw = window.top;
        var twa = tw.location.href.split("#");
        var newUrl = twa[0] + "#" + url;
        tw.history.replaceState(null, null, newUrl);
    }
})

