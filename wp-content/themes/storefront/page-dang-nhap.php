<?php
/**
 * Template Name: NEM - Đăng nhập / Đăng ký
 *
 * Đồng bộ trang Đăng nhập & Đăng ký chung một trang cho NEM Fashion.
 * Tự động cập nhật mọi thay đổi từ Gutenberg Editor ra ngoài Frontend.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( ! function_exists( 'WC' ) ) {
	echo '<main class="site-main nem-auth-main"><p>Hệ thống tài khoản đang được bảo trì.</p></main>';
	get_footer();
	return;
}

$current_post_id = get_the_ID() ?: 239;
$post_obj        = get_post( $current_post_id );
$post_content    = $post_obj ? $post_obj->post_content : '';
$blocks          = parse_blocks( $post_content );

$auth_data = array(
	'breadcrumb_html' => '',
	'login_title'     => 'Đăng nhập',
	'login_desc'      => 'Nếu bạn đã có tài khoản, hãy đăng nhập để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!',
	'login_btn'       => 'Đăng nhập',
	'register_title'  => 'Đăng ký',
	'register_desc'   => 'Hãy đăng ký ngay để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!',
	'register_btn'    => 'Đăng ký',
	'register_first'  => false,
);

$before_auth_blocks = array();
$after_auth_blocks  = array();
$found_auth_layout  = false;

foreach ( $blocks as $b ) {
	if ( empty( $b['blockName'] ) && empty( trim( $b['innerHTML'] ) ) ) {
		continue;
	}

	$cls = isset( $b['attrs']['className'] ) ? str_replace( array( '\u002d', 'u002d' ), '-', $b['attrs']['className'] ) : '';

	// Khối Breadcrumb
	if ( strpos( $cls, 'nem-auth-breadcrumb' ) !== false ) {
		$auth_data['breadcrumb_html'] = render_block( $b );
		continue;
	}

	// Bỏ qua khối newsletter và footer trong post_content vì template footer-nem đã hiển thị chuẩn
	if ( strpos( $cls, 'nem-collection-newsletter' ) !== false || strpos( $cls, 'nem-footer-wrapper' ) !== false ) {
		continue;
	}

	// Khối 2 cột Đăng nhập & Đăng ký (core/columns)
	if ( 'core/columns' === $b['blockName'] && ( strpos( $cls, 'nem-auth-layout' ) !== false || strpos( $cls, 'nem-auth' ) !== false ) ) {
		$found_auth_layout = true;
		$cols = ! empty( $b['innerBlocks'] ) ? $b['innerBlocks'] : array();

		if ( ! empty( $cols ) ) {
			$first_cls = isset( $cols[0]['attrs']['className'] ) ? str_replace( array( '\u002d', 'u002d' ), '-', $cols[0]['attrs']['className'] ) : '';
			if ( strpos( $first_cls, 'register' ) !== false ) {
				$auth_data['register_first'] = true;
			}

			foreach ( $cols as $col ) {
				$col_cls  = isset( $col['attrs']['className'] ) ? str_replace( array( '\u002d', 'u002d' ), '-', $col['attrs']['className'] ) : '';
				$is_login = ( strpos( $col_cls, 'login' ) !== false );
				$is_reg   = ( strpos( $col_cls, 'register' ) !== false );
				$col_html = serialize_blocks( array( $col ) );

				if ( preg_match( '/<h2[^>]*class="[^"]*nem-auth__title[^"]*"[^>]*>(.*?)<\/h2>/is', $col_html, $m ) ) {
					$val = trim( strip_tags( $m[1] ) );
					if ( '' !== $val ) {
						if ( $is_login ) {
							$auth_data['login_title'] = $val;
						}
						if ( $is_reg ) {
							$auth_data['register_title'] = $val;
						}
					}
				}
				if ( preg_match( '/<p[^>]*class="[^"]*nem-auth__desc[^"]*"[^>]*>(.*?)<\/p>/is', $col_html, $m ) ) {
					$val = trim( strip_tags( $m[1] ) );
					if ( '' !== $val ) {
						if ( $is_login ) {
							$auth_data['login_desc'] = $val;
						}
						if ( $is_reg ) {
							$auth_data['register_desc'] = $val;
						}
					}
				}
				if ( preg_match( '/<p[^>]*class="[^"]*nem-auth__btn[^"]*"[^>]*>(.*?)<\/p>/is', $col_html, $m ) ) {
					$val = trim( strip_tags( $m[1] ) );
					if ( '' !== $val ) {
						if ( $is_login ) {
							$auth_data['login_btn'] = $val;
						}
						if ( $is_reg ) {
							$auth_data['register_btn'] = $val;
						}
					}
				}
			}
		}
		continue;
	}

	// Bất kỳ khối tùy biến nào khác do người dùng thêm vào
	if ( ! $found_auth_layout ) {
		$before_auth_blocks[] = $b;
	} else {
		$after_auth_blocks[] = $b;
	}
}
?>
<main id="main" class="site-main nem-auth-main">
	<?php
	// 1. Hiển thị Breadcrumb tùy biến từ Gutenberg
	if ( ! empty( $auth_data['breadcrumb_html'] ) ) {
		echo $auth_data['breadcrumb_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// 2. Nếu đã đăng nhập, hiển thị thanh thông báo trạng thái phía trên (vẫn hiển thị giao diện bên dưới để xem/sửa)
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		?>
		<div class="nem-auth-logged-banner" style="max-width:1320px; margin:24px auto 0; padding:16px 24px; background:#f5f7fa; border:1px solid #e2e8f0; border-radius:4px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; font-family:'Times New Roman',Times,serif; box-sizing:border-box;">
			<div style="font-size:15px; color:#333;">
				Xin chào <strong><?php echo esc_html( $current_user->display_name ? $current_user->display_name : $current_user->user_email ); ?></strong>. Bạn hiện đang đăng nhập vào hệ thống.
			</div>
			<div style="display:flex; gap:10px;">
				<a href="<?php echo esc_url( $account_url ); ?>" class="nem-auth__btn" style="display:inline-block; padding:8px 20px; background:#000; color:#fff; text-decoration:none; font-size:13px; font-weight:700; text-transform:uppercase;">Tài khoản của tôi</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/dang-nhap/' ) ) ); ?>" class="nem-auth__btn" style="display:inline-block; padding:8px 18px; background:#555; color:#fff; text-decoration:none; font-size:13px;">Đăng xuất</a>
			</div>
		</div>
		<?php
	}

	// 3. Hiển thị các khối tùy biến trước form (nếu có)
	foreach ( $before_auth_blocks as $b ) {
		echo render_block( $b ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// 4. Hiển thị Form Đăng nhập & Đăng ký WooCommerce với dữ liệu đã đồng bộ từ Gutenberg
	wc_get_template( 'myaccount/form-login.php', $auth_data );

	// 5. Hiển thị các khối tùy biến sau form (nếu có)
	foreach ( $after_auth_blocks as $b ) {
		echo render_block( $b ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</main>
<?php
get_footer();
