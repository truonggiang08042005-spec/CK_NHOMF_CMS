(function () {
    'use strict';
    function init() {
        var filters = document.querySelectorAll('.nem-new-filter');
        function positionPanel(filter) {
            var panel = filter.querySelector('.nem-new-filter-panel');
            if (!panel || !filter.open) return;
            filter.style.setProperty('--nem-panel-shift', '0px');
            var bounds = panel.getBoundingClientRect();
            var shift = Math.max(0, 12 - bounds.left);
            if (bounds.right + shift > window.innerWidth - 12) shift = window.innerWidth - 12 - bounds.right;
            filter.style.setProperty('--nem-panel-shift', shift + 'px');
        }
        document.querySelectorAll('.nem-new-filters').forEach(function (form) {
            form.classList.add('is-enhanced');
            form.addEventListener('change', function (event) {
                if (event.target.matches('input[type="checkbox"], input[type="radio"]')) form.requestSubmit();
            });
        });
        filters.forEach(function (filter) {
            filter.addEventListener('toggle', function () {
                if (filter.open) {
                    filters.forEach(function (other) { if (other !== filter) other.open = false; });
                    positionPanel(filter);
                }
            });
        });
        window.addEventListener('resize', function () { filters.forEach(positionPanel); });
        document.addEventListener('click', function (event) {
            filters.forEach(function (filter) { if (!filter.contains(event.target)) filter.open = false; });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            filters.forEach(function (filter) {
                if (filter.open) { filter.open = false; filter.querySelector('summary').focus(); }
            });
        });
        var categories = document.querySelector('.nem-new-categories');
        if (categories && window.matchMedia('(max-width: 700px)').matches) categories.open = false;

        // Quantity controls for NEM product cards
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('.nem-qty-btn');
            if (!btn) return;
            event.preventDefault();
            event.stopPropagation();
            var wrapper = btn.closest('.nem-product-quantity-control');
            if (!wrapper) return;
            var input = wrapper.querySelector('.nem-qty-input');
            if (!input) return;
            var min = parseInt(input.getAttribute('min'), 10) || 1;
            var max = parseInt(input.getAttribute('max'), 10) || 9999;
            var currentVal = parseInt(input.value, 10) || min;
            if (btn.classList.contains('nem-qty-minus')) {
                if (currentVal > min) {
                    input.value = currentVal - 1;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } else if (btn.classList.contains('nem-qty-plus')) {
                if (currentVal < max) {
                    input.value = currentVal + 1;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

        document.addEventListener('change', function (event) {
            if (!event.target.classList.contains('nem-qty-input')) return;
            var input = event.target;
            var min = parseInt(input.getAttribute('min'), 10) || 1;
            var max = parseInt(input.getAttribute('max'), 10) || 9999;
            var val = parseInt(input.value, 10);
            if (isNaN(val) || val < min) {
                val = min;
            } else if (val > max) {
                val = max;
            }
            input.value = val;

            var card = input.closest('.nem-collection-product');
            if (card) {
                var links = card.querySelectorAll('a[href]');
                links.forEach(function (link) {
                    try {
                        var url = new URL(link.href, window.location.origin);
                        url.searchParams.set('quantity', val);
                        link.href = url.toString();
                    } catch (e) {}
                });
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
