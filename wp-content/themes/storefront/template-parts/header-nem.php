<?php
/**
 * NEM Header Template Part
 * Designed to accurately match NEM Fashion style
 */

$home_url    = esc_url( home_url( '/' ) );
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? esc_url( wc_get_page_permalink( 'shop' ) ) : $home_url;
$cart_url    = function_exists( 'wc_get_cart_url' ) ? esc_url( wc_get_cart_url() ) : $home_url . 'cart/';
$account_url = function_exists( 'wc_get_page_permalink' ) ? esc_url( wc_get_page_permalink( 'myaccount' ) ) : $home_url . 'my-account/';
$cart_count  = ( function_exists( 'WC' ) && is_object( WC()->cart ) ) ? WC()->cart->get_cart_contents_count() : 0;

// Determine display name
$is_logged_in = is_user_logged_in();
if ( $is_logged_in ) {
    $current_user = wp_get_current_user();
    $user_display_name = ! empty( $current_user->display_name ) ? $current_user->display_name : 'Nguyễn Giang';
} else {
    $user_display_name = 'Tài khoản';
}
$img_dir         = get_template_directory_uri() . '/assets/images/nem/';
$logo_url        = $img_dir . 'logo.png';
$account_img_url = $img_dir . 'account.png';
$cart_img_url    = $img_dir . 'cart.png';
$search_img_url  = $img_dir . 'search.png';
?>

