<?php
/** New arrivals catalog with persistent, server-rendered filters. */
defined( 'ABSPATH' ) || exit;

function nem_is_new_products_page() {
	return is_page( 'san-pham-moi' ) || is_page_template( 'page-san-pham-moi.php' ) ||
		( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['orderby'] ) && 'date' === $_GET['orderby'] );
}

/** Use saved swatch colors where available, with named and patterned fallbacks. */
function nem_catalog_swatch_style( $term ) {
	$color = sanitize_hex_color( get_term_meta( $term->term_id, 'product_attribute_color', true ) );
	if ( $color ) {
		return 'background: ' . $color . ';';
	}
	$name = sanitize_title( $term->name );
	$slug = $term->slug;
	if ( preg_match( '/stripe|ke-soc|soc/', $slug . ' ' . $name ) ) {
		return 'background: repeating-linear-gradient(90deg, #c7cbe9 0 3px, #f7f7fb 3px 6px);';
	}
	if ( preg_match( '/pattern|hoa-tiet|hoa-xanh/', $slug . ' ' . $name ) ) {
		return 'background: radial-gradient(ellipse at 25% 30%, #a7b6cf, transparent 45%), radial-gradient(ellipse at 75% 65%, #93a9c7, transparent 40%), #e7edf4;';
	}
	$colors = array(
		'light-blue' => '#a3cfe7', 'xanh-nhat' => '#a3cfe7', 'xanh-duong' => '#a3cfe7', 'xanh' => '#a3cfe7',
		'red' => '#eb2d30', 'do' => '#eb2d30', 'light-pink' => '#efc8ed', 'hong-nhat' => '#efc8ed',
		'yellow' => '#f7e27a', 'vang' => '#f7e27a', 'white' => '#fff', 'trang' => '#fff',
		'black' => '#000', 'den' => '#000', 'olive' => '#897925', 'vang-reu' => '#897925',
		'gray' => '#999', 'grey' => '#999', 'xam' => '#999', 'ghi' => '#999', 'nau' => '#8d6e63',
		'mint-green' => '#84e987', 'xanh-la' => '#84e987', 'xanh-mint' => '#84e987',
		'orange' => '#ff8511', 'cam' => '#ff8511', 'pink' => '#f2acd1', 'hong' => '#f2acd1',
		'be' => '#e6d7bd', 'kem' => '#f4ecdc', 'tim' => '#c4a5d8', 'trang-trang-kem' => '#f4ecdc',
	);
	return 'background: ' . ( $colors[ $slug ] ?? $colors[ $name ] ?? '#ccc' ) . ';';
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! nem_is_new_products_page() && ! nem_is_shop_archive() &&
		! ( function_exists( 'nem_is_plus_page' ) && nem_is_plus_page() ) &&
		! ( function_exists( 'nem_is_sale_page' ) && nem_is_sale_page() ) ) {
		return;
	}
	$dependencies = wp_style_is( 'nem-new-products', 'enqueued' ) ? array( 'nem-new-products' ) : array( 'nem-shop' );
	wp_enqueue_style( 'nem-catalog-controls', get_template_directory_uri() . '/assets/css/nem-catalog-controls.css', $dependencies, filemtime( get_template_directory() . '/assets/css/nem-catalog-controls.css' ) );
}, 102 );

function nem_new_products_url() {
	return home_url( '/san-pham-moi/' );
}

function nem_new_product_categories() {
	return array(
		'dam' => array( 'label' => 'Đầm', 'children' => array( 'dam-suong' => 'Đầm suông', 'dam-dang-a' => 'Đầm dáng A', 'dam-om' => 'Đầm ôm', 'dam-maxi' => 'Đầm maxi' ) ),
		'ao' => array( 'label' => 'Áo', 'children' => array( 'ao-ngan-tay' => 'Ngắn tay', 'ao-tay-lo' => 'Tay lỡ', 'ao-dai-tay' => 'Dài tay' ) ),
		'quan' => array( 'label' => 'Quần', 'children' => array( 'quan-short' => 'Quần short', 'quan-lung' => 'Quần lửng', 'quan-dai' => 'Quần dài', 'quan-jeans' => 'Quần jeans' ) ),
		'chan-vay' => array( 'label' => 'Chân váy', 'children' => array( 'chan-vay-xep-li' => 'Chân váy xếp li', 'chan-vay-but-chi' => 'Chân váy bút chì', 'chan-vay-chu-a' => 'Chân váy chữ A' ) ),
		'set-bo' => array( 'label' => 'Set bộ', 'children' => array() ),
		'jumpsuit' => array( 'label' => 'Jumpsuit', 'children' => array() ),
		'ao-dai' => array( 'label' => 'Áo dài', 'children' => array() ),
		'ao-khoac' => array( 'label' => 'Áo khoác', 'children' => array( 'mang-to' => 'Măng tô', 'ao-vest' => 'Áo vest', 'ao-phao' => 'Áo phao' ) ),
	);
}

