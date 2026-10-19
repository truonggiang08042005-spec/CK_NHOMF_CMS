<?php
/**
 * NEM - Trang Tài khoản (Đăng nhập + Đăng ký + Đăng ký bản tin)
 *
 * - Breadcrumb "TRANG CHỦ / TÀI KHOẢN" ngay dưới header.
 * - Template 2 cột: woocommerce/myaccount/form-login.php
 * - Khối "ĐĂNG KÝ BẢN TIN" phía trên footer.
 * - Validate + lưu Họ / Tên / SĐT / điều khoản khi đăng ký.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trang đăng nhập / đăng ký (trang Tài khoản khi chưa đăng nhập).
 */
function nem_is_auth_page() {
	return function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in();
}

/**
 * Bật đăng ký, cho khách tự đặt mật khẩu, tự sinh username từ email.
 */
add_filter( 'pre_option_woocommerce_enable_myaccount_registration', function () {
	return 'yes';
} );
add_filter( 'pre_option_woocommerce_registration_generate_password', function () {
	return 'no';
} );
add_filter( 'pre_option_woocommerce_registration_generate_username', function () {
	return 'yes';
} );

/**
 * Bỏ đoạn chính sách bảo mật mặc định trong form đăng ký (mẫu không có).
 */
add_action( 'init', function () {
	remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );
} );

/**
 * CSS + JS cho trang.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! nem_is_auth_page() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'nem-account',
		$uri . '/assets/css/nem-account.css',
		array( 'nem-header-footer-style' ),
		filemtime( $dir . '/assets/css/nem-account.css' )
	);
	wp_enqueue_script(
		'nem-account',
		$uri . '/assets/js/nem-account.js',
		array(),
		filemtime( $dir . '/assets/js/nem-account.js' ),
		true
	);
}, 100 );

add_filter( 'body_class', function ( $classes ) {
	if ( nem_is_auth_page() ) {
		$classes[] = 'nem-auth-page';
	}
	return $classes;
} );

/**
 * Breadcrumb full-width ngay dưới header.
 */
add_action( 'storefront_before_content', function () {
	if ( ! nem_is_auth_page() ) {
		return;
	}
	?>
	<nav class="nem-auth-breadcrumb" aria-label="Breadcrumb">
		<div class="nem-auth-breadcrumb__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
			<span class="nem-auth-breadcrumb__sep">/</span>
			<span aria-current="page">Tài khoản</span>
		</div>
	</nav>
	<?php
}, 20 );

/**
 * Khối "ĐĂNG KÝ BẢN TIN" full-width ngay trên footer.
 */
add_action( 'storefront_before_footer', function () {
	if ( nem_is_auth_page() ) {
		get_template_part( 'template-parts/newsletter', 'nem' );
	}
} );

/* ==========================================================================
   ĐĂNG KÝ BẢN TIN
   ========================================================================== */

/**
 * Thêm email vào danh sách nhận tin.
 * Ưu tiên MailPoet (đã cài sẵn), nếu không có thì lưu vào option.
 *
 * @return string ok | exists | error
 */
function nem_newsletter_add_email( $email, $first_name = '', $last_name = '' ) {
	if ( class_exists( '\MailPoet\API\API' ) ) {
		try {
			$mp = \MailPoet\API\API::MP( 'v1' );
			try {
				if ( $mp->getSubscriber( $email ) ) {
					return 'exists';
				}
			} catch ( \Exception $e ) {
				// Chưa có subscriber -> tiếp tục thêm mới.
			}
			$list_ids = wp_list_pluck( (array) $mp->getLists(), 'id' );
			$mp->addSubscriber(
				array(
					'email'      => $email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				),
				$list_ids
			);
			return 'ok';
		} catch ( \Exception $e ) {
			// Lỗi MailPoet -> dùng cách lưu dự phòng bên dưới.
		}
	}

	$emails = (array) get_option( 'nem_newsletter_emails', array() );
	if ( in_array( $email, $emails, true ) ) {
		return 'exists';
	}
	$emails[] = $email;
	update_option( 'nem_newsletter_emails', $emails, false );
	return 'ok';
}

function nem_handle_newsletter_subscribe() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$redirect = remove_query_arg( 'nem_newsletter', $redirect );

	if ( ! isset( $_POST['nem_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nem_newsletter_nonce'] ), 'nem_newsletter' ) ) {
		wp_safe_redirect( add_query_arg( 'nem_newsletter', 'error', $redirect ) . '#nem-newsletter' );
		exit;
	}

	$email  = isset( $_POST['nem_newsletter_email'] ) ? sanitize_email( wp_unslash( $_POST['nem_newsletter_email'] ) ) : '';
	$status = is_email( $email ) ? nem_newsletter_add_email( $email ) : 'invalid';

	wp_safe_redirect( add_query_arg( 'nem_newsletter', $status, $redirect ) . '#nem-newsletter' );
	exit;
}
add_action( 'admin_post_nopriv_nem_newsletter_subscribe', 'nem_handle_newsletter_subscribe' );
add_action( 'admin_post_nem_newsletter_subscribe', 'nem_handle_newsletter_subscribe' );

