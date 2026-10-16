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
        <div class="nem-field-box">
            <input type="text" id="nem_profile_fullname" class="nem-field-input" value="<?php echo esc_attr( $fullname ); ?>" readonly />
        </div>
    </div>

    <div class="nem-profile-field-group">
        <label class="nem-field-label" for="nem_profile_country">Quốc gia:</label>
        <div class="nem-field-box">
            <input type="text" id="nem_profile_country" class="nem-field-input" value="<?php echo esc_attr( $country_name ); ?>" readonly />
        </div>
    </div>

    <div class="nem-profile-field-group">
        <label class="nem-field-label" for="nem_profile_phone">Số điện thoại:</label>
        <div class="nem-field-box">
            <input type="text" id="nem_profile_phone" class="nem-field-input" value="<?php echo esc_attr( $phone ); ?>" readonly />
        </div>
    </div>
</div>
