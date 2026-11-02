<?php
/**
 * NEM - Đăng nhập + Đăng ký chung một trang.
 * Hỗ trợ nhận biến tùy biến tiêu đề, mô tả và nút bấm từ Gutenberg Editor.
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Missing
$nem_post = function ( $key ) {
	return ( ! empty( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) ? esc_attr( wp_unslash( $_POST[ $key ] ) ) : '';
};

$nem_terms_id  = function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0;
$nem_terms_url = $nem_terms_id ? get_permalink( $nem_terms_id ) : ( get_privacy_policy_url() ? get_privacy_policy_url() : '#' );

// Nhận các giá trị tùy biến từ Gutenberg Block Editor (nếu có)
$login_title    = ! empty( $login_title ) ? $login_title : 'Đăng nhập';
$login_desc     = ! empty( $login_desc ) ? $login_desc : 'Nếu bạn đã có tài khoản, hãy đăng nhập để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!';
$login_btn      = ! empty( $login_btn ) ? $login_btn : 'Đăng nhập';
$register_title = ! empty( $register_title ) ? $register_title : 'Đăng ký';
$register_desc  = ! empty( $register_desc ) ? $register_desc : 'Hãy đăng ký ngay để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!';
$register_btn   = ! empty( $register_btn ) ? $register_btn : 'Đăng ký';
$is_swap        = ! empty( $register_first );

do_action( 'woocommerce_before_customer_login_form' ); ?>

<div class="nem-auth <?php echo $is_swap ? 'nem-auth--swap' : ''; ?>" id="customer_login">

	<!-- ĐĂNG NHẬP -->
	<section class="nem-auth__col nem-auth__col--login" aria-labelledby="nem-login-title">
		<div class="nem-auth__box">
			<h2 class="nem-auth__title" id="nem-login-title"><?php echo esc_html( $login_title ); ?></h2>
			<p class="nem-auth__desc"><?php echo esc_html( $login_desc ); ?></p>

			<form class="woocommerce-form woocommerce-form-login login nem-auth__form" method="post" novalidate>
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<div class="nem-auth__field">
					<label for="username">Email</label>
					<input maxlength="250" type="text" class="input-text" name="username" id="username" autocomplete="username" placeholder="Email" value="<?php echo $nem_post( 'username' ); // phpcs:ignore ?>" required aria-required="true" />
				</div>

				<div class="nem-auth__field">
					<label for="password">Mật khẩu</label>
					<input maxlength="250" type="password" class="input-text" name="password" id="password" autocomplete="current-password" placeholder="Mật khẩu" required aria-required="true" />
				</div>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="nem-auth__forgot">
					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" id="nem-forgot-link">Quên mật khẩu</a>
				</div>

				<input type="hidden" name="rememberme" value="forever" />
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="nem-auth__btn woocommerce-form-login__submit" id="nem-login-submit" name="login" value="<?php echo esc_attr( $login_btn ); ?>"><?php echo esc_html( $login_btn ); ?></button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>
	</section>

	<!-- ĐĂNG KÝ -->
	<section class="nem-auth__col nem-auth__col--register" aria-labelledby="nem-register-title">
		<div class="nem-auth__box">
			<h2 class="nem-auth__title" id="nem-register-title"><?php echo esc_html( $register_title ); ?></h2>
			<p class="nem-auth__desc"><?php echo esc_html( $register_desc ); ?></p>

			<form method="post" class="woocommerce-form woocommerce-form-register register nem-auth__form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<div class="nem-auth__field">
					<label for="reg_last_name">Họ</label>
					<input maxlength="250" type="text" class="input-text" name="nem_last_name" id="reg_last_name" autocomplete="family-name" placeholder="Họ" value="<?php echo $nem_post( 'nem_last_name' ); // phpcs:ignore ?>" required />
				</div>

				<div class="nem-auth__field">
					<label for="reg_first_name">Tên</label>
					<input maxlength="250" type="text" class="input-text" name="nem_first_name" id="reg_first_name" autocomplete="given-name" placeholder="Tên" value="<?php echo $nem_post( 'nem_first_name' ); // phpcs:ignore ?>" required />
				</div>

				<div class="nem-auth__field">
					<label for="reg_email">Email</label>
					<input maxlength="250" type="email" class="input-text" name="email" id="reg_email" autocomplete="email" placeholder="Email" value="<?php echo $nem_post( 'email' ); // phpcs:ignore ?>" required aria-required="true" />
				</div>

				<div class="nem-auth__field">
					<label for="reg_phone">Số điện thoại</label>
					<input maxlength="10" inputmode="numeric" pattern="[0-9]{1,10}" title="Chỉ nhập chữ số, tối đa 10 ký tự" type="tel" class="input-text" name="nem_phone" id="reg_phone" autocomplete="tel" placeholder="Số điện thoại" value="<?php echo $nem_post( 'nem_phone' ); // phpcs:ignore ?>" required />
				</div>

				<div class="nem-auth__field">
					<label for="reg_password">Mật khẩu</label>
					<input maxlength="250" type="password" class="input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="Mật khẩu" required aria-required="true" />
				</div>

				<label class="nem-auth__check" for="reg_newsletter">
					<input type="checkbox" name="nem_newsletter" id="reg_newsletter" value="1" <?php checked( ! empty( $_POST['nem_newsletter'] ) ); ?> />
					<span>Đăng ký nhận bản tin</span>
				</label>

				<label class="nem-auth__check" for="reg_terms">
					<input type="checkbox" name="nem_terms" id="reg_terms" value="1" <?php checked( ! empty( $_POST['nem_terms'] ) ); ?> />
					<span>Tôi đồng ý với các <a href="<?php echo esc_url( $nem_terms_url ); ?>" target="_blank" rel="noopener">điều khoản</a> của NEM</span>
				</label>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="nem-auth__btn woocommerce-form-register__submit" id="nem-register-submit" name="register" value="<?php echo esc_attr( $register_btn ); ?>"><?php echo esc_html( $register_btn ); ?></button>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>
	</section>

</div>

<?php
// phpcs:enable
do_action( 'woocommerce_after_customer_login_form' );
