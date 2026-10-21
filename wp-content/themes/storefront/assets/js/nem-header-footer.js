/**
 * NEM Header & Footer Interactive Script
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Desktop navigation: hover, click on the arrow, or keyboard focus.
        var navigation = document.querySelector('.nem-desktop-nav');
        var menuItems = navigation ? navigation.querySelectorAll('.nem-nav-item') : [];
        function setMenuOpen(item, open) {
            var toggle = item.querySelector('.nem-menu-toggle');
            if (!toggle) return;
            item.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        }
        function closeMenus(except) {
            menuItems.forEach(function (item) { if (item !== except) setMenuOpen(item, false); });
        }
        if (navigation) {
            navigation.classList.add('nem-menu-ready');
            menuItems.forEach(function (item) {
                var link = item.querySelector('.nem-nav-link');
                var toggle = item.querySelector('.nem-menu-toggle');
                if (link) {
                    var destination = new URL(link.href, location.href);
                    var current = new URL(location.href);
                    var isCollection = /\/bo-suu-tap\/?$/.test(destination.pathname);
                    var currentCollection = current.searchParams.get('collection');
                    var knownCollection = ['thoi-nha', 'nhe-buoc-moi-ngay', 'giao-thoi', 'nhip-thanh-pho'].indexOf(currentCollection) !== -1;
                    var active = isCollection ? /\/bo-suu-tap\/?$/.test(current.pathname) || knownCollection : destination.pathname === current.pathname && destination.search === current.search;
                    item.classList.toggle('is-active', active);
                    if (active) link.setAttribute('aria-current', 'page');
                }
                if (!toggle) return;
                item.addEventListener('pointerenter', function (event) {
                    if (event.pointerType === 'mouse') { closeMenus(item); setMenuOpen(item, true); }
                });
                item.addEventListener('pointerleave', function (event) {
                    if (event.pointerType !== 'mouse') return;
                    if (!item.contains(document.activeElement)) setMenuOpen(item, false);
                });
                item.addEventListener('focusin', function () { closeMenus(item); setMenuOpen(item, true); });
                item.addEventListener('focusout', function (event) {
                    if (!item.contains(event.relatedTarget)) setMenuOpen(item, false);
                });
                var openBeforePointerDown;
                toggle.addEventListener('pointerdown', function () {
                    openBeforePointerDown = item.classList.contains('is-open');
                });
                toggle.addEventListener('click', function () {
                    var open = openBeforePointerDown === undefined ? toggle.getAttribute('aria-expanded') !== 'true' : !openBeforePointerDown;
                    openBeforePointerDown = undefined;
                    closeMenus(item);
                    setMenuOpen(item, open);
                });
                toggle.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        setMenuOpen(item, true);
                        var first = document.getElementById(toggle.getAttribute('aria-controls')).querySelector('a');
                        if (first) first.focus();
                    }
                });
            });
            document.addEventListener('click', function (event) {
                if (!navigation.contains(event.target)) closeMenus();
            });
            navigation.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    var item = event.target.closest('.nem-nav-item');
                    if (item) {
                        item.querySelector('.nem-nav-link').focus();
                        setMenuOpen(item, false);
                    }
                }
            });
        }

        // --- 1. Search Overlay ---
        var searchTrigger = document.getElementById('nemSearchTrigger');
        var searchOverlay = document.getElementById('nemSearchOverlay');
        var searchBackdrop = document.getElementById('nemSearchBackdrop');
        var searchCloseBtn = document.getElementById('nemSearchCloseBtn');
        var searchInputField = document.getElementById('nemSearchInputField');

        function openSearch() {
            if (searchOverlay) {
                searchOverlay.classList.add('active');
                if (searchInputField) {
                    setTimeout(function () {
                        searchInputField.focus();
                    }, 100);
                }
            }
        }

        function closeSearch() {
            if (searchOverlay) {
                searchOverlay.classList.remove('active');
            }
        }

        if (searchTrigger) {
            searchTrigger.addEventListener('click', function (e) {
                e.preventDefault();
                openSearch();
            });
        }

        if (searchBackdrop) {
            searchBackdrop.addEventListener('click', closeSearch);
        }

        if (searchCloseBtn) {
            searchCloseBtn.addEventListener('click', closeSearch);
        }

        // Close on Esc key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeSearch();
                closeMobileDrawer();
            }
        });

        // --- 2. Mobile Drawer Navigation ---
        var mobileToggle = document.getElementById('nemMobileToggle');
        var mobileDrawer = document.getElementById('nemMobileDrawer');
        var drawerBackdrop = document.getElementById('nemDrawerBackdrop');
        var drawerClose = document.getElementById('nemDrawerClose');

        function openMobileDrawer() {
            if (mobileDrawer) {
                mobileDrawer.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeMobileDrawer() {
            if (mobileDrawer) {
                mobileDrawer.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function (e) {
                e.preventDefault();
                openMobileDrawer();
            });
        }

        if (drawerBackdrop) {
            drawerBackdrop.addEventListener('click', closeMobileDrawer);
        }

        if (drawerClose) {
            drawerClose.addEventListener('click', closeMobileDrawer);
        }

        // Mobile submenu accordions
        var subToggles = document.querySelectorAll('.nem-m-sub-toggle');
        subToggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var subList = this.nextElementSibling;
                if (subList) {
                    var isOpen = subList.classList.contains('active');
                    if (isOpen) {
                        subList.classList.remove('active');
                        this.textContent = '+';
                    } else {
                        subList.classList.add('active');
                        this.textContent = '−';
                    }
                }
            });
        });

        // --- 3. Scroll to Top Button ---
        var scrollTopBtn = document.getElementById('nemScrollToTopBtn');
        var stickyHeader = document.querySelector('.nem-header-sticky-wrapper');

        window.addEventListener('scroll', function () {
            var scrollY = window.pageYOffset || document.documentElement.scrollTop;

            // Back to top button visibility
            if (scrollTopBtn) {
                if (scrollY > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            }

            // Header shadow boost on scroll
            if (stickyHeader) {
                if (scrollY > 30) {
                    stickyHeader.style.boxShadow = '0 3px 12px rgba(0,0,0,0.08)';
                } else {
                    stickyHeader.style.boxShadow = '0 2px 8px rgba(0,0,0,0.04)';
                }
            }
        });

        if (scrollTopBtn) {
            scrollTopBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    });

    // --- 4. WooCommerce Dynamic Cart Fragment Hook ---
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function (event, fragments, cart_hash) {
            // Trigger cart count update if available in fragments
            if (fragments && fragments['.nem-cart-count']) {
                jQuery('.nem-cart-count').replaceWith(fragments['.nem-cart-count']);
            }
        });
    }
})();
