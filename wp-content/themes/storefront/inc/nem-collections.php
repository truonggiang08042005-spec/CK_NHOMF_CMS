<?php
/** NEM collection landing page and WooCommerce collection links. */
defined( 'ABSPATH' ) || exit;

function nem_collections_catalog() {
	return array(
		'thoi-nha' => array(
			'title' => 'ÁO DÀI COLLECTION - THỜI NHÃ',
			'description' => 'Thời Nhã gợi lại nét thanh lịch của áo dài Việt qua sắc màu dịu nhẹ, chất liệu mềm mại và những chi tiết được chăm chút. Mỗi thiết kế mang vẻ duyên dáng truyền thống vào nhịp sống hôm nay, để bạn tự tin trong những dịp gặp gỡ và khoảnh khắc đáng nhớ.',
			'banner' => 'banner_section_coll_1.png',
			'editorials' => array( 'banner_section_coll_2_1.png', 'banner_section_coll_2_2.png' ),
			'skus' => array( 'AD0235', 'AD02402', 'AD02372', 'AD02362' ),
		),
		'nhe-buoc-moi-ngay' => array(
			'title' => 'CASUAL COLLECTION – NHẸ BƯỚC MỖI NGÀY',
			'description' => 'Từ buổi sáng nơi công sở đến những cuộc hẹn cuối ngày, những thiết kế Casual mang đến sự thoải mái và vẻ chỉn chu. Áo len nhẹ, đầm suông và quần jeans dễ kết hợp giúp bạn chọn trang phục theo cách riêng, để mỗi ngày đều bắt đầu thật nhẹ nhàng.',
			'banner' => 'banner_section_coll_3.png',
			'editorials' => array( 'banner_section_coll_4_1.png', 'banner_section_coll_4_2.png' ),
			'skus' => array( 'D64282', 'AL62372', 'Q67112', 'Q67102' ),
		),
	);
}

function nem_collection_product( $sku ) {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return false;
	}
	$id = wc_get_product_id_by_sku( 'NEM-' . $sku ) ?: wc_get_product_id_by_sku( $sku );
	$product = $id ? wc_get_product( $id ) : false;
	return $product && 'publish' === $product->get_status() && $product->is_visible() ? $product : false;
}

function nem_collection_asset( $filename ) {
	return get_template_directory_uri() . '/assets/images/nem/collections/' . pathinfo( $filename, PATHINFO_FILENAME ) . '.webp';
}

function nem_collection_url( $slug ) {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	return add_query_arg( 'collection', $slug, $shop );
}

function nem_collection_catalog_effects() {
	return function_exists( 'is_shop' ) && (
		is_search() && function_exists( 'nem_is_product_search' ) && nem_is_product_search() ||
		! is_search() && ( is_shop() || is_product_taxonomy() || is_product() )
	);
}

function nem_is_collection_page() {
	return is_page( 'bo-suu-tap' ) || is_page_template( 'page-bo-suu-tap.php' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( nem_is_collection_page() || nem_collection_catalog_effects() ) {
		wp_enqueue_style( 'nem-collections', get_template_directory_uri() . '/assets/css/nem-collections.css', array( 'nem-header-footer-style' ), filemtime( get_template_directory() . '/assets/css/nem-collections.css' ) );
	}
}, 100 );

add_filter( 'body_class', function ( $classes ) {
	if ( nem_is_collection_page() ) {
		$classes[] = 'nem-collections-page';
	}
	return $classes;
} );

add_action( 'wp', function () {
	if ( nem_is_collection_page() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}
} );

// Collection links filter the real shop catalog, including pagination.
add_action( 'woocommerce_product_query', function ( $query ) {
	$slug = isset( $_GET['collection'] ) && is_string( $_GET['collection'] ) ? sanitize_title( wp_unslash( $_GET['collection'] ) ) : '';
	if ( ! in_array( $slug, array( 'thoi-nha', 'nhe-buoc-moi-ngay', 'giao-thoi', 'nhip-thanh-pho' ), true ) ) {
		return;
	}
	$tax_query = (array) $query->get( 'tax_query' );
	$query->set( 'tax_query', array(
		'relation' => 'AND',
		$tax_query,
		array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => 'bst-' . $slug ),
	) );
} );

// Keep the same image interaction when opening a collection in the shop.
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	if ( nem_collection_catalog_effects() ) {
		echo '<span class="nem-catalog-product-media">';
	}
}, 9 );

add_action( 'woocommerce_before_shop_loop_item_title', function () {
	global $product;
	if ( ! nem_collection_catalog_effects() ) { return; }
	$gallery = $product ? $product->get_gallery_image_ids() : array();
	if ( $gallery ) {
		echo wp_get_attachment_image( $gallery[0], 'woocommerce_thumbnail', false, array( 'class' => 'nem-catalog-image-secondary', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy' ) );
	}
	echo '<span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span></span>';
}, 11 );

add_filter( 'woocommerce_page_title', function ( $title ) {
	$names = array( 'thoi-nha' => 'Áo dài THỜI NHÃ', 'nhe-buoc-moi-ngay' => 'NHẸ BƯỚC MỖI NGÀY', 'giao-thoi' => 'GIAO THỜI', 'nhip-thanh-pho' => 'Nhịp Thành phố' );
	$slug = isset( $_GET['collection'] ) && is_string( $_GET['collection'] ) ? sanitize_title( wp_unslash( $_GET['collection'] ) ) : '';
	return is_shop() && isset( $names[ $slug ] ) ? $names[ $slug ] : $title;
} );

add_action( 'storefront_before_footer', function () {
	if ( nem_is_collection_page() ) {
		get_template_part( 'template-parts/collection-newsletter' );
	}
} );

function nem_collection_newsletter_subscribe() {
	$raw = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
	$nonce = isset( $_POST['nem_collection_nonce'] ) && is_string( $_POST['nem_collection_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nem_collection_nonce'] ) ) : '';
	$status = 'error';
	if ( wp_verify_nonce( $nonce, 'nem_collection_newsletter' ) && strlen( $raw ) <= 250 && is_email( $raw ) ) {
		$email = sanitize_email( $raw );
		if ( function_exists( 'nem_newsletter_add_email' ) ) {
			$status = nem_newsletter_add_email( $email );
		} else {
			$emails = (array) get_option( 'nem_newsletter_emails', array() );
			$status = in_array( $email, $emails, true ) ? 'exists' : 'ok';
			if ( 'ok' === $status ) {
				$emails[] = $email;
				update_option( 'nem_newsletter_emails', $emails, false );
			}
		}
	}
	$return_path = '/bo-suu-tap/';
	if ( isset( $_POST['return_to'] ) ) {
		if ( 'san-pham-moi' === $_POST['return_to'] ) {
			$return_path = '/san-pham-moi/';
		} elseif ( 'nem-plus' === $_POST['return_to'] ) {
			$return_path = '/nem-plus/';
		}
	}
	wp_safe_redirect( add_query_arg( 'newsletter', $status, home_url( $return_path ) ) . '#nem-collection-newsletter' );
	exit;
}
add_action( 'admin_post_nem_collection_newsletter', 'nem_collection_newsletter_subscribe' );
add_action( 'admin_post_nopriv_nem_collection_newsletter', 'nem_collection_newsletter_subscribe' );