function nem_new_price_ranges() {
	return array(
		'under-500' => array( 'label' => 'Nhỏ hơn 500.000₫', 'min' => 0, 'max' => 499999 ),
		'500-1000' => array( 'label' => '500.000₫ – 1.000.000₫', 'min' => 500000, 'max' => 1000000 ),
		'1000-1500' => array( 'label' => '1.000.000₫ – 1.500.000₫', 'min' => 1000000, 'max' => 1500000 ),
		'1500-2000' => array( 'label' => '1.500.000₫ – 2.000.000₫', 'min' => 1500000, 'max' => 2000000 ),
		'2000-3000' => array( 'label' => '2.000.000₫ – 3.000.000₫', 'min' => 2000000, 'max' => 3000000 ),
		'over-3000' => array( 'label' => 'Lớn hơn 3.000.000₫', 'min' => 3000001, 'max' => null ),
	);
}

function nem_new_filter_state( $input = null ) {
	$input = null === $input ? wp_unslash( $_GET ) : $input;
	$scalar = function ( $key ) use ( $input ) { return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_title( $input[ $key ] ) : ''; };
	$state = array( 'danh_muc' => $scalar( 'danh_muc' ), 'gia' => $scalar( 'gia' ), 'cot' => '2' === $scalar( 'cot' ) ? 2 : 3, 'trang' => max( 1, min( 1000, absint( $scalar( 'trang' ) ) ) ) );
	$categories = array();
	foreach ( nem_new_product_categories() as $slug => $group ) { $categories[] = $slug; $categories = array_merge( $categories, array_keys( $group['children'] ) ); }
	if ( ! in_array( $state['danh_muc'], $categories, true ) ) { $state['danh_muc'] = ''; }
	if ( ! isset( nem_new_price_ranges()[ $state['gia'] ] ) ) { $state['gia'] = ''; }
	foreach ( array( 'kich_co', 'mau_sac' ) as $key ) {
		$values = $input[ $key ] ?? array();
		$values = is_array( $values ) ? array_slice( $values, 0, 30 ) : array( $values );
		$state[ $key ] = array_values( array_unique( array_filter( array_map( 'sanitize_title', array_filter( $values, 'is_string' ) ) ) ) );
	}
	return $state;
}

function nem_new_filter_url( $state, $changes = array() ) {
	$state = array_merge( $state, $changes );
	if ( 1 === (int) $state['trang'] ) { unset( $state['trang'] ); }
	return add_query_arg( array_filter( $state ), nem_new_products_url() );
}

function nem_new_query_args( $state ) {
	$visibility = wc_get_product_visibility_term_ids();
	$exclude = array( $visibility['exclude-from-catalog'] );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) { $exclude[] = $visibility['outofstock']; }
	$tax_query = array( 'relation' => 'AND', array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => $exclude, 'operator' => 'NOT IN' ) );
	if ( $state['danh_muc'] ) { $tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $state['danh_muc'], 'include_children' => true ); }
	foreach ( array( 'kich_co' => 'pa_kich-co', 'mau_sac' => 'pa_mau-sac' ) as $key => $taxonomy ) {
		if ( $state[ $key ] ) { $tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $state[ $key ], 'operator' => 'IN' ); }
	}
	$args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $state['trang'], 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'fields' => 'ids', 'tax_query' => $tax_query, 'ignore_sticky_posts' => true );
	$ranges = nem_new_price_ranges();
	if ( isset( $ranges[ $state['gia'] ] ) ) {
		$range = $ranges[ $state['gia'] ];
		$args['meta_query'] = array( array( 'key' => '_price', 'value' => $range['min'], 'compare' => '>=', 'type' => 'NUMERIC' ) );
		if ( null !== $range['max'] ) { $args['meta_query'][] = array( 'key' => '_price', 'value' => $range['max'], 'compare' => '<=', 'type' => 'NUMERIC' ); }
	}
	return $args;
}

add_filter( 'template_include', function ( $template ) {
	if ( nem_is_new_products_page() && function_exists( 'WC' ) ) { return get_template_directory() . '/page-san-pham-moi.php'; }
	return $template;
}, 100 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! nem_is_new_products_page() ) { return; }
	$dir = get_template_directory(); $uri = get_template_directory_uri();
	wp_enqueue_style( 'nem-collections', $uri . '/assets/css/nem-collections.css', array( 'nem-header-footer-style' ), filemtime( $dir . '/assets/css/nem-collections.css' ) );
	wp_enqueue_style( 'nem-new-products', $uri . '/assets/css/nem-new-products.css', array( 'nem-collections' ), filemtime( $dir . '/assets/css/nem-new-products.css' ) );
	wp_enqueue_script( 'nem-new-products', $uri . '/assets/js/nem-new-products.js', array(), filemtime( $dir . '/assets/js/nem-new-products.js' ), true );
}, 101 );

add_filter( 'body_class', function ( $classes ) {
	if ( nem_is_new_products_page() ) { $classes[] = 'nem-new-products-page'; }
	return $classes;
} );

add_action( 'wp', function () {
	if ( nem_is_new_products_page() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}
} );

add_action( 'storefront_before_footer', function () {
	if ( nem_is_new_products_page() ) { get_template_part( 'template-parts/collection-newsletter', null, array( 'return_to' => 'san-pham-moi' ) ); }
} );