<div class="nem-header-sticky-wrapper">
    <div class="nem-header-inner">
        <!-- Logo -->
        <div class="nem-header-logo">
            <a href="<?php echo $home_url; ?>" title="NEM Fashion - Thời Trang Công Sở">
                <img src="<?php echo $logo_url; ?>" alt="NEM Fashion" class="nem-logo-img" />
            </a>
        </div>

        <!-- Main Desktop Navigation -->
        <nav class="nem-desktop-nav" role="navigation" aria-label="NEM Navigation">
            <ul class="nem-nav-list">
                <!-- SẢN PHẨM -->
                <li class="nem-nav-item nem-has-megamenu">
                    <a href="<?php echo $shop_url; ?>" class="nem-nav-link">
                        SẢN PHẨM <span class="nem-arrow-down">⌵</span>
                    </a>
                    <div class="nem-megamenu-panel">
                        <div class="nem-megamenu-inner">
                            <div class="nem-megamenu-col">
                                <a href="<?php echo $shop_url; ?>?category=dam" class="nem-col-title">ĐẦM</a>
                                <ul class="nem-sub-list">
                                    <li><a href="<?php echo $shop_url; ?>?category=dam-suong">Đầm suông</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=dam-dang-a">Đầm dáng A</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=dam-om">Đầm ôm</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=dam-maxi">Đầm maxi</a></li>
                                </ul>
                            </div>
                            <div class="nem-megamenu-col">
                                <a href="<?php echo $shop_url; ?>?category=ao" class="nem-col-title">ÁO</a>
                                <ul class="nem-sub-list">
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-so-mi">Áo sơ mi & Áo kiểu</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-len">Áo len</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-phong">Áo phông</a></li>
                                </ul>
                            </div>
                            <div class="nem-megamenu-col">
                                <a href="<?php echo $shop_url; ?>?category=ao-khoac" class="nem-col-title">ÁO KHOÁC</a>
                                <ul class="nem-sub-list">
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-chong-nang">Áo chống nắng</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-vest">Áo vest</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-phao">Áo phao</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=ao-khoac-kieu">Áo khoác kiểu</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=mang-to">Măng tô</a></li>
                                </ul>
                            </div>
                            <div class="nem-megamenu-col">
                                <a href="<?php echo $shop_url; ?>?category=chan-vay" class="nem-col-title">CHÂN VÁY</a>
                                <ul class="nem-sub-list">
                                    <li><a href="<?php echo $shop_url; ?>?category=chan-vay-xep-li">Chân váy xếp li</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=chan-vay-but-chi">Chân váy bút chì</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=chan-vay-dang-a">Chân váy chữ A</a></li>
                                </ul>
                                <a href="<?php echo $shop_url; ?>?category=ao-dai" class="nem-col-title nem-col-extra">ÁO DÀI</a>
                            </div>
                            <div class="nem-megamenu-col">
                                <a href="<?php echo $shop_url; ?>?category=quan" class="nem-col-title">QUẦN</a>
                                <ul class="nem-sub-list">
                                    <li><a href="<?php echo $shop_url; ?>?category=quan-dai">Quần dài</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=quan-short">Quần short</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=quan-lung">Quần lửng</a></li>
                                    <li><a href="<?php echo $shop_url; ?>?category=quan-jeans">Quần jeans</a></li>
                                </ul>
                                <a href="<?php echo $shop_url; ?>?category=set-bo" class="nem-col-title nem-col-extra">SET BỘ</a>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- SẢN PHẨM MỚI -->
                <li class="nem-nav-item">
                    <a href="<?php echo $shop_url; ?>?orderby=date" class="nem-nav-link">SẢN PHẨM MỚI</a>
                </li>

                <!-- NEM PLUS+ -->
                <li class="nem-nav-item">
                    <a href="<?php echo $shop_url; ?>?collection=nem-plus" class="nem-nav-link">NEM PLUS+</a>
                </li>

                <!-- SẢN PHẨM SALE -->
                <li class="nem-nav-item nem-has-dropdown">
                    <a href="<?php echo $shop_url; ?>?on_sale=1" class="nem-nav-link">
                        SẢN PHẨM SALE <span class="nem-arrow-down">⌵</span>
                    </a>
                    <ul class="nem-simple-dropdown">
                        <li><a href="<?php echo $shop_url; ?>?sale=199k">CHỈ TỪ 199K</a></li>
                        <li><a href="<?php echo $shop_url; ?>?sale=70">SALE UP 70%</a></li>
                    </ul>
                </li>

                <!-- BỘ SƯU TẬP -->
                <li class="nem-nav-item nem-has-dropdown">
                    <a href="<?php echo $home_url; ?>bo-suu-tap/" class="nem-nav-link">
                        BỘ SƯU TẬP <span class="nem-arrow-down">⌵</span>
                    </a>
                    <ul class="nem-simple-dropdown">
                        <li><a href="<?php echo $shop_url; ?>?collection=giao-thoi">GIAO THỜI</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=nhip-thanh-pho">Nhịp Thành phố</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=thoi-nha">Áo dài THỜI NHÃ</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=nhe-buoc-moi-ngay">NHẸ BƯỚC MỖI NGÀY</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Right Action Bar -->
        <div class="nem-header-actions">
            <!-- Search Icon -->
            <button type="button" class="nem-action-btn nem-search-trigger" id="nemSearchTrigger" title="Tìm kiếm">
                <svg class="nem-header-icon-svg nem-search-icon-svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="10.5" cy="10.5" r="7.5" />
                    <line x1="16" y1="16" x2="22" y2="22" />
                </svg>
            </button>

            <!-- Vertical Divider -->
            <span class="nem-divider-pipe"></span>

            <!-- User Account -->
            <div class="nem-user-wrapper nem-has-dropdown">
                <a href="<?php echo $account_url; ?>" class="nem-user-link">
                    <svg class="nem-header-icon-svg nem-account-icon-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#000000" stroke-width="1.8" />
                        <circle cx="12" cy="8.7" r="3.2" fill="#000000" />
                        <path d="M5.3 19.3 C6.6 15.6 9 14.5 12 14.5 C15 14.5 17.4 15.6 18.7 19.3 C16.9 21 14.6 22 12 22 C9.4 22 7.1 21 5.3 19.3 Z" fill="#000000" />
                    </svg>
                    <span class="nem-user-name">Tài khoản</span>
                </a>
                <div class="nem-user-dropdown-panel <?php echo $is_logged_in ? 'nem-is-logged-in' : 'nem-is-logged-out'; ?>">
                    <?php if ( $is_logged_in ) : ?>
                        <div class="nem-user-dropdown-header">
                            <span class="nem-user-badge">Khách hàng</span>
                            <strong><?php echo esc_html( $user_display_name ); ?></strong>
                        </div>
                        <ul class="nem-user-links">
                            <li><a href="<?php echo $account_url; ?>">Thông tin tài khoản</a></li>
                            <?php if ( function_exists( 'wc_get_account_endpoint_url' ) ) : ?>
                                <li><a href="<?php echo wc_get_account_endpoint_url( 'orders' ); ?>">Quản lý đơn hàng</a></li>
                                <li><a href="<?php echo wc_get_account_endpoint_url( 'edit-address' ); ?>">Thông tin giao hàng</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo wp_logout_url( $home_url ); ?>" class="nem-logout-link">Đăng xuất</a></li>
                        </ul>
                    <?php else : ?>
                        <ul class="nem-user-links nem-guest-links">
                            <li><a href="<?php echo $account_url; ?>">Đăng nhập</a></li>
                            <li><a href="<?php echo $account_url; ?>">Đăng ký</a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vertical Divider -->
            <span class="nem-divider-pipe"></span>

            <!-- Cart Section -->
            <div class="nem-cart-wrapper nem-has-dropdown">
                <a href="<?php echo $cart_url; ?>" class="nem-cart-link" id="nemHeaderCartLink">
                    <div class="nem-cart-bag-icon">
                        <span class="nem-cart-handle"></span>
                        <span class="nem-cart-badge nem-cart-count"><?php echo esc_html( $cart_count ); ?></span>
                    </div>
                    <span class="nem-cart-label">Giỏ hàng</span>
                </a>

                <!-- Mini Cart Dropdown -->
                <div class="nem-minicart-dropdown">
                    <div class="nem-minicart-header">
                        <strong>GIỎ HÀNG CỦA BẠN</strong>
                    </div>
                    <div class="nem-minicart-body widget_shopping_cart_content">
                        <?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
                            <?php woocommerce_mini_cart(); ?>
                        <?php else : ?>
                            <p class="nem-empty-cart-msg">Bạn chưa có sản phẩm nào trong giỏ hàng!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button type="button" class="nem-hamburger-btn" id="nemMobileToggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</div>