/* ==========================================================================
   ĐĂNG KÝ TÀI KHOẢN - TRƯỜNG BỔ SUNG
   ========================================================================== */

add_action( 'woocommerce_register_post', function ( $username, $email, $errors ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce đã kiểm tra nonce.
	$last_name  = isset( $_POST['nem_last_name'] ) ? trim( wp_unslash( $_POST['nem_last_name'] ) ) : '';
	$first_name = isset( $_POST['nem_first_name'] ) ? trim( wp_unslash( $_POST['nem_first_name'] ) ) : '';
	$phone      = isset( $_POST['nem_phone'] ) ? trim( wp_unslash( $_POST['nem_phone'] ) ) : '';

	if ( '' === $last_name ) {
		$errors->add( 'nem_last_name_error', 'Vui lòng nhập Họ.' );
	}
	if ( '' === $first_name ) {
		$errors->add( 'nem_first_name_error', 'Vui lòng nhập Tên.' );
	}
	if ( '' === $phone ) {
		$errors->add( 'nem_phone_error', 'Vui lòng nhập Số điện thoại.' );
	} elseif ( ! preg_match( '/^(\+84|0)[0-9]{9,10}$/', preg_replace( '/[\s\.\-]/', '', $phone ) ) ) {
		$errors->add( 'nem_phone_error', 'Số điện thoại không hợp lệ.' );
	}
	if ( empty( $_POST['nem_terms'] ) ) {
		$errors->add( 'nem_terms_error', 'Bạn cần đồng ý với điều khoản của NEM.' );
	}
	// phpcs:enable
	return $errors;
}, 10, 3 );

add_action( 'woocommerce_created_customer', function ( $customer_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$last_name  = sanitize_text_field( wp_unslash( $_POST['nem_last_name'] ?? '' ) );
	$first_name = sanitize_text_field( wp_unslash( $_POST['nem_first_name'] ?? '' ) );
	$phone      = sanitize_text_field( wp_unslash( $_POST['nem_phone'] ?? '' ) );
	$newsletter = ! empty( $_POST['nem_newsletter'] );
	// phpcs:enable

	update_user_meta( $customer_id, 'last_name', $last_name );
	update_user_meta( $customer_id, 'billing_last_name', $last_name );
	update_user_meta( $customer_id, 'first_name', $first_name );
	update_user_meta( $customer_id, 'billing_first_name', $first_name );
	update_user_meta( $customer_id, 'billing_phone', $phone );
	update_user_meta( $customer_id, 'nem_newsletter', $newsletter ? 'yes' : 'no' );

	$display = trim( $last_name . ' ' . $first_name );
	if ( $display ) {
		wp_update_user( array( 'ID' => $customer_id, 'display_name' => $display ) );
	}

	if ( $newsletter ) {
		$user = get_userdata( $customer_id );
		if ( $user ) {
			nem_newsletter_add_email( $user->user_email, $first_name, $last_name );
		}
	}
} );

/**
 * AJAX cập nhật hoặc lưu thông tin địa chỉ từ trang Profile
 */
add_action( 'wp_ajax_nem_save_profile_address', function() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Bạn cần đăng nhập.' ) );
	}

	$user_id = get_current_user_id();

	$fullname = isset( $_POST['fullname'] ) ? sanitize_text_field( wp_unslash( $_POST['fullname'] ) ) : '';
	$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$country  = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : 'Vietnam';
	$address  = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : 'Vietnam';

	if ( ! empty( $fullname ) ) {
		$parts = explode( ' ', trim( $fullname ) );
		$first_name = array_pop( $parts );
		$last_name  = implode( ' ', $parts );

		update_user_meta( $user_id, 'billing_first_name', $first_name );
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'billing_last_name', $last_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $fullname,
		) );
	}

	if ( ! empty( $phone ) ) {
		update_user_meta( $user_id, 'billing_phone', $phone );
	}

	if ( ! empty( $country ) ) {
		update_user_meta( $user_id, 'billing_country', ( 'Vietnam' === $country || 'Việt Nam' === $country ) ? 'VN' : $country );
	}

	if ( ! empty( $address ) ) {
		update_user_meta( $user_id, 'billing_address_1', $address );
	}

	wp_send_json_success( array(
		'fullname' => $fullname,
		'phone'    => $phone,
		'country'  => $country,
		'address'  => $address,
	) );
} );

