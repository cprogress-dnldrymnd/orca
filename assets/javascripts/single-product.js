document.addEventListener('DOMContentLoaded', function () {
    var stickyBar = document.getElementById('sticky-add-to-cart');
    var form = document.querySelector('form.cart');
    var nativeButton = form ? form.querySelector('.single_add_to_cart_button') : null;

    if (!stickyBar || !form || !nativeButton) {
        return;
    }

    var stickyButton = stickyBar.querySelector('.orca-sticky-atc__button');
    var stickyPrice = stickyBar.querySelector('.orca-sticky-atc__price');
    var isVariable = form.classList.contains('variations_form');
    var defaultPriceHtml = stickyPrice ? stickyPrice.innerHTML : '';

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

    function syncStickyButtonState() {
        if (stickyButton) {
            stickyButton.classList.toggle('orca-sticky-atc__button--disabled', !!nativeButton.disabled);
        }
    }

    // Variable products: the native button stays disabled until a variation is
    // selected, and its price is a range until then. Mirror both via the
    // jQuery events WooCommerce's own variation-form script triggers on this
    // form, rather than assuming the button is always ready to submit.
    if (isVariable && window.jQuery) {
        var $form = window.jQuery(form);

        $form.on('found_variation', function (event, variation) {
            if (stickyPrice && variation && variation.price_html) {
                stickyPrice.innerHTML = variation.price_html;
            }
            syncStickyButtonState();
        });

        $form.on('reset_data hide_variation', function () {
            if (stickyPrice) {
                stickyPrice.innerHTML = defaultPriceHtml;
            }
            syncStickyButtonState();
        });

        syncStickyButtonState();
    }

    if (stickyButton) {
        stickyButton.addEventListener('click', function () {
            if (isVariable && nativeButton.disabled) {
                // No variation selected yet - guide the shopper to the picker
                // instead of submitting, which WooCommerce would reject anyway.
                var variationsEl = form.querySelector('.variations') || form;
                variationsEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(nativeButton);
            } else {
                nativeButton.click();
            }
        });
    }
});
