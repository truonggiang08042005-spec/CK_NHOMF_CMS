<?php
/** Import the existing account layout as native blocks. Run once from the project root. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';

function nem_account_design_leaf( $type, $class, $text, $attrs = [] ) {
	$attrs['className'] = $class;
	$tag = 'heading' === $type ? 'h' . ( $attrs['level'] ?? 2 ) : 'p';
	$root_class = ( 'heading' === $type ? 'wp-block-heading ' : '' ) . $class;
	$id = isset( $attrs['anchor'] ) ? ' id="' . esc_attr( $attrs['anchor'] ) . '"' : '';
	$html = '<' . $tag . $id . ' class="' . esc_attr( $root_class ) . '">' . $text . '</' . $tag . '>';
	return [ 'blockName' => 'core/' . $type, 'attrs' => $attrs, 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [ $html ] ];
}
function nem_account_design_group( $class, $children, $attrs = [], $type = 'group' ) {
	$attrs['className'] = $class;
	$tag = $attrs['tagName'] ?? 'div';
	$root_class = 'wp-block-' . $type . ( 'columns' === $type ? ' is-not-stacked-on-mobile' : '' ) . ' ' . $class;
	if ( 'columns' === $type ) $attrs['isStackedOnMobile'] = false;
	$id = isset( $attrs['anchor'] ) ? ' id="' . esc_attr( $attrs['anchor'] ) . '"' : '';
	$open = '<' . $tag . $id . ' class="' . esc_attr( $root_class ) . '">';
	$close = '</' . $tag . '>';
	return [ 'blockName' => 'core/' . $type, 'attrs' => $attrs, 'innerBlocks' => $children, 'innerHTML' => $open . $close, 'innerContent' => array_merge( [ $open ], array_fill( 0, count( $children ), null ), [ $close ] ) ];
}
$p = static fn( $class, $text, $attrs = [] ) => nem_account_design_leaf( 'paragraph', $class, $text, $attrs );
$h = static fn( $class, $text, $attrs = [] ) => nem_account_design_leaf( 'heading', $class, $text, $attrs );
$g = 'nem_account_design_group';
$meta = static fn( $name ) => [ 'metadata' => [ 'name' => $name ] ];
$home = esc_url( home_url( '/' ) );
$profile_fields = [];
foreach ( [ 'fullname' => 'Họ tên', 'country' => 'Quốc gia:', 'phone' => 'Số điện thoại:' ] as $key => $label ) {
	$profile_fields[] = $g( 'nem-profile-field-group', [ $p( 'nem-field-label', $label ), $p( 'nem-field-box nem-account-value-' . $key, $label, $meta( 'Giá trị tự động: ' . $label ) ) ] );
}
$nav = [];
foreach ( [ 'account' => 'Thông tin tài khoản', 'orders' => 'Quản lý đơn hàng', 'address' => 'Thông tin giao hàng' ] as $tab => $label ) {
	$nav[] = $p( 'nem-account-nav-item nem-account-tab-' . $tab, '<a class="nem-account-nav-link" href="#' . $tab . '">' . $label . '</a>' );
}
$members = $g( 'nem-account-members', [ $g( 'nem-profile-wrapper', [
	$g( 'nem-profile-breadcrumb', [ $p( 'nem-profile-home', '<a href="' . $home . '">TRANG CHỦ</a>' ), $p( 'nem-bc-separator', '/' ), $p( 'nem-bc-active', 'TÀI KHOẢN', [ 'anchor' => 'nemBcActive' ] ) ] ),
	$g( 'nem-profile-greeting-wrap', [ $p( 'nem-greeting-sub', 'Xin chào,' ), $h( 'nem-greeting-name nem-account-customer-name', 'Tên khách hàng', [ 'level' => 1, 'anchor' => 'nemGreetingName', 'metadata' => [ 'name' => 'Tên khách hàng • dữ liệu tự động' ] ] ) ] ),
	$g( 'nem-profile-grid', [
		$g( 'nem-profile-sidebar-col', [ $g( 'nem-account-nav', $nav, [ 'tagName' => 'nav' ] ) ], $meta( 'Menu tài khoản' ), 'column' ),
		$g( 'nem-profile-content-col', [
			$g( 'nem-tab-panel is-active', [ $g( 'nem-profile-dashboard', $profile_fields ) ], [ 'anchor' => 'nemPanelAccount', 'metadata' => [ 'name' => 'Thông tin tài khoản' ] ] ),
			$g( 'nem-tab-panel', [ $p( 'nem-orders-empty nem-account-orders-list', 'Bạn chưa có đơn hàng nào.' ) ], [ 'anchor' => 'nemPanelOrders', 'metadata' => [ 'name' => 'Quản lý đơn hàng • dữ liệu tự động' ] ] ),
			$g( 'nem-tab-panel', [ $g( 'nem-address-card-wrap', [
				$g( 'nem-account-address-details', [
					$g( 'nem-address-card-header', [
						$g( 'nem-address-name-wrap', [ $p( 'nem-address-name nem-account-address-name', 'Tên khách hàng', [ 'anchor' => 'nemAddrDisplayName' ] ), $p( 'nem-address-default-badge', '(Địa chỉ mặc định)' ) ] ),
						$g( 'nem-address-actions', [ $p( 'nem-addr-action-btn nem-account-action-edit', 'Sửa' ), $p( 'nem-addr-sep', '|' ), $p( 'nem-addr-action-btn nem-account-action-delete', 'Xóa' ) ] ),
					] ),
					$g( 'nem-address-row', [ $p( 'nem-address-label', 'Địa chỉ:' ), $p( 'nem-account-address-address', 'Địa chỉ khách hàng', [ 'anchor' => 'nemAddrValAddress' ] ) ], [ 'anchor' => 'nemAddrRowAddress' ] ),
					$g( 'nem-address-row', [ $p( 'nem-address-label', 'Điện thoại:' ), $p( 'nem-account-address-phone', 'Số điện thoại khách hàng', [ 'anchor' => 'nemAddrValPhone' ] ) ], [ 'anchor' => 'nemAddrRowPhone' ] ),
				], [ 'anchor' => 'nemAddressDetails' ] ),
				$p( 'nem-account-address-empty', 'Bạn chưa có thông tin giao hàng.', [ 'anchor' => 'nemAddressEmpty' ] ),
				$p( 'nem-account-address-status', '', [ 'anchor' => 'nemAddressStatus' ] ),
				$g( 'nem-address-divider', [] ),
				$p( 'nem-btn-add-address nem-account-action-add', 'THÊM MỚI' ),
			] ) ], [ 'anchor' => 'nemPanelAddress', 'metadata' => [ 'name' => 'Thông tin giao hàng' ] ] ),
		], $meta( 'Nội dung tài khoản' ), 'column' ),
	], [], 'columns' ),
] ) ], $meta( 'Tài khoản • đã đăng nhập' ) );

$columns = [];
$labels = [ 'login-email' => 'Email', 'login-password' => 'Mật khẩu', 'register-last-name' => 'Họ', 'register-first-name' => 'Tên', 'register-email' => 'Email', 'register-phone' => 'Số điện thoại', 'register-password' => 'Mật khẩu' ];
$terms_id = function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0;
$terms_url = $terms_id ? get_permalink( $terms_id ) : ( get_privacy_policy_url() ?: '#' );
foreach ( [ 'login' => 'Đăng nhập', 'register' => 'Đăng ký' ] as $kind => $title ) {
	$fields = [];
	foreach ( nem_account_input_fields() as $key => $config ) {
		if ( ! str_starts_with( $key, $kind . '-' ) ) continue;
		$fields[] = $g( 'nem-auth__field', [ $p( 'nem-auth__label nem-account-label-' . $key, $labels[ $key ] ), $p( 'nem-auth__input-box nem-account-input-' . $key, $labels[ $key ] ) ], $meta( $labels[ $key ] ) );
	}
	if ( 'login' === $kind ) {
		$fields[] = $p( 'nem-auth__forgot', '<a href="' . esc_url( wp_lostpassword_url() ) . '">Quên mật khẩu</a>' );
	} else {
		$fields[] = $p( 'nem-auth__check nem-account-check-newsletter', 'Đăng ký nhận bản tin' );
		$fields[] = $p( 'nem-auth__check nem-account-check-terms', 'Tôi đồng ý với các <a href="' . esc_url( $terms_url ) . '" target="_blank" rel="noreferrer noopener">điều khoản</a> của NEM' );
	}
	$fields[] = $p( 'nem-auth__btn nem-account-submit-' . $kind, mb_strtoupper( $title, 'UTF-8' ) );
	$columns[] = $g( 'nem-auth__col nem-auth__col--' . $kind, [ $g( 'nem-auth__box', [
		$h( 'nem-auth__title', $title ),
		$p( 'nem-auth__desc', 'login' === $kind ? 'Nếu bạn đã có tài khoản, hãy đăng nhập để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!' : 'Hãy đăng ký ngay để tích lũy điểm thành viên và nhận được những ưu đãi tốt hơn!' ),
		$g( 'nem-account-form-' . $kind, $fields ),
	] ) ], $meta( $title ), 'column' );
}
$guests = $g( 'nem-account-guests nem-auth-page', [
	$g( 'nem-auth-breadcrumb', [ $p( 'nem-auth-breadcrumb__inner', '<a href="' . $home . '">TRANG CHỦ</a> / TÀI KHOẢN' ) ] ),
	$g( 'nem-auth', $columns, [], 'columns' ),
], $meta( 'Đăng nhập / Đăng ký • khách chưa đăng nhập' ) );
$socials = [];
foreach ( [ 'facebook' => [ 'Facebook', 'https://www.facebook.com/nemfashion.official' ], 'instagram' => [ 'Instagram', 'https://www.instagram.com/nemfashion.official/' ], 'youtube' => [ 'YouTube', 'https://www.youtube.com/channel/UCrDQ6euiL9CekivZmD9-MCA' ] ] as $key => [ $label, $url ] ) {
	$socials[] = $p( 'nem-account-social-item', '<a class="nem-social-bubble nem-social-' . $key . '" href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer noopener">' . $label . '</a>' );
}
$newsletter = $g( 'nem-newsletter-section', [ $g( 'nem-newsletter-container', [
	$h( 'nem-newsletter-heading', 'ĐĂNG KÝ BẢN TIN' ),
	$p( 'nem-newsletter-desc', 'Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất' ),
	$g( 'nem-newsletter-form nem-account-newsletter-form', [ $p( 'nem-newsletter-input nem-account-newsletter-email', 'Vui lòng nhập email...' ), $p( 'nem-newsletter-submit nem-account-newsletter-submit', 'ĐĂNG KÝ' ) ] ),
	$g( 'nem-newsletter-social-icons', $socials ),
] ) ], [ 'tagName' => 'section', 'anchor' => 'nem-newsletter', 'metadata' => [ 'name' => 'Đăng ký bản tin và mạng xã hội' ] ] );

$page = nem_account_design_post();
if ( ! $page ) throw new RuntimeException( 'WooCommerce account page was not found.' );
if ( str_contains( $page->post_content, 'nem-account-design' ) ) {
	echo "Account already has an editable design; no content changed.\n";
	exit;
}
// Never replace an independently edited page on a subsequent deployment.
if ( trim( wp_strip_all_tags( $page->post_content ) ) !== '[woocommerce_my_account]' ) throw new RuntimeException( 'Account page contains custom content; preserve and migrate it before setup.' );
add_post_meta( $page->ID, '_nem_account_editor_original_content', wp_slash( $page->post_content ), true );
$content = serialize_blocks( [ $g( 'nem-account-design', [ $members, $guests, $newsletter ] ) ] );
$updated = wp_update_post( wp_slash( [ 'ID' => $page->ID, 'post_content' => $content ] ), true );
if ( is_wp_error( $updated ) ) throw new RuntimeException( $updated->get_error_message() );
echo 'Imported native account blocks into page #' . $page->ID . "; original shortcode backed up.\n";
