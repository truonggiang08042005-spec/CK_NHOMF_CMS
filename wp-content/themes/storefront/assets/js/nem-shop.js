document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.nem-shop-main');
    if (!main) return;

    let activeRequest = null;
    let columns = main.dataset.columns || '3';
    let categoriesOpen = window.matchMedia('(min-width: 761px)').matches;
    const getMain = () => document.querySelector('.nem-shop-main');
    const setColumns = (shopMain, value) => {
        shopMain.dataset.columns = value;
        shopMain.querySelectorAll('.nem-shop-view-button').forEach(button => {
            const selected = button.dataset.columns === value;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
    };
    const positionPanel = filter => {
        const panel = filter.querySelector('.nem-shop-filter-panel');
        if (!panel || panel.hidden) return;
        filter.style.setProperty('--nem-panel-shift', '0px');
        const bounds = panel.getBoundingClientRect();
        let shift = 0;
        if (bounds.left < 12) shift = 12 - bounds.left;
        if (bounds.right + shift > window.innerWidth - 12) shift = window.innerWidth - 12 - bounds.right;
        filter.style.setProperty('--nem-panel-shift', `${shift}px`);
    };

    const closeFilter = filter => {
        const toggle = filter.querySelector('.nem-shop-filter-toggle');
        const panel = filter.querySelector('.nem-shop-filter-panel');
        if (!toggle || !panel) return;
        filter.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
        filter.style.removeProperty('--nem-panel-shift');
    };

    const closeFilters = container => container.querySelectorAll('[data-shop-filter]').forEach(closeFilter);
    const openFilter = key => {
        if (!key) return;
        const filter = getMain()?.querySelector(`[data-filter-key="${key}"]`);
        const toggle = filter?.querySelector('.nem-shop-filter-toggle');
        const panel = filter?.querySelector('.nem-shop-filter-panel');
        if (!toggle || !panel) return;
        filter.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        positionPanel(filter);
    };
    const announce = (shopMain, message, isError = false) => {
        const status = shopMain.querySelector('.nem-shop-filter-status');
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('has-error', isError);
    };

    const updateResults = (url, options = {}) => {
        const shopMain = getMain();
        const requestUrl = new URL(url, window.location.href);
        if (!shopMain || requestUrl.origin !== window.location.origin) return Promise.resolve();

        activeRequest?.abort();
        activeRequest = new AbortController();
        const request = activeRequest;
        shopMain.classList.add('is-filtering');
        shopMain.setAttribute('aria-busy', 'true');
        announce(shopMain, 'Đang lọc sản phẩm...');

        return fetch(requestUrl.href, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: request.signal
        }).then(response => {
            if (!response.ok) throw new Error(`Shop request failed with status ${response.status}`);
            return response.text().then(html => ({ html, url: response.url }));
        }).then(result => {
            if (activeRequest !== request) return;
            const parsedPage = new DOMParser().parseFromString(result.html, 'text/html');
            const nextMain = parsedPage.querySelector('.nem-shop-main');
            const currentResults = shopMain.querySelector('.nem-shop-results');
            const nextResults = nextMain?.querySelector('.nem-shop-results');
            const currentFilters = shopMain.querySelector('.nem-shop-filters');
            const nextFilters = nextMain?.querySelector('.nem-shop-filters');
            const currentSidebar = document.querySelector('.nem-shop-sidebar');
            const nextSidebar = parsedPage.querySelector('.nem-shop-sidebar');
            const currentTitle = shopMain.querySelector('.nem-shop-toolbar-title');
            const nextTitle = nextMain?.querySelector('.nem-shop-toolbar-title');

            if (
                !nextResults ||
                !currentResults ||
                !currentTitle ||
                !nextTitle ||
                Boolean(currentFilters) !== Boolean(nextFilters) ||
                Boolean(currentSidebar) !== Boolean(nextSidebar)
            ) {
                throw new Error('Shop response did not contain the expected title and results.');
            }

            currentResults.replaceWith(nextResults);
            if (currentFilters && nextFilters) currentFilters.replaceWith(nextFilters);
            if (currentSidebar && nextSidebar) currentSidebar.replaceWith(nextSidebar);
            currentTitle.textContent = nextTitle.textContent;
            setColumns(shopMain, columns);
            shopMain.classList.remove('is-filtering');
            shopMain.removeAttribute('aria-busy');
            const categoryDetails = nextSidebar?.querySelector('.nem-shop-categories');
            if (categoryDetails) categoryDetails.open = categoriesOpen;
            if (options.filterKey) openFilter(options.filterKey);
            if (options.pushState) window.history.pushState({}, '', result.url);

            const productCount = shopMain.querySelectorAll('.nem-shop-results .products li.product').length;
            announce(shopMain, `${productCount} sản phẩm`);
        }).catch(error => {
            if (error.name === 'AbortError' || activeRequest !== request) return;
            shopMain.classList.remove('is-filtering');
            shopMain.removeAttribute('aria-busy');
            announce(shopMain, 'Không thể lọc sản phẩm. Vui lòng thử lại.', true);
            console.error('Unable to update Shop products:', error);
        }).then(() => {
            if (activeRequest === request) activeRequest = null;
        });
    };

    const sidebar = document.querySelector('.nem-shop-sidebar');
    const categoryDetails = sidebar?.querySelector('.nem-shop-categories');
    if (categoryDetails) {
        categoriesOpen = categoryDetails.open;
        if (window.matchMedia('(max-width: 760px)').matches) categoryDetails.open = false;
    }

    document.addEventListener('click', event => {
        const shopMain = getMain();
        if (!shopMain) return;

        const toggle = event.target.closest('.nem-shop-main .nem-shop-filter-toggle');
        if (toggle) {
            event.preventDefault();
            const filter = toggle.closest('[data-shop-filter]');
            const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';
            closeFilters(shopMain);
            if (shouldOpen && filter) {
                filter.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                filter.querySelector('.nem-shop-filter-panel').hidden = false;
                positionPanel(filter);
            }
            return;
        }

        const viewButton = event.target.closest('.nem-shop-main .nem-shop-view-button');
        if (viewButton) {
            const selectedColumns = viewButton.dataset.columns;
            if (selectedColumns !== '2' && selectedColumns !== '3') return;
            columns = selectedColumns;
            setColumns(shopMain, columns);
            return;
        }

        const link = event.target.closest('.nem-shop-size-option, .nem-shop-color-option, .nem-shop-price-option, .nem-shop-category-option, .nem-shop-all-categories, .nem-shop-results .woocommerce-pagination a');
        if (link && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            const filter = link.closest('[data-filter-key]');
            updateResults(url.href, {
                filterKey: filter?.getAttribute('data-filter-key'),
                pushState: true
            });
            return;
        }

        if (!event.target.closest('.nem-shop-main [data-shop-filter]')) closeFilters(shopMain);
    });

    document.addEventListener('keydown', event => {
        const shopMain = getMain();
        if (shopMain && event.key === 'Escape') closeFilters(shopMain);
    });

    document.addEventListener('toggle', event => {
        if (event.target.matches?.('.nem-shop-categories')) categoriesOpen = event.target.open;
    }, true);

    window.addEventListener('resize', () => {
        getMain()?.querySelectorAll('[data-shop-filter].is-open').forEach(positionPanel);
    });

    window.addEventListener('popstate', () => updateResults(window.location.href));
});
