document.addEventListener('DOMContentLoaded', function () {
    var stickyBar = document.getElementById('sticky-add-to-cart');
    var form = document.querySelector('form.cart');
    var nativeButton = form ? form.querySelector('.single_add_to_cart_button') : null;

    if (!stickyBar || !form || !nativeButton) {
        return;
    }

    var stickyContainer = stickyBar.querySelector('.container');
    var stickyButton = stickyBar.querySelector('.orca-sticky-atc__button');
    var stickyPrice = stickyBar.querySelector('.orca-sticky-atc__price');
    var isVariable = form.classList.contains('variations_form');
    var defaultPriceHtml = stickyPrice ? stickyPrice.innerHTML : '';
    var variationPairs = [];

    function showSticky() {
        stickyBar.classList.add('show-sticky');
        stickyBar.setAttribute('aria-hidden', 'false');
    }

    function hideSticky() {
        stickyBar.classList.remove('show-sticky');
        stickyBar.setAttribute('aria-hidden', 'true');
    }

    // Trigger the sticky bar a bit before the native button is fully out of
    // view rather than only once it has completely left the viewport.
    var revealBufferPx = 150;

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries[0].isIntersecting ? hideSticky() : showSticky();
        }, { rootMargin: '0px 0px -' + revealBufferPx + 'px 0px' });
        observer.observe(nativeButton);
    } else {
        window.addEventListener('scroll', function () {
            var rect = nativeButton.getBoundingClientRect();
            (rect.bottom > 0 && rect.top < window.innerHeight - revealBufferPx) ? hideSticky() : showSticky();
        });
    }

    function syncStickyButtonState() {
        if (stickyButton) {
            stickyButton.classList.toggle('orca-sticky-atc__button--disabled', !!nativeButton.disabled);
        }
    }

    function syncClonesFromOriginals() {
        variationPairs.forEach(function (pair) {
            pair.clone.value = pair.original.value;
        });
    }

    // Variable products: clone the real variation select(s) into the sticky
    // bar so a shopper can pick one without scrolling back to the main form,
    // keeping both in sync in either direction.
    if (isVariable) {
        var variationsWrap = form.querySelector('.variations');
        var selects = variationsWrap ? variationsWrap.querySelectorAll('select') : [];

        if (selects.length && stickyContainer) {
            var variationsGroup = document.createElement('div');
            variationsGroup.className = 'orca-sticky-atc__variations';

            selects.forEach(function (original) {
                var label = original.id ? document.querySelector('label[for="' + original.id + '"]') : null;
                var labelText = label ? label.textContent.trim() : '';
                var clone = original.cloneNode(true);

                clone.removeAttribute('id');
                clone.removeAttribute('name');
                clone.value = original.value;
                if (labelText) {
                    clone.setAttribute('aria-label', labelText);
                }

                clone.addEventListener('change', function () {
                    original.value = clone.value;
                    original.dispatchEvent(new Event('change', { bubbles: true }));
                });

                original.addEventListener('change', function () {
                    clone.value = original.value;
                });

                var field = document.createElement('div');
                field.className = 'orca-sticky-atc__variation-field';

                if (labelText) {
                    var cloneLabel = document.createElement('span');
                    cloneLabel.className = 'orca-sticky-atc__variation-label';
                    cloneLabel.textContent = labelText;
                    field.appendChild(cloneLabel);
                }

                field.appendChild(clone);
                variationsGroup.appendChild(field);
                variationPairs.push({ original: original, clone: clone });
            });

            stickyContainer.insertBefore(variationsGroup, stickyButton);
        }
    }

    // WooCommerce's own variation-form script triggers these jQuery events on
    // the form as selections change; mirror the price/disabled state (and
    // resync the cloned selects on reset) rather than assuming the native
    // button is always ready to submit.
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
            syncClonesFromOriginals();
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
