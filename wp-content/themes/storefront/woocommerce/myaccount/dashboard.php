<?php
/**
 * My Account Dashboard override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// Fullname
$fullname = ! empty( $current_user->display_name ) ? $current_user->display_name : 'Nguyễn Giang';

// Country
$billing_country = get_user_meta( $user_id, 'billing_country', true );
if ( empty( $billing_country ) || $billing_country === 'VN' ) {
    $country_name = 'Vietnam';
} else {
    $country_name = function_exists( 'WC' ) && isset( WC()->countries->countries[ $billing_country ] ) 
        ? WC()->countries->countries[ $billing_country ] 
        : 'Vietnam';
}

// Phone number
$phone = get_user_meta( $user_id, 'billing_phone', true );
if ( empty( $phone ) ) {
    $phone = '0215487963';
}
?>

<div class="nem-profile-dashboard">
    <div class="nem-profile-field-group">
        <label class="nem-field-label" for="nem_profile_fullname">Họ tên</label>
        <div class="nem-field-box" id="nem_profile_fullname">
            <input type="text" class="nem-field-input" value="<?php echo esc_attr( $fullname ); ?>" readonly />
        </div>
    </div>

    <div class="nem-profile-field-group">
        <label class="nem-field-label" for="nem_profile_country">Quốc gia:</label>
        <div class="nem-field-box" id="nem_profile_country">
            <input type="text" class="nem-field-input" value="<?php echo esc_attr( $country_name ); ?>" readonly />
        </div>
    </div>

    <div class="nem-profile-field-group">
        <label class="nem-field-label" for="nem_profile_phone">Số điện thoại:</label>
        <div class="nem-field-box" id="nem_profile_phone">
            <input type="text" class="nem-field-input" value="<?php echo esc_attr( $phone ); ?>" readonly />
        </div>
    </div>

    <div class="nem-profile-edit-actions">
        <a href="<?php echo esc_url( wc_customer_edit_account_url() ); ?>" class="nem-btn-edit-account">
            Chỉnh sửa thông tin
        </a>
    </div>
</div>
