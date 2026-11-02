<?php
/** Native account blocks: saved layout, live customer data and WooCommerce forms. */
defined( 'ABSPATH' ) || exit;

function nem_account_design_post( $id = 0 ) {
	$id = $id ?: (int) get_option( 'woocommerce_myaccount_page_id' );
	$page = get_post( $id );
	return $page instanceof WP_Post && 'page' === $page->post_type ? $page : null;
}

function nem_account_uses_editor() {
	if ( is_admin() ) return false;
	$page = get_queried_object();
	return $page instanceof WP_Post && $page->ID === (int) get_option( 'woocommerce_myaccount_page_id' ) && str_contains( $page->post_content, 'nem-account-design' );
}

function nem_account_editor_screen() {
	$id = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	return is_admin() && $id && $id === (int) get_option( 'woocommerce_myaccount_page_id' );
}

function nem_account_block_class( $block, $class ) {
	return in_array( $class, preg_split( '/\s+/', $block['attrs']['className'] ?? '' ), true );
}

function nem_account_customer_data() {
	$user = wp_get_current_user();
	$country = get_user_meta( $user->ID, 'billing_country', true );
	$countries = function_exists( 'WC' ) && WC()->countries ? WC()->countries->countries : [];
	return [
		'fullname' => nem_customer_display_name(),
		'country' => ! $country || 'VN' === $country ? 'Vietnam' : ( $countries[ $country ] ?? $country ),
		'phone' => get_user_meta( $user->ID, 'billing_phone', true ),
		'address' => get_user_meta( $user->ID, 'billing_address_1', true ),
		'email' => $user->user_email,
	];
}

/** IDs and names remain stable when an editor moves or renames a field. */
function nem_account_input_fields() {
	return [
		'login-email' => [ 'username', 'username', 'text', 'username' ],
		'login-password' => [ 'password', 'password', 'password', 'current-password' ],
		'register-last-name' => [ 'nem_last_name', 'reg_last_name', 'text', 'family-name' ],
		'register-first-name' => [ 'nem_first_name', 'reg_first_name', 'text', 'given-name' ],
		'register-email' => [ 'email', 'reg_email', 'email', 'email' ],
		'register-phone' => [ 'nem_phone', 'reg_phone', 'tel', 'tel' ],
		'register-password' => [ 'password', 'reg_password', 'password', 'new-password' ],
	];
}

function nem_account_capture_action( $name ) {
	ob_start();
	do_action( $name );
	return ob_get_clean();
}

/** Endpoints keep the saved newsletter without repeating the account panels. */
function nem_account_endpoint_newsletter( $blocks = null ) {
	if ( null === $blocks ) {
		$page = nem_account_design_post();
		$blocks = $page ? parse_blocks( $page->post_content ) : [];
	}
	foreach ( $blocks as $block ) {
		if ( nem_account_block_class( $block, 'nem-newsletter-section' ) ) return render_block( $block );
		$newsletter = nem_account_endpoint_newsletter( $block['innerBlocks'] ?? [] );
		if ( '' !== $newsletter ) return $newsletter;
	}
	return '';
}

// Skip the other audience before rendering children (including plugin form hooks).
add_filter( 'pre_render_block', function ( $pre, $block ) {
	if ( ! nem_account_uses_editor() ) return $pre;
	if ( nem_account_block_class( $block, is_user_logged_in() ? 'nem-account-guests' : 'nem-account-members' ) ) return '';
	return $pre;
}, 10, 2 );

