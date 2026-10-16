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

// Determine active tab
$current_endpoint = function_exists( 'WC' ) && isset( WC()->query ) ? WC()->query->get_current_endpoint() : '';
$is_orders        = ( 'orders' === $current_endpoint || 'view-order' === $current_endpoint );
$is_address       = ( 'edit-address' === $current_endpoint );
$is_account       = empty( $current_endpoint );
?>

<nav class="nem-account-nav">
    <ul class="nem-account-nav-list">
        <li class="nem-account-nav-item <?php echo $is_account ? 'is-active' : ''; ?>" data-target="account">
            <a href="<?php echo esc_url( $myaccount_url ); ?>#account" data-tab="account" class="nem-account-nav-link">Thông tin tài khoản</a>
        </li>
        <li class="nem-account-nav-item <?php echo $is_orders ? 'is-active' : ''; ?>" data-target="orders">
            <a href="<?php echo esc_url( $orders_url ); ?>#orders" data-tab="orders" class="nem-account-nav-link">Quản lý đơn hàng</a>
        </li>
        <li class="nem-account-nav-item <?php echo $is_address ? 'is-active' : ''; ?>" data-target="address">
            <a href="<?php echo esc_url( $address_url ); ?>#address" data-tab="address" class="nem-account-nav-link">Thông tin giao hàng</a>
        </li>
    </ul>
</nav>
