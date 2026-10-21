(function () {
    'use strict';
    function init() {
        var filters = document.querySelectorAll('.nem-new-filter');
        filters.forEach(function (filter) {
            filter.addEventListener('toggle', function () {
                if (filter.open) filters.forEach(function (other) { if (other !== filter) other.open = false; });
            });
        });
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