<!-- Search Bar Overlay Modal -->
<div class="nem-search-overlay" id="nemSearchOverlay">
    <div class="nem-search-backdrop" id="nemSearchBackdrop"></div>
    <div class="nem-search-box">
        <form role="search" method="get" class="nem-search-form" action="<?php echo $home_url; ?>">
            <div class="nem-search-input-wrap">
                <svg class="nem-search-input-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#666" stroke-width="2">
                    <circle cx="10.5" cy="10.5" r="7.5"></circle>
                    <line x1="21" y1="21" x2="15.8" y2="15.8"></line>
                </svg>
                <input type="search" class="nem-search-input" id="nemSearchInputField" placeholder="Tìm kiếm sản phẩm tại NEM..." value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
                <input type="hidden" name="post_type" value="product" />
                <button type="submit" class="nem-search-btn">Tìm kiếm</button>
                <button type="button" class="nem-search-close-btn" id="nemSearchCloseBtn" aria-label="Đóng tìm kiếm">&times;</button>
            </div>
        </form>
    </div>
</div>

<!-- Mobile Navigation Drawer -->
<div class="nem-mobile-drawer" id="nemMobileDrawer">
    <div class="nem-drawer-backdrop" id="nemDrawerBackdrop"></div>
    <div class="nem-drawer-content">
        <div class="nem-drawer-header">
            <img src="<?php echo $logo_url; ?>" alt="NEM Fashion" class="nem-drawer-logo" />
            <button type="button" class="nem-drawer-close" id="nemDrawerClose">&times;</button>
        </div>
        <div class="nem-drawer-body">
            <ul class="nem-mobile-nav-list">
                <li class="nem-m-item nem-m-has-sub">
                    <a href="<?php echo $shop_url; ?>">SẢN PHẨM</a>
                    <button type="button" class="nem-m-sub-toggle">+</button>
                    <ul class="nem-m-sub-list">
                        <li><a href="<?php echo $shop_url; ?>?category=dam">Đầm</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=ao">Áo</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=ao-khoac">Áo khoác</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=chan-vay">Chân váy</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=quan">Quần</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=ao-dai">Áo dài</a></li>
                        <li><a href="<?php echo $shop_url; ?>?category=set-bo">Set bộ</a></li>
                    </ul>
                </li>
                <li class="nem-m-item"><a href="<?php echo $shop_url; ?>?orderby=date">SẢN PHẨM MỚI</a></li>
                <li class="nem-m-item"><a href="<?php echo $shop_url; ?>?collection=nem-plus">NEM PLUS+</a></li>
                <li class="nem-m-item nem-m-has-sub">
                    <a href="<?php echo $shop_url; ?>?on_sale=1">SẢN PHẨM SALE</a>
                    <button type="button" class="nem-m-sub-toggle">+</button>
                    <ul class="nem-m-sub-list">
                        <li><a href="<?php echo $shop_url; ?>?sale=199k">CHỈ TỪ 199K</a></li>
                        <li><a href="<?php echo $shop_url; ?>?sale=70">SALE UP 70%</a></li>
                    </ul>
                </li>
                <li class="nem-m-item nem-m-has-sub">
                    <a href="<?php echo $home_url; ?>bo-suu-tap/">BỘ SƯU TẬP</a>
                    <button type="button" class="nem-m-sub-toggle">+</button>
                    <ul class="nem-m-sub-list">
                        <li><a href="<?php echo $shop_url; ?>?collection=giao-thoi">GIAO THỜI</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=nhip-thanh-pho">Nhịp Thành phố</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=thoi-nha">Áo dài THỜI NHÃ</a></li>
                        <li><a href="<?php echo $shop_url; ?>?collection=nhe-buoc-moi-ngay">NHẸ BƯỚC MỖI NGÀY</a></li>
                    </ul>
                </li>
            </ul>

            <div class="nem-drawer-footer">
                <a href="<?php echo $account_url; ?>" class="nem-drawer-account-link">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="7" r="4"></circle>
                        <path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path>
                    </svg>
                    <span><?php echo esc_html( $user_display_name ); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
