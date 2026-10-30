<?php
/**
 * Template Name: NEM - Đăng nhập / Đăng ký
 *
 * Đồng bộ trang Đăng nhập & Đăng ký chung một trang cho NEM Fashion.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! function_exists( 'WC' ) ) {
	echo '<main class="site-main nem-auth-main"><p>Hệ thống tài khoản đang được bảo trì.</p></main>';
	get_footer();
	return;
}
?>
<main id="main" class="site-main nem-auth-main">
	<?php
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		?>
		<div class="nem-auth-logged-box" style="max-width:540px; margin:90px auto; text-align:center; padding:40px 24px; font-family:'Times New Roman',Times,serif;">
			<h2 style="font-size:26px; margin:0 0 16px; font-weight:700; color:#111;">Bạn đã đăng nhập</h2>
			<p style="font-size:16px; color:#555; margin:0 0 28px; line-height:1.6;">Xin chào <strong><?php echo esc_html( $current_user->display_name ? $current_user->display_name : $current_user->user_email ); ?></strong>. Bạn hiện đã đăng nhập vào hệ thống.</p>
			<div style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
				<a href="<?php echo esc_url( $account_url ); ?>" class="nem-auth__btn" style="display:inline-block; padding:12px 28px; text-decoration:none; text-align:center;">Quản lý tài khoản</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="nem-auth__btn" style="display:inline-block; padding:12px 28px; background:#444; text-decoration:none; text-align:center;">Đăng xuất</a>
			</div>
		</div>
		<?php
	} else {
		// Kiểm tra nếu admin đổi vị trí 2 cột trong Gutenberg Editor (kéo Đăng ký sang trái, Đăng nhập sang phải)
		$current_post_id = get_the_ID();
		$post_obj        = $current_post_id ? get_post( $current_post_id ) : get_post( 239 );
		$content         = $post_obj ? $post_obj->post_content : '';
		$pos_reg         = strpos( $content, 'nem-auth__col--register' );
		$pos_log         = strpos( $content, 'nem-auth__col--login' );
		if ( false !== $pos_reg && false !== $pos_log && $pos_reg < $pos_log ) {
			echo '<style>.nem-auth { display: flex !important; flex-direction: row-reverse !important; } .nem-auth__col--register { border-left: none !important; border-right: 1px solid var(--nem-line) !important; padding-left: 9.2% !important; padding-right: 8% !important; } .nem-auth__col--login { padding-left: 17.5% !important; }</style>';
		}
		wc_get_template( 'myaccount/form-login.php' );
	}
	?>
</main>
<?php
get_footer();
