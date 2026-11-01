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
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
