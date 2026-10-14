/**
 * NEM Header & Footer Interactive Script
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
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
