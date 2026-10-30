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
	if ( is_page_template( 'page-dang-nhap.php' ) || is_page( array( 'dang-nhap', 'dang-nhap-dang-ky', 'tai-khoan' ) ) ) {
		return true;
	}
	if ( is_user_logged_in() ) {
		return false;
	}
	return ( function_exists( 'is_account_page' ) && is_account_page() );
}

/** Customer name shared by the account page and header. */
function nem_customer_display_name() {
	$user = wp_get_current_user();
	$name = trim( get_user_meta( $user->ID, 'last_name', true ) . ' ' . get_user_meta( $user->ID, 'first_name', true ) );
	if ( '' === $name ) {
		$name = trim( get_user_meta( $user->ID, 'billing_last_name', true ) . ' ' . get_user_meta( $user->ID, 'billing_first_name', true ) );
	}
	return '' !== $name ? $name : ( $user->display_name ?: $user->user_login );
}

/** Validate original input before sanitizing, including crafted POST requests. */
function nem_validate_customer_field( $value, $label, $phone = false ) {
	if ( ! is_string( $value ) ) {
		return $label . ' không hợp lệ.';
	}
	if ( '' === trim( $value ) ) {
		return 'Vui lòng nhập ' . $label . '.';
	}
	if ( $phone ) {
		return preg_match( '/\A[0-9]{1,10}\z/', $value ) ? '' : 'Số điện thoại chỉ được chứa chữ số từ 0 đến 9 và không quá 10 ký tự.';
	}
	return mb_strlen( $value, 'UTF-8' ) > 250 ? $label . ' không được quá 250 ký tự.' : '';
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
	if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
		wp_enqueue_script( 'nem-profile', get_template_directory_uri() . '/assets/js/nem-profile.js', array(), filemtime( get_template_directory() . '/assets/js/nem-profile.js' ), true );
		wp_localize_script( 'nem-profile', 'nemProfile', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nem_profile_address' ),
		) );
	}
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
add_action( 'wp', function () {
	if ( nem_is_auth_page() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
	}
} );

add_action( 'storefront_before_content', function () {
	if ( ! nem_is_auth_page() ) {
		return;
	}
	?>
	<nav class="nem-auth-breadcrumb" aria-label="Breadcrumb">
		<div class="nem-auth-breadcrumb__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
			<span class="nem-auth-breadcrumb__sep">/</span>
			<?php $is_dn = is_page( array( 'dang-nhap', 'dang-nhap-dang-ky' ) ) || is_page_template( 'page-dang-nhap.php' ); ?><span aria-current="page"><?php echo $is_dn ? 'Đăng nhập / Đăng ký' : 'Tài khoản'; ?></span>
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
	$status = mb_strlen( $email, 'UTF-8' ) <= 250 && is_email( $email ) ? nem_newsletter_add_email( $email ) : 'invalid';

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
	foreach ( array( 'nem_last_name' => 'Họ', 'nem_first_name' => 'Tên', 'nem_phone' => 'Số điện thoại', 'email' => 'Email', 'password' => 'Mật khẩu' ) as $key => $label ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		if ( $message = nem_validate_customer_field( $value, $label, 'nem_phone' === $key ) ) {
			$errors->add( $key . '_error', $message );
		}
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
function nem_check_profile_request() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Bạn cần đăng nhập.' ), 401 );
	}
	if ( ! check_ajax_referer( 'nem_profile_address', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.' ), 403 );
	}
}

add_action( 'wp_ajax_nem_save_profile_address', function() {
	nem_check_profile_request();
	$data = array();
	foreach ( array( 'fullname' => 'Họ và tên', 'phone' => 'Số điện thoại', 'country' => 'Quốc gia', 'address' => 'Địa chỉ' ) as $key => $label ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$message = nem_validate_customer_field( $value, $label, 'phone' === $key );
		if ( $message ) {
			wp_send_json_error( array( 'message' => $message ), 422 );
		}
		$data[ $key ] = sanitize_text_field( $value );
		if ( '' === $data[ $key ] ) {
			wp_send_json_error( array( 'message' => $label . ' không hợp lệ.' ), 422 );
		}
	}
	$user_id = get_current_user_id();
	$parts = preg_split( '/\s+/u', $data['fullname'] );
	$first_name = array_pop( $parts );
	$last_name = implode( ' ', $parts );
	$result = wp_update_user( array( 'ID' => $user_id, 'display_name' => $data['fullname'], 'first_name' => $first_name, 'last_name' => $last_name ) );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => 'Không thể lưu thông tin. Vui lòng thử lại.' ), 500 );
	}
	update_user_meta( $user_id, 'billing_first_name', $first_name );
	update_user_meta( $user_id, 'billing_last_name', $last_name );
	update_user_meta( $user_id, 'billing_phone', $data['phone'] );
	update_user_meta( $user_id, 'billing_country', in_array( $data['country'], array( 'Vietnam', 'Việt Nam' ), true ) ? 'VN' : $data['country'] );
	update_user_meta( $user_id, 'billing_address_1', $data['address'] );
	wp_send_json_success( $data );
} );

add_action( 'wp_ajax_nem_delete_profile_address', function() {
	nem_check_profile_request();
	$user_id = get_current_user_id();
	foreach ( array( 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country', 'billing_company', 'billing_phone' ) as $key ) {
		delete_user_meta( $user_id, $key );
	}
	wp_send_json_success( array( 'message' => 'Đã xóa thông tin giao hàng.' ) );
} );

