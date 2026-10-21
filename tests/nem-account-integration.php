<?php
/** Run from the project root: php tests/nem-account-integration.php */
if ( PHP_SAPI !== 'cli' ) {
    exit;
}
define( 'DOING_AJAX', true );
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

class Nem_Test_Json_End extends RuntimeException {}
add_filter( 'wp_die_ajax_handler', function () {
    return function () { throw new Nem_Test_Json_End(); };
} );

$checks = 0;
function nem_test_assert( $condition, $message ) {
    global $checks;
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
    ++$checks;
}
function nem_test_request( $action, $data, $nonce = null ) {
    $_POST = $data;
    $_POST['nonce'] = null === $nonce ? wp_create_nonce( 'nem_profile_address' ) : $nonce;
    $_REQUEST = $_POST;
    ob_start();
    try {
        do_action( 'wp_ajax_' . $action );
    } catch ( Nem_Test_Json_End $end ) {
        return json_decode( ob_get_clean(), true );
    }
    ob_end_clean();
    throw new RuntimeException( 'AJAX handler did not return JSON.' );
}

$user_id = 0;
try {
    $user_id = wp_insert_user( array(
        'user_login' => 'nem_test_' . wp_generate_password( 12, false ),
        'user_pass'  => wp_generate_password( 32 ),
        'role'       => 'customer',
    ) );
    nem_test_assert( ! is_wp_error( $user_id ), 'Create isolated customer' );
    wp_set_current_user( $user_id );
    $valid = array( 'fullname' => 'Nguyễn Thị Ánh', 'phone' => '0123456789', 'country' => 'Việt Nam', 'address' => '123 Đường Nguyễn Trãi' );

    $result = nem_test_request( 'nem_save_profile_address', $valid );
    nem_test_assert( $result['success'], 'Save valid Vietnamese customer' );
    nem_test_assert( get_user_meta( $user_id, 'billing_phone', true ) === '0123456789', 'Keep leading zero and 10 digits' );
    nem_test_assert( get_user_meta( $user_id, 'billing_country', true ) === 'VN', 'Save Vietnam country code' );
    nem_test_assert( get_user_meta( $user_id, 'billing_address_1', true ) === $valid['address'], 'Persist address' );
    wp_set_current_user( 0 );
    wp_set_current_user( $user_id );
    nem_test_assert( nem_customer_display_name() === $valid['fullname'], 'Name remains in Vietnamese order after reload' );

    foreach ( array( '01234567890', '01234abc89', '+841234567', '012 345678', "0123456789\n", '', array( '123' ) ) as $phone ) {
        $result = nem_test_request( 'nem_save_profile_address', array_merge( $valid, array( 'phone' => $phone ) ) );
        nem_test_assert( ! $result['success'], 'Reject invalid phone' );
        nem_test_assert( get_user_meta( $user_id, 'billing_phone', true ) === $valid['phone'], 'Invalid request leaves data unchanged' );
    }
    foreach ( array( 'fullname', 'country', 'address' ) as $key ) {
        $result = nem_test_request( 'nem_save_profile_address', array_merge( $valid, array( $key => str_repeat( 'ế', 251 ) ) ) );
        nem_test_assert( ! $result['success'], 'Reject more than 250 Unicode characters: ' . $key );
        $result = nem_test_request( 'nem_save_profile_address', array_merge( $valid, array( $key => str_repeat( 'ế', 250 ) ) ) );
        nem_test_assert( $result['success'], 'Accept 250 Unicode characters: ' . $key );
    }
    nem_test_request( 'nem_save_profile_address', $valid );
    foreach ( array( 'nem_save_profile_address', 'nem_delete_profile_address' ) as $action ) {
        $result = nem_test_request( $action, $valid, 'bad-nonce' );
        nem_test_assert( ! $result['success'], 'Reject invalid nonce: ' . $action );
    }

    ob_start();
    get_template_part( 'template-parts/header', 'nem' );
    $header = ob_get_clean();
    nem_test_assert( strpos( $header, 'class="nem-user-name" title="' . $valid['fullname'] . '">' . $valid['fullname'] ) !== false, 'Header displays logged-in customer name' );

    $result = nem_test_request( 'nem_delete_profile_address', array() );
    nem_test_assert( $result['success'], 'Delete address' );
    nem_test_assert( '' === get_user_meta( $user_id, 'billing_address_1', true ), 'Deleted address remains absent in database' );
    nem_test_assert( '' === get_user_meta( $user_id, 'billing_phone', true ), 'Deleted phone remains absent in database' );
    wp_set_current_user( 0 );
    wp_set_current_user( $user_id );
    nem_test_assert( nem_customer_display_name() === $valid['fullname'], 'Deleting delivery information preserves customer identity' );
    ob_start();
    wc_get_template( 'myaccount/my-account.php' );
    $account = ob_get_clean();
    nem_test_assert( strpos( $account, 'id="nemAddressDetails" hidden' ) !== false, 'Reload shows empty address state' );
    nem_test_assert( strpos( $account, '0215487963' ) === false, 'No fabricated fallback phone' );
    $result = nem_test_request( 'nem_save_profile_address', $valid );
    nem_test_assert( $result['success'], 'Add address after deleting' );

    foreach ( array( 'nem_last_name', 'nem_first_name', 'email', 'password' ) as $key ) {
        $_POST = array( 'nem_last_name' => 'Nguyễn', 'nem_first_name' => 'Ánh', 'nem_phone' => '0123456789', 'email' => 'test@example.com', 'password' => 'test-password', 'nem_terms' => '1' );
        $_POST[ $key ] = str_repeat( 'a', 251 );
        $errors = new WP_Error();
        do_action( 'woocommerce_register_post', '', 'test@example.com', $errors );
        nem_test_assert( $errors->has_errors(), 'Registration rejects 251 characters: ' . $key );
    }
    wp_set_current_user( 0 );
    $result = nem_test_request( 'nem_delete_profile_address', array() );
    nem_test_assert( ! $result['success'], 'Anonymous request cannot delete data' );
    $result = nem_test_request( 'nem_save_profile_address', $valid );
    nem_test_assert( ! $result['success'], 'Anonymous request cannot save data' );
} finally {
    wp_set_current_user( 0 );
    if ( is_int( $user_id ) && $user_id > 0 ) {
        wp_delete_user( $user_id );
    }
}
echo 'PASS: ' . $checks . ' account integration checks; temporary customer removed.' . PHP_EOL;
