document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.nem-shop-main');
    if (!main) return;

    let activeRequest = null;
    let filterTimeout = null;
    let pendingFilterUrl = null;
    let pendingFilterKey = null;
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
            if (options.scrollToTop) window.scrollTo({ top: 0, behavior: 'smooth' });
            shopMain.classList.remove('is-filtering');
            shopMain.removeAttribute('aria-busy');
            const categoryDetails = nextSidebar?.querySelector('.nem-shop-categories');
            if (categoryDetails) categoryDetails.open = categoriesOpen;
            if (options.filterKey) openFilter(options.filterKey);
            if (options.pushState) window.history.pushState({}, '', result.url);

            const productCount = shopMain.querySelectorAll('.nem-shop-results .products li.product').length;
            announce(shopMain, `${productCount} sản phẩm`);
            if (pendingFilterUrl === requestUrl.href) {
                pendingFilterUrl = null;
                pendingFilterKey = null;
            }
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

        const attributeLink = event.target.closest('.nem-shop-size-option, .nem-shop-color-option');
        if (attributeLink && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
            const parameter = attributeLink.dataset.filterParameter;
            const slug = attributeLink.dataset.termSlug;
            const filter = attributeLink.closest('[data-filter-key]');
            if (!parameter || !slug || !filter) return;

            event.preventDefault();
            const url = new URL(pendingFilterUrl || window.location.href, window.location.href);
            const selectedTerms = (url.searchParams.get(parameter) || '').split(',').filter(Boolean);
            const isSelected = selectedTerms.includes(slug);
            const nextTerms = isSelected
                ? selectedTerms.filter(term => term !== slug)
                : [...selectedTerms, slug];

            if (nextTerms.length) {
                url.searchParams.set(parameter, nextTerms.join(','));
            } else {
                url.searchParams.delete(parameter);
            }
            if (parameter.startsWith('filter_')) {
                const queryTypeParameter = `query_type_${parameter.slice('filter_'.length)}`;
                if (nextTerms.length) {
                    url.searchParams.set(queryTypeParameter, 'or');
                } else {
                    url.searchParams.delete(queryTypeParameter);
                }
            }

            attributeLink.classList.toggle('is-selected', !isSelected);
            attributeLink.setAttribute('aria-pressed', String(!isSelected));
            const hasSelection = filter.querySelector('.nem-shop-size-option.is-selected, .nem-shop-color-option.is-selected');
            filter.querySelector('.nem-shop-filter-toggle')?.classList.toggle('is-selected', Boolean(hasSelection));

            pendingFilterUrl = url.href;
            pendingFilterKey = filter.getAttribute('data-filter-key');
            window.clearTimeout(filterTimeout);
            if (activeRequest) {
                activeRequest.abort();
                activeRequest = null;
                shopMain.classList.remove('is-filtering');
                shopMain.removeAttribute('aria-busy');
            }
            announce(shopMain, 'Đã chọn bộ lọc. Kết quả sẽ cập nhật sau nửa giây.');
            filterTimeout = window.setTimeout(() => {
                if (pendingFilterUrl) {
                    updateResults(pendingFilterUrl, {
                        filterKey: pendingFilterKey,
                        pushState: true
                    });
                }
            }, 500);
            return;
        }

        const link = event.target.closest('.nem-shop-size-option, .nem-shop-color-option, .nem-shop-price-option, .nem-shop-category-option, .nem-shop-all-categories, .nem-shop-results .woocommerce-pagination a');
        if (link && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
            const currentUrl = new URL(window.location.href);
            const url = new URL(link.href, window.location.href);
            if (pendingFilterUrl) {
                const pendingUrl = new URL(pendingFilterUrl, window.location.href);
                const queryKeys = new Set([...currentUrl.searchParams.keys(), ...url.searchParams.keys()]);
                queryKeys.forEach(key => {
                    if (url.searchParams.get(key) === currentUrl.searchParams.get(key)) return;
                    const values = url.searchParams.getAll(key);
                    pendingUrl.searchParams.delete(key);
                    values.forEach(value => pendingUrl.searchParams.append(key, value));
                });
                url.search = pendingUrl.search;
                window.clearTimeout(filterTimeout);
                pendingFilterUrl = url.href;
            }
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            const filter = link.closest('[data-filter-key]');
            updateResults(url.href, {
                filterKey: filter?.getAttribute('data-filter-key'),
                scrollToTop: link.matches('.nem-shop-results .woocommerce-pagination a'),
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