add_filter( 'render_block', function ( $html, $block ) {
	if ( ! nem_account_uses_editor() ) return $html;
	$has = static fn( $class ) => nem_account_block_class( $block, $class );
	$text = trim( wp_strip_all_tags( $html ) );
	$customer = nem_account_customer_data();
	$tags = new WP_HTML_Tag_Processor( $html );
	$tags->next_tag();
	foreach ( [ 'fullname' => 'nemFieldFullname', 'country' => 'nemFieldCountry', 'phone' => 'nemFieldPhone' ] as $key => $id ) {
		if ( $has( 'nem-account-value-' . $key ) ) return '<div class="nem-field-box"><input class="nem-field-input" id="' . esc_attr( $id ) . '" aria-label="' . esc_attr( $text ) . '" value="' . esc_attr( $customer[ $key ] ) . '" readonly /></div>';
	}
	if ( $has( 'nem-account-customer-name' ) || $has( 'nem-account-address-name' ) ) {
		$value = esc_html( mb_strtoupper( $customer['fullname'], 'UTF-8' ) );
		$html = preg_replace_callback( '/(?<=>)[\s\S]*(?=<\/[^>]+>\s*$)/', static fn() => $value, $html, 1 );
	}
	foreach ( [ 'address', 'phone' ] as $key ) {
		if ( $has( 'nem-account-address-' . $key ) ) $html = preg_replace_callback( '/(?<=>)[\s\S]*(?=<\/[^>]+>\s*$)/', static fn() => esc_html( $customer[ $key ] ), $html, 1 );
	}
	if ( $has( 'nem-account-address-details' ) && '' === $customer['address'] && '' === $customer['phone'] ) $tags->set_attribute( 'hidden', true );
	if ( $has( 'nem-account-address-empty' ) && ( '' !== $customer['address'] || '' !== $customer['phone'] ) ) $tags->set_attribute( 'hidden', true );
	if ( $has( 'nem-account-address-details' ) || $has( 'nem-account-address-empty' ) ) $html = $tags->get_updated_html();
	foreach ( [ 'account', 'orders', 'address' ] as $tab ) {
		if ( $has( 'nem-account-tab-' . $tab ) ) {
			$tags->set_attribute( 'data-target', $tab );
			if ( 'account' === $tab ) $tags->add_class( 'is-active' );
			if ( $tags->next_tag( 'A' ) ) {
				$tags->set_attribute( 'data-tab', $tab );
				if ( 'account' === $tab ) $tags->set_attribute( 'aria-current', 'true' );
			}
			$html = $tags->get_updated_html();
		}
	}
	if ( $has( 'nem-account-orders-list' ) && function_exists( 'wc_get_orders' ) && wc_get_orders( [ 'customer' => get_current_user_id(), 'limit' => 1, 'return' => 'ids' ] ) ) {
		ob_start();
		woocommerce_account_orders( 1 );
		$html = '<div class="nem-orders-wrap">' . ob_get_clean() . '</div>';
	}
	foreach ( nem_account_input_fields() as $key => [ $name, $id, $type, $autocomplete ] ) {
		if ( $has( 'nem-account-label-' . $key ) ) return '<label for="' . esc_attr( $id ) . '">' . esc_html( $text ) . '</label>';
		if ( ! $has( 'nem-account-input-' . $key ) ) continue;
		$value = 'password' !== $type && isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
		$phone = 'tel' === $type ? ' inputmode="numeric" pattern="[0-9]{1,10}"' : '';
		return '<input class="input-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" autocomplete="' . esc_attr( $autocomplete ) . '" maxlength="' . ( 'tel' === $type ? '10' : '250' ) . '" placeholder="' . esc_attr( $text ) . '" value="' . esc_attr( $value ) . '" required' . $phone . ' />';
	}
	foreach ( [ 'newsletter', 'terms' ] as $key ) {
		if ( ! $has( 'nem-account-check-' . $key ) ) continue;
		$label = preg_replace( '/^<p\b[^>]*>|<\/p>\s*$/', '', trim( $html ) );
		return '<label class="nem-auth__check" for="reg_' . $key . '"><input type="checkbox" id="reg_' . $key . '" name="nem_' . $key . '" value="1"' . checked( ! empty( $_POST[ 'nem_' . $key ] ), true, false ) . ( 'terms' === $key ? ' required' : '' ) . ' /><span>' . wp_kses_post( $label ) . '</span></label>';
	}
	foreach ( [ 'login', 'register' ] as $kind ) {
		if ( $has( 'nem-account-submit-' . $kind ) ) return '<button type="submit" class="nem-auth__btn woocommerce-form-' . $kind . '__submit" id="nem-' . $kind . '-submit" name="' . $kind . '" value="1">' . esc_html( $text ) . '</button>';
		if ( ! $has( 'nem-account-form-' . $kind ) ) continue;
		$nonce = wp_nonce_field( 'woocommerce-' . $kind, 'woocommerce-' . $kind . '-nonce', true, false );
		$remember = 'login' === $kind ? '<input type="hidden" name="rememberme" value="forever" />' : '';
		$hooks = nem_account_capture_action( 'woocommerce_' . $kind . '_form' );
		// Keep extension fields before the submit button just as in the WooCommerce template.
		$html = preg_replace_callback( '/(?=<button\b)/', static fn() => $hooks . $nonce . $remember, $html, 1 );
		$html = '<form method="post" class="woocommerce-form woocommerce-form-' . $kind . ' nem-auth__form"' . ( 'register' === $kind ? nem_account_capture_action( 'woocommerce_register_form_tag' ) : '' ) . '>' . nem_account_capture_action( 'woocommerce_' . $kind . '_form_start' ) . $html . nem_account_capture_action( 'woocommerce_' . $kind . '_form_end' ) . '</form>';
	}
	if ( $has( 'nem-account-guests' ) ) $html = nem_account_capture_action( 'woocommerce_before_customer_login_form' ) . $html . nem_account_capture_action( 'woocommerce_after_customer_login_form' );
	if ( $has( 'nem-account-address-status' ) ) {
		$tags->set_attribute( 'role', 'status' );
		$tags->set_attribute( 'aria-live', 'polite' );
		$html = $tags->get_updated_html();
	}
	foreach ( [ 'edit' => 'nemBtnEditAddr', 'delete' => 'nemBtnDeleteAddr', 'add' => 'nemBtnAddNewAddr' ] as $action => $id ) {
		if ( $has( 'nem-account-action-' . $action ) ) return '<button type="button" class="' . ( 'add' === $action ? 'nem-btn-add-address' : 'nem-addr-action-btn' ) . '" id="' . $id . '">' . esc_html( $text ) . '</button>';
	}
	if ( $has( 'nem-account-newsletter-email' ) ) return '<label class="screen-reader-text" for="nem-account-newsletter-email">Email</label><input id="nem-account-newsletter-email" class="nem-newsletter-input" type="email" name="nem_newsletter_email" maxlength="250" value="' . esc_attr( $customer['email'] ) . '" placeholder="' . esc_attr( $text ) . '" required />';
	if ( $has( 'nem-account-newsletter-submit' ) ) return '<button type="submit" class="nem-newsletter-submit">' . esc_html( $text ) . '</button>';
	if ( $has( 'nem-account-newsletter-form' ) ) {
		$html = '<form class="nem-account-newsletter-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="nem_newsletter_subscribe" />' . wp_nonce_field( 'nem_newsletter', 'nem_newsletter_nonce', true, false ) . $html . '</form>';
		$messages = [ 'ok' => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!', 'exists' => 'Email này đã đăng ký nhận bản tin.', 'invalid' => 'Vui lòng nhập email hợp lệ.', 'error' => 'Vui lòng thử đăng ký lại.' ];
		$status = isset( $_GET['nem_newsletter'] ) && is_string( $_GET['nem_newsletter'] ) ? sanitize_key( $_GET['nem_newsletter'] ) : '';
		if ( isset( $messages[ $status ] ) ) $html .= '<p role="status">' . esc_html( $messages[ $status ] ) . '</p>';
	}
	return $html;
}, 25, 2 );

function nem_account_enqueue_design() {
	if ( ! nem_account_uses_editor() && ! nem_account_editor_screen() ) return;
	$dir = get_template_directory();
	wp_enqueue_style( 'nem-account-block-design', get_template_directory_uri() . '/assets/css/nem-account-blocks.css', is_admin() ? [ 'nem-editor-account' ] : [ 'nem-header-footer-style', 'nem-account' ], filemtime( $dir . '/assets/css/nem-account-blocks.css' ) );
	if ( is_admin() ) {
		wp_dequeue_style( 'nem-editor-sale' );
		wp_dequeue_script( 'nem-editor-sale-js' );
	} else {
		wp_enqueue_style( 'nem-account', get_template_directory_uri() . '/assets/css/nem-account.css', [ 'nem-header-footer-style' ], filemtime( $dir . '/assets/css/nem-account.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'nem_account_enqueue_design', 200 );
add_action( 'enqueue_block_assets', 'nem_account_enqueue_design', 200 );
add_action( 'enqueue_block_editor_assets', 'nem_account_enqueue_design', 200 );
add_action( 'enqueue_block_editor_assets', function () {
	if ( ! nem_account_editor_screen() ) return;
	wp_enqueue_script( 'nem-account-editor', get_template_directory_uri() . '/assets/js/nem-account-editor.js', [ 'wp-plugins', 'wp-editor', 'wp-components', 'wp-element' ], filemtime( __DIR__ . '/../assets/js/nem-account-editor.js' ), true );
}, 201 );
add_action( 'after_setup_theme', function () {
	if ( ! nem_account_editor_screen() ) return;
	global $editor_styles;
	$editor_styles = array_values( array_filter( (array) $editor_styles, static fn( $style ) => ! str_contains( $style, 'nem-sale-editor.css' ) ) );
	add_editor_style( 'assets/css/nem-account-blocks.css' );
}, 100 );
add_filter( 'body_class', function ( $classes ) {
	if ( nem_account_uses_editor() ) $classes[] = 'nem-account-block-page';
	return $classes;
} );
add_action( 'wp', function () {
	if ( ! nem_account_uses_editor() ) return;
	remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
	remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
} );
add_filter( 'template_include', function ( $template ) {
	return nem_account_uses_editor() ? get_template_directory() . '/page-my-account.php' : $template;
}, 110 );
