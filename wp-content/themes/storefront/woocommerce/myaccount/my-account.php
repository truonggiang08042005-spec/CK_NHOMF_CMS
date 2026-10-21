<?php
/**
 * My Account page override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

$fullname = nem_customer_display_name();
$addr_name  = $fullname;

$user_email = $current_user->user_email;

// Country
$billing_country = get_user_meta( $user_id, 'billing_country', true );
if ( empty( $billing_country ) || 'VN' === $billing_country ) {
    $country_name = 'Vietnam';
} else {
    $country_name = function_exists( 'WC' ) && isset( WC()->countries->countries[ $billing_country ] ) 
        ? WC()->countries->countries[ $billing_country ] 
        : $billing_country;
}

// Phone
$phone = get_user_meta( $user_id, 'billing_phone', true );

// Address
$address_line = get_user_meta( $user_id, 'billing_address_1', true );
$has_address = '' !== $address_line || '' !== $phone;

// Determine active tab
$current_endpoint = function_exists( 'WC' ) && isset( WC()->query ) ? WC()->query->get_current_endpoint() : '';
$active_tab = 'account';
if ( in_array( $current_endpoint, array( 'orders', 'view-order' ), true ) ) {
    $active_tab = 'orders';
} elseif ( in_array( $current_endpoint, array( 'edit-address' ), true ) ) {
    $active_tab = 'address';
}
?>

<div class="nem-profile-wrapper">
    <!-- Breadcrumb -->
    <div class="nem-profile-breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a>
        <span class="nem-bc-separator">/</span>
        <span class="nem-bc-active" id="nemBcActive"><?php echo ( 'address' === $active_tab ) ? 'ĐỊA CHỈ' : 'TÀI KHOẢN'; ?></span>
    </div>

    <!-- Profile Header Greeting -->
    <div class="nem-profile-greeting-wrap">
        <span class="nem-greeting-sub">Xin chào,</span>
        <h1 class="nem-greeting-name" id="nemGreetingName"><?php echo esc_html( mb_strtoupper( $fullname, 'UTF-8' ) ); ?></h1>
    </div>

    <!-- Main Grid: Sidebar Menu + Account Content -->
    <div class="nem-profile-grid">
        <!-- Sidebar Navigation -->
        <div class="nem-profile-sidebar-col">
            <nav class="nem-account-nav">
                <ul class="nem-account-nav-list">
                    <li class="nem-account-nav-item <?php echo ( 'account' === $active_tab ) ? 'is-active' : ''; ?>" data-target="account">
                        <a href="#account" data-tab="account" class="nem-account-nav-link">Thông tin tài khoản</a>
                    </li>
                    <li class="nem-account-nav-item <?php echo ( 'orders' === $active_tab ) ? 'is-active' : ''; ?>" data-target="orders">
                        <a href="#orders" data-tab="orders" class="nem-account-nav-link">Quản lý đơn hàng</a>
                    </li>
                    <li class="nem-account-nav-item <?php echo ( 'address' === $active_tab ) ? 'is-active' : ''; ?>" data-target="address">
                        <a href="#address" data-tab="address" class="nem-account-nav-link">Thông tin giao hàng</a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Account Content Panels -->
        <div class="nem-profile-content-col">
            <!-- TAB 1: Thông tin tài khoản (Image 1) -->
            <div class="nem-tab-panel <?php echo ( 'account' === $active_tab ) ? 'is-active' : ''; ?>" id="nemPanelAccount">
                <div class="nem-profile-dashboard">
                    <div class="nem-profile-field-group">
                        <label class="nem-field-label" for="nemFieldFullname">Họ tên</label>
                        <div class="nem-field-box">
                            <input type="text" class="nem-field-input" id="nemFieldFullname" value="<?php echo esc_attr( $fullname ); ?>" readonly />
                        </div>
                    </div>

                    <div class="nem-profile-field-group">
                        <label class="nem-field-label" for="nemFieldCountry">Quốc gia:</label>
                        <div class="nem-field-box">
                            <input type="text" class="nem-field-input" id="nemFieldCountry" value="<?php echo esc_attr( $country_name ); ?>" readonly />
                        </div>
                    </div>

                    <div class="nem-profile-field-group">
                        <label class="nem-field-label" for="nemFieldPhone">Số điện thoại:</label>
                        <div class="nem-field-box">
                            <input type="text" class="nem-field-input" id="nemFieldPhone" value="<?php echo esc_attr( $phone ); ?>" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Quản lý đơn hàng (Image 2) -->
            <div class="nem-tab-panel <?php echo ( 'orders' === $active_tab ) ? 'is-active' : ''; ?>" id="nemPanelOrders">
                <div class="nem-orders-wrap">
                    <div class="nem-orders-empty">customer.orders.none</div>
                </div>
            </div>

            <!-- TAB 3: Thông tin giao hàng (Image 3) -->
            <div class="nem-tab-panel <?php echo ( 'address' === $active_tab ) ? 'is-active' : ''; ?>" id="nemPanelAddress">
                <div class="nem-address-card-wrap">
                    <div id="nemAddressDetails"<?php echo $has_address ? '' : ' hidden'; ?>>
                    <div class="nem-address-card-header">
                        <div class="nem-address-name-wrap">
                            <strong class="nem-address-name" id="nemAddrDisplayName"><?php echo esc_html( mb_strtoupper( $addr_name, 'UTF-8' ) ); ?></strong>
                            <span class="nem-address-default-badge">(Địa chỉ mặc định)</span>
                        </div>
                        <div class="nem-address-actions">
                            <a href="javascript:void(0)" class="nem-addr-action-btn" id="nemBtnEditAddr">Sửa</a>
                            <span class="nem-addr-sep">|</span>
                            <a href="javascript:void(0)" class="nem-addr-action-btn" id="nemBtnDeleteAddr">Xóa</a>
                        </div>
                    </div>
                    <div class="nem-address-row" id="nemAddrRowAddress">
                        Địa chỉ: <span id="nemAddrValAddress"><?php echo esc_html( $address_line ); ?></span>
                    </div>
                    <div class="nem-address-row" id="nemAddrRowPhone">
                        Điện thoại: <span id="nemAddrValPhone"><?php echo esc_html( $phone ); ?></span>
                    </div>
                    </div>
                    <p id="nemAddressEmpty"<?php echo $has_address ? ' hidden' : ''; ?>>Bạn chưa có thông tin giao hàng.</p>
                    <p id="nemAddressStatus" role="status" aria-live="polite"></p>
                    <div class="nem-address-divider"></div>
                    <button type="button" class="nem-btn-add-address" id="nemBtnAddNewAddr">THÊM MỚI</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa / Thêm Địa Chỉ -->
<div class="nem-modal-overlay" id="nemAddressModal" aria-hidden="true">
    <div class="nem-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="nemModalTitle">
        <button type="button" class="nem-modal-close-btn" id="nemModalCloseBtn" aria-label="Đóng">&times;</button>
        <h3 class="nem-modal-title" id="nemModalTitle">CHỈNH SỬA ĐỊA CHỈ</h3>
        <form id="nemAddressForm">
            <p id="nemAddressFormStatus" role="alert"></p>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputName">Họ và tên</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputName" value="<?php echo esc_attr( $fullname ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputPhone">Số điện thoại</label>
                <input type="tel" class="nem-modal-input" maxlength="10" inputmode="numeric" pattern="[0-9]{1,10}" title="Chỉ nhập chữ số, tối đa 10 ký tự" id="nemModalInputPhone" value="<?php echo esc_attr( $phone ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputCountry">Quốc gia</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputCountry" value="<?php echo esc_attr( $country_name ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputAddress">Địa chỉ chi tiết</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputAddress" value="<?php echo esc_attr( $address_line ); ?>" required />
            </div>
            <div class="nem-modal-actions">
                <button type="submit" class="nem-modal-btn-submit" id="nemModalBtnSubmit">LƯU THAY ĐỔI</button>
                <button type="button" class="nem-modal-btn-cancel" id="nemModalBtnCancel">HỦY</button>
            </div>
        </form>
    </div>
</div>

<!-- ĐĂNG KÝ BẢN TIN SECTION -->
<section class="nem-newsletter-section">
    <div class="nem-newsletter-container">
        <h2 class="nem-newsletter-heading">ĐĂNG KÝ BẢN TIN</h2>
        <p class="nem-newsletter-desc">Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất</p>

        <form class="nem-newsletter-form" action="#" method="post" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký nhận bản tin NEM!');">
            <input type="email" maxlength="250" class="nem-newsletter-input" name="email" value="<?php echo esc_attr( $user_email ); ?>" placeholder="Vui lòng nhập email..." required />
            <button type="submit" class="nem-newsletter-submit">ĐĂNG KÝ</button>
        </form>

        <div class="nem-newsletter-social-icons">
            <!-- Facebook -->
            <a href="https://www.facebook.com/nemfashion.official" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-facebook" title="Facebook">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>
            <!-- Instagram -->
            <a href="https://www.instagram.com/nemfashion.official/" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-instagram" title="Instagram">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>
            <!-- YouTube -->
            <a href="https://www.youtube.com/channel/UCrDQ6euiL9CekivZmD9-MCA" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-youtube" title="YouTube">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </a>
        </div>
    </div>
</section>
