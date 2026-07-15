document.addEventListener('DOMContentLoaded', function () {
    var stickyBar = document.getElementById('sticky-add-to-cart');
    var form = document.querySelector('form.cart');
    var nativeButton = form ? form.querySelector('.single_add_to_cart_button') : null;

    if (!stickyBar || !form || !nativeButton) {
        return;
    }

    var stickyButton = stickyBar.querySelector('.orca-sticky-atc__button');

    function showSticky() {
        stickyBar.classList.add('show-sticky');
        stickyBar.setAttribute('aria-hidden', 'false');
    }

    function hideSticky() {
        stickyBar.classList.remove('show-sticky');
        stickyBar.setAttribute('aria-hidden', 'true');
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries[0].isIntersecting ? hideSticky() : showSticky();
        });
        observer.observe(nativeButton);
    } else {
        window.addEventListener('scroll', function () {
            var rect = nativeButton.getBoundingClientRect();
            (rect.bottom > 0 && rect.top < window.innerHeight) ? hideSticky() : showSticky();
        });
    }

    if (stickyButton) {
        stickyButton.addEventListener('click', function () {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(nativeButton);
            } else {
                nativeButton.click();
            }
        });
    }
});
