<?php
/** Integration tests use isolated customers and remove them when finished. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$checks = 0;
function nem_account_design_assert( $condition, $message ) {
	global $checks;
	if ( ! $condition ) throw new RuntimeException( $message );
	++$checks;
}
function nem_account_test_dom( $html ) {
	$dom = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return new DOMXPath( $dom );
}
$design_page = nem_account_design_post();
nem_account_design_assert( $design_page && str_contains( $design_page->post_content, 'nem-account-design' ), 'The configured WooCommerce page has the editable design.' );
$original = $design_page->post_content;
$blocks = parse_blocks( $original );
$inspect = function ( $items ) use ( &$inspect ) {
	foreach ( $items as $block ) {
		if ( null === $block['blockName'] ) continue;
		nem_account_design_assert( in_array( $block['blockName'], [ 'core/group', 'core/columns', 'core/column', 'core/paragraph', 'core/heading', 'core/image' ], true ), 'The account design uses editable native blocks, not opaque HTML or shortcodes.' );
		$inspect( $block['innerBlocks'] ?? [] );
	}
};
$inspect( $blocks );
global $wp_query, $post;
$wp_query = new WP_Query( [ 'page_id' => $design_page->ID ] );
$post = clone $design_page;
$wp_query->queried_object = $post;
$wp_query->queried_object_id = $post->ID;
$wp_query->in_the_loop = true;
setup_postdata( $post );
$render = static fn() => apply_filters( 'the_content', $post->post_content );
$_POST = $_GET = [];
wp_set_current_user( 0 );
$guest = $render();
$dom = nem_account_test_dom( $guest );
nem_account_design_assert( ! str_contains( $guest, 'nem-account-members' ), 'Guests never receive private account panels.' );
foreach ( [ 'login', 'register' ] as $kind ) {
	$form = $dom->query( '//form[contains(@class,"woocommerce-form-' . $kind . '")]' );
	nem_account_design_assert( 1 === $form->length, 'One real ' . $kind . ' form.' );
	$nonce = $dom->query( './/input[@name="woocommerce-' . $kind . '-nonce"]', $form->item( 0 ) );
	nem_account_design_assert( 1 === $nonce->length && wp_verify_nonce( $nonce->item( 0 )->getAttribute( 'value' ), 'woocommerce-' . $kind ), 'A valid WooCommerce nonce is inside the correct form.' );
}
foreach ( nem_account_input_fields() as [ $name, $id, $type ] ) {
	$input = $dom->query( '//input[@id="' . $id . '"]' );
	nem_account_design_assert( 1 === $input->length && $input->item( 0 )->getAttribute( 'name' ) === $name && $input->item( 0 )->getAttribute( 'type' ) === $type, 'The live field retains its WooCommerce identity: ' . $id );
	nem_account_design_assert( 1 === $dom->query( '//label[@for="' . $id . '"]' )->length, 'A matching label exists for ' . $id );
}
nem_account_design_assert( 1 === $dom->query( '//input[@id="reg_terms" and @required]' )->length, 'Terms are required independently of JavaScript.' );
nem_account_design_assert( 0 === $dom->query( '//form//form' )->length, 'There are no nested forms.' );
nem_account_design_assert( 1 === $dom->query( '//input[@name="nem_newsletter_nonce"]' )->length && 1 === $dom->query( '//input[@name="nem_newsletter_email"]' )->length, 'One functioning newsletter form is rendered.' );
$endpoint_newsletter = nem_account_endpoint_newsletter();
nem_account_design_assert( str_contains( $endpoint_newsletter, 'nem_newsletter_nonce' ) && ! str_contains( $endpoint_newsletter, 'nem-account-guests' ), 'WooCommerce endpoints keep only the saved newsletter.' );
$_POST = [ 'username' => '"><script>alert(1)</script>', 'password' => 'never-print-this-password' ];
$submitted = $render();
nem_account_design_assert( ! str_contains( $submitted, 'never-print-this-password' ) && ! str_contains( $submitted, '<script>alert(1)</script>' ), 'Posted credentials are escaped and passwords are never repopulated.' );
$_POST = [];

$ids = [];
$order = null;
try {
	foreach ( [ 'Khách Thứ Nhất', 'Khách Thứ Hai' ] as $name ) {
		$id = wp_insert_user( [ 'user_login' => 'nem_design_test_' . wp_generate_password( 12, false ), 'user_pass' => wp_generate_password( 32 ), 'user_email' => 'nem-design-' . wp_generate_password( 12, false ) . '@example.invalid', 'role' => 'customer', 'display_name' => $name ] );
		if ( is_wp_error( $id ) ) throw new RuntimeException( $id->get_error_message() );
		$ids[] = $id;
	}
	update_user_meta( $ids[0], 'billing_phone', '0123456789' );
	update_user_meta( $ids[0], 'billing_country', 'VN' );
	update_user_meta( $ids[0], 'billing_address_1', '123 Đường Test $1 <script>alert(1)</script>' );
	wp_set_current_user( $ids[0] );
	$member = $render();
	$dom = nem_account_test_dom( $member );
	nem_account_design_assert( ! str_contains( $member, 'nem-account-guests' ) && ! str_contains( $member, 'woocommerce-form-login' ), 'Members receive account panels and no guest form.' );
	nem_account_design_assert( str_contains( $member, 'KHÁCH THỨ NHẤT' ) && str_contains( $member, '0123456789' ), 'The greeting and phone use the current customer.' );
	nem_account_design_assert( str_contains( $member, '123 Đường Test $1 &lt;script&gt;' ) && ! str_contains( $member, '<script>alert(1)</script>' ), 'Live addresses preserve dollar signs and escape HTML.' );
	nem_account_design_assert( 3 === $dom->query( '//a[@data-tab]' )->length && 3 === $dom->query( '//div[starts-with(@id,"nemPanel")]' )->length, 'All three navigation targets and panels remain interactive.' );
	nem_account_design_assert( 1 === $dom->query( '//*[@id="nemAddressEmpty" and @hidden]' )->length && 0 === $dom->query( '//*[@id="nemAddressDetails" and @hidden]' )->length, 'Saved address uses the correct visible state.' );
	foreach ( [ 'nemBtnEditAddr', 'nemBtnDeleteAddr', 'nemBtnAddNewAddr' ] as $id ) nem_account_design_assert( 1 === $dom->query( '//button[@id="' . $id . '"]' )->length, 'Delivery action remains a real button: ' . $id );
	ob_start();
	get_template_part( 'template-parts/account-address-modal' );
	$modal = ob_get_clean();
	nem_account_design_assert( str_contains( $modal, 'id="nemAddressForm"' ) && str_contains( $modal, '0123456789' ), 'The delivery modal retains real customer data and existing JavaScript IDs.' );
	wp_set_current_user( $ids[1] );
	$second = $render();
	nem_account_design_assert( str_contains( $second, 'KHÁCH THỨ HAI' ) && ! str_contains( $second, '0123456789' ) && ! str_contains( $second, '123 Đường Test' ), 'A second customer cannot see the first customer’s data.' );
	nem_account_design_assert( 1 === nem_account_test_dom( $second )->query( '//*[@id="nemAddressDetails" and @hidden]' )->length, 'A new customer sees the empty delivery state.' );

	// Drag rows, navigation items, fields and columns in memory, as Gutenberg does.
	$move = function ( $items ) use ( &$move ) {
		foreach ( $items as &$block ) {
			if ( nem_account_block_class( $block, 'nem-profile-grid' ) || nem_account_block_class( $block, 'nem-profile-dashboard' ) || nem_account_block_class( $block, 'nem-account-nav' ) || nem_account_block_class( $block, 'nem-auth' ) || nem_account_block_class( $block, 'nem-account-form-register' ) ) $block['innerBlocks'] = array_reverse( $block['innerBlocks'] );
			$block['innerBlocks'] = $move( $block['innerBlocks'] ?? [] );
		}
		unset( $block );
		return $items;
	};
	$post->post_content = serialize_blocks( $move( $blocks ) );
	$post->post_content = str_replace( [ 'Xin chào,', 'Thông tin tài khoản', '>Họ tên<', '>ĐĂNG KÝ<' ], [ 'Chào mừng bạn,', 'Hồ sơ của tôi', '>Tên của bạn<', '>NHẬN BẢN TIN<' ], $post->post_content );
	$edited = $render();
	nem_account_design_assert( strpos( $edited, 'nem-profile-content-col' ) < strpos( $edited, 'nem-profile-sidebar-col' ), 'Dragging the sidebar changes the frontend column order.' );
	nem_account_design_assert( strpos( $edited, 'id="nemFieldPhone"' ) < strpos( $edited, 'id="nemFieldFullname"' ), 'Dragging profile fields changes the frontend order.' );
	nem_account_design_assert( str_contains( $edited, 'Chào mừng bạn,' ) && str_contains( $edited, 'Hồ sơ của tôi' ) && str_contains( $edited, 'Tên của bạn' ), 'Edited greetings, navigation and field labels reach the frontend.' );
	nem_account_design_assert( str_contains( $edited, '<button type="submit" class="nem-newsletter-submit">NHẬN BẢN TIN</button>' ), 'The newsletter keeps the edited submit caption.' );
	wp_set_current_user( 0 );
	$guest_edited = $render();
	nem_account_design_assert( strpos( $guest_edited, 'nem-auth__col--register' ) < strpos( $guest_edited, 'nem-auth__col--login' ), 'Guest columns follow the saved order.' );
	nem_account_design_assert( str_contains( $guest_edited, 'name="nem_phone" id="reg_phone"' ) && str_contains( $guest_edited, 'woocommerce-register-nonce' ), 'Reordering registration fields preserves field identities and nonce.' );
	$_GET = [ 'nem_newsletter' => 'ok' ];
	$feedback = $render();
	nem_account_design_assert( str_contains( $feedback, '<p role="status">Cảm ơn' ), 'Newsletter feedback is displayed after redirect.' );
	$_GET = [];
	$post->post_content = $original;
	wp_set_current_user( $ids[0] );
	$order = wc_create_order( [ 'customer_id' => $ids[0], 'status' => 'pending' ] );
	if ( is_wp_error( $order ) ) throw new RuntimeException( $order->get_error_message() );
	$order->set_total( 123000 );
	$order->save();
	$with_order = $render();
	nem_account_design_assert( str_contains( $with_order, esc_url( $order->get_view_order_url() ) ), 'The orders panel renders the customer’s real WooCommerce order.' );
	wp_set_current_user( $ids[1] );
	nem_account_design_assert( ! str_contains( $render(), esc_url( $order->get_view_order_url() ) ), 'Another customer cannot see that order.' );
	$wp_query = new WP_Query( [ 'pagename' => 'san-pham-moi' ] );
	nem_account_design_assert( ! nem_account_uses_editor(), 'Account transformations do not run on other pages.' );
	nem_account_design_assert( get_post( $design_page->ID )->post_content === $original, 'All edit simulations leave the saved page unchanged.' );
} finally {
	wp_set_current_user( 0 );
	if ( $order instanceof WC_Order ) $order->delete( true );
	foreach ( $ids as $id ) wp_delete_user( $id );
}
echo 'PASS: ' . $checks . ' account editor checks; temporary customers and order removed.' . PHP_EOL;
