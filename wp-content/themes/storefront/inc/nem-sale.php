<?php
/**
 * NEM SALE catalog page with persistent filters and synchronized layout.
 */
defined( 'ABSPATH' ) || exit;

function nem_is_sale_page() {
	return is_page( 'sale' ) || is_page_template( 'page-sale.php' ) || is_page( 'san-pham-sale' ) ||
		( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['on_sale'] ) && '1' === (string) $_GET['on_sale'] ) ||
		( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['sale'] ) && '' !== $_GET['sale'] );
}

function nem_sale_products_url() {
	return home_url( '/sale/' );
}

function nem_sale_filter_state( $input = null ) {
	$input = null === $input ? wp_unslash( $_GET ) : $input;
	$scalar = function ( $key ) use ( $input ) {
		return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_title( $input[ $key ] ) : '';
	};
	$state = array(
		'danh_muc' => $scalar( 'danh_muc' ),
		'gia'      => $scalar( 'gia' ),
		'cot'      => '2' === $scalar( 'cot' ) ? 2 : 3,
		'trang'    => max( 1, min( 1000, absint( $scalar( 'trang' ) ) ) ),
		'sale'     => $scalar( 'sale' ),
	);

	if ( function_exists( 'nem_new_product_categories' ) ) {
		$categories = array();
		foreach ( nem_new_product_categories() as $slug => $group ) {
			$categories[] = $slug;
			$categories   = array_merge( $categories, array_keys( $group['children'] ) );
		}
		if ( ! in_array( $state['danh_muc'], $categories, true ) ) {
			$state['danh_muc'] = '';
		}
	}

	if ( function_exists( 'nem_new_price_ranges' ) && ! isset( nem_new_price_ranges()[ $state['gia'] ] ) ) {
		$state['gia'] = '';
	}

	if ( ! in_array( $state['sale'], array( '199k', '70' ), true ) ) {
		$state['sale'] = '';
	}

	foreach ( array( 'kich_co', 'mau_sac' ) as $key ) {
		$values        = $input[ $key ] ?? array();
		$values        = is_array( $values ) ? array_slice( $values, 0, 30 ) : array( $values );
		$state[ $key ] = array_values( array_unique( array_filter( array_map( 'sanitize_title', array_filter( $values, 'is_string' ) ) ) ) );
	}

	return $state;
}

function nem_sale_filter_url( $state, $changes = array() ) {
	$state = array_merge( $state, $changes );
	if ( 1 === (int) $state['trang'] ) {
		unset( $state['trang'] );
	}
	return add_query_arg( array_filter( $state ), nem_sale_products_url() );
}

function nem_sale_query_args( $state ) {
	$visibility = wc_get_product_visibility_term_ids();
	$exclude    = array( $visibility['exclude-from-catalog'] );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$exclude[] = $visibility['outofstock'];
	}

	$on_sale_ids = wc_get_product_ids_on_sale();
	if ( empty( $on_sale_ids ) ) {
		$on_sale_ids = array( 0 );
	}

	// Filter by sale parameter if present (199k or 70%)
	if ( '70' === $state['sale'] ) {
		$sale70_ids = array();
		foreach ( $on_sale_ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$reg = (float) $p->get_regular_price();
			$sal = (float) $p->get_price();
			if ( $reg > 0 && round( ( ( $reg - $sal ) / $reg ) * 100 ) >= 70 ) {
				$sale70_ids[] = $id;
			}
		}
		$on_sale_ids = ! empty( $sale70_ids ) ? $sale70_ids : array( 0 );
	} elseif ( '199k' === $state['sale'] ) {
		$sale199_ids = array();
		foreach ( $on_sale_ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$sal = (float) $p->get_price();
			if ( $sal <= 300000 ) {
				$sale199_ids[] = $id;
			}
		}
		$on_sale_ids = ! empty( $sale199_ids ) ? $sale199_ids : array( 0 );
	}

	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => $exclude,
			'operator' => 'NOT IN',
		),
	);

	// Subcategory filter if chosen by user
	if ( ! empty( $state['danh_muc'] ) ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $state['danh_muc'],
			'include_children' => true,
		);
	}

	foreach ( array( 'kich_co' => 'pa_kich-co', 'mau_sac' => 'pa_mau-sac' ) as $key => $taxonomy ) {
		if ( ! empty( $state[ $key ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $state[ $key ],
				'operator' => 'IN',
			);
		}
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => $state['trang'],
		'post__in'            => $on_sale_ids,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
			'ID'         => 'DESC',
		),
		'fields'              => 'ids',
		'tax_query'           => $tax_query,
		'ignore_sticky_posts' => true,
	);

	if ( function_exists( 'nem_new_price_ranges' ) ) {
		$ranges = nem_new_price_ranges();
		if ( isset( $ranges[ $state['gia'] ] ) ) {
			$range = $ranges[ $state['gia'] ];
			$args['meta_query'] = array(
				array(
					'key'     => '_price',
					'value'   => $range['min'],
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			);
			if ( null !== $range['max'] ) {
				$args['meta_query'][] = array(
					'key'     => '_price',
					'value'   => $range['max'],
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
		}
	}

	return $args;
}

add_action( 'template_redirect', function () {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		if ( isset( $_GET['on_sale'] ) && '1' === (string) $_GET['on_sale'] ) {
			wp_safe_redirect( nem_sale_products_url(), 301 );
			exit;
		}
		if ( isset( $_GET['sale'] ) && in_array( $_GET['sale'], array( '199k', '70' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'sale', sanitize_title( $_GET['sale'] ), nem_sale_products_url() ), 301 );
			exit;
		}
	}
} );

add_filter( 'template_include', function ( $template ) {
	if ( nem_is_sale_page() && function_exists( 'WC' ) ) {
		return get_template_directory() . '/page-sale.php';
	}
	return $template;
}, 100 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! nem_is_sale_page() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'nem-collections', $uri . '/assets/css/nem-collections.css', array( 'nem-header-footer-style' ), filemtime( $dir . '/assets/css/nem-collections.css' ) );
	wp_enqueue_style( 'nem-new-products', $uri . '/assets/css/nem-new-products.css', array( 'nem-collections' ), filemtime( $dir . '/assets/css/nem-new-products.css' ) );
	wp_enqueue_script( 'nem-new-products', $uri . '/assets/js/nem-new-products.js', array(), filemtime( $dir . '/assets/js/nem-new-products.js' ), true );
}, 101 );

add_filter( 'body_class', function ( $classes ) {
	if ( nem_is_sale_page() ) {
		$classes[] = 'nem-new-products-page';
		$classes[] = 'nem-sale-page';
	}
	return $classes;
} );

add_action( 'wp', function () {
	if ( nem_is_sale_page() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}
} );

add_action( 'storefront_before_footer', function () {
	if ( nem_is_sale_page() ) {
		get_template_part( 'template-parts/collection-newsletter', null, array( 'return_to' => 'sale' ) );
	}
} );
