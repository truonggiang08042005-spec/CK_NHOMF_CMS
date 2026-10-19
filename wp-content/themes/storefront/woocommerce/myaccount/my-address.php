<?php
/**
 * My Addresses template override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// Fullname
$first_name = get_user_meta( $user_id, 'billing_first_name', true );
if ( empty( $first_name ) ) {
    $first_name = get_user_meta( $user_id, 'first_name', true );
}
$last_name = get_user_meta( $user_id, 'billing_last_name', true );
if ( empty( $last_name ) ) {
    $last_name = get_user_meta( $user_id, 'last_name', true );
}

$fullname = trim( $last_name . ' ' . $first_name );
if ( empty( $fullname ) ) {
    $fullname = ! empty( $current_user->display_name ) ? $current_user->display_name : 'Nguyễn Giang';
}

$first_last = trim( $first_name . ' ' . $last_name );
$addr_name  = ! empty( $first_last ) ? $first_last : $fullname;

// Phone
$phone = get_user_meta( $user_id, 'billing_phone', true );
if ( empty( $phone ) ) {
    $phone = '0215487963';
}

// Address
$address_line = get_user_meta( $user_id, 'billing_address_1', true );
if ( empty( $address_line ) ) {
    $address_line = 'Vietnam';
}
?>

<div class="nem-address-card-wrap">
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
    <div class="nem-address-divider"></div>
    <button type="button" class="nem-btn-add-address" id="nemBtnAddNewAddr">THÊM MỚI</button>
</div>
