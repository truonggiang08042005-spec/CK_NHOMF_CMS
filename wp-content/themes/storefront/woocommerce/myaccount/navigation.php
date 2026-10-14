<?php
/**
 * My Account navigation for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$myaccount_url = wc_get_page_permalink( 'myaccount' );
$orders_url    = wc_get_account_endpoint_url( 'orders' );
$address_url   = wc_get_account_endpoint_url( 'edit-address' );
$logout_url    = wp_logout_url( home_url( '/' ) );

// Determine active tab
$current_endpoint = WC()->query->get_current_endpoint();
$is_dashboard     = empty( $current_endpoint );
$is_orders        = ( 'orders' === $current_endpoint || 'view-order' === $current_endpoint );
$is_address       = ( 'edit-address' === $current_endpoint );
?>

<nav class="nem-account-nav">
    <ul class="nem-account-nav-list">
        <li class="nem-account-nav-item <?php echo $is_dashboard ? 'is-active' : ''; ?>">
            <a href="<?php echo esc_url( $myaccount_url ); ?>" class="nem-account-nav-link">Thông tin tài khoản</a>
        </li>
        <li class="nem-account-nav-item <?php echo $is_orders ? 'is-active' : ''; ?>">
            <a href="<?php echo esc_url( $orders_url ); ?>" class="nem-account-nav-link">Quản lý đơn hàng</a>
        </li>
        <li class="nem-account-nav-item <?php echo $is_address ? 'is-active' : ''; ?>">
            <a href="<?php echo esc_url( $address_url ); ?>" class="nem-account-nav-link">Thông tin giao hàng</a>
        </li>
        <li class="nem-account-nav-item nem-account-nav-logout">
            <a href="<?php echo esc_url( $logout_url ); ?>" class="nem-account-nav-link">Đăng xuất</a>
        </li>
    </ul>
</nav>
