<?php
/**
 * NEM Custom Header & Footer Setup
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue NEM styles and scripts.
 */
function nem_enqueue_assets() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    // NEM Header & Footer Styles
    wp_enqueue_style(
        'nem-header-footer-style',
        $theme_uri . '/assets/css/nem-header-footer.css',
        array(),
        filemtime( $theme_dir . '/assets/css/nem-header-footer.css' )
    );

    // NEM Header & Footer Script
    wp_enqueue_script(
        'nem-header-footer-script',
        $theme_uri . '/assets/js/nem-header-footer.js',
        array( 'jquery' ),
        filemtime( $theme_dir . '/assets/js/nem-header-footer.js' ),
        true
    );

    wp_localize_script( 'nem-header-footer-script', 'nemAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'nem_enqueue_assets', 99 );

/**
 * Check whether the current search request targets WooCommerce products.
 */
function nem_is_product_search() {
	$post_type = get_query_var( 'post_type' );

	return is_search() && (
		'product' === $post_type ||
		( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
	);
}

function nem_is_shop_archive() {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );
}

function nem_shop_selected_category_filters() {
	if ( isset( $_GET['nem_category'] ) ) {
		$raw      = wp_unslash( $_GET['nem_category'] );
		$values   = is_array( $raw ) ? $raw : array( $raw );
		$selected = array();

		foreach ( $values as $value ) {
			if ( is_scalar( $value ) && ctype_digit( (string) $value ) ) {
				$term_id = absint( $value );
				if ( $term_id && term_exists( $term_id, 'product_cat' ) ) {
					$selected[] = $term_id;
				}
			}
		}

		return array_values( array_unique( $selected ) );
	}

	$queried = get_queried_object();
	return $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy
		? array( (int) $queried->term_id )
		: array();
}

function nem_shop_custom_attribute_values( $filter_key ) {
	static $values_by_key = null;

	if ( null === $values_by_key ) {
		$values_by_key = array(
			'size'  => array(),
			'color' => array(),
		);
		$products      = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'meta_query'             => array(
					array(
						'key'     => '_product_attributes',
						'compare' => 'EXISTS',
					),
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $products as $product_id ) {
			$attributes = get_post_meta( $product_id, '_product_attributes', true );
			if ( ! is_array( $attributes ) ) {
				continue;
			}

			foreach ( $attributes as $attribute ) {
				if ( ! is_array( $attribute ) || ! empty( $attribute['is_taxonomy'] ) ) {
					continue;
				}

				$name = remove_accents( strtolower( sanitize_title( $attribute['name'] ?? '' ) ) );
				$key  = preg_match( '/^(size|kich-co|kichco)$/', $name )
					? 'size'
					: ( preg_match( '/^(color|colour|mau-sac|mausac)$/', $name ) ? 'color' : null );

				if ( null === $key || ! isset( $attribute['value'] ) || ! is_scalar( $attribute['value'] ) ) {
					continue;
				}

				foreach ( explode( '|', (string) $attribute['value'] ) as $value ) {
					$value = trim( $value );
					$slug  = sanitize_title( $value );
					if ( '' !== $value && '' !== $slug ) {
						$values_by_key[ $key ][ $slug ] = $value;
					}
				}
			}
		}

		foreach ( $values_by_key as &$values ) {
			uasort( $values, 'strnatcasecmp' );
		}
		unset( $values );
	}

	return $values_by_key[ $filter_key ] ?? array();
}

function nem_shop_filter_categories( $query ) {
	if ( ! nem_is_shop_archive() ) {
		return;
	}

	$categories = nem_shop_selected_category_filters();
	if ( $categories ) {
		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => $categories,
			'operator'         => 'IN',
			'include_children' => true,
		);
		$query->set( 'tax_query', $tax_query );
	}

	$custom_attribute_filters = array();
	foreach ( array( 'size', 'color' ) as $filter_key ) {
		$parameter = 'nem_attribute_' . $filter_key;
		if ( ! isset( $_GET[ $parameter ] ) || ! is_scalar( $_GET[ $parameter ] ) ) {
			continue;
		}

		$values        = nem_shop_custom_attribute_values( $filter_key );
		$selected_slugs = array_filter(
			array_map(
				'sanitize_title',
				explode( ',', sanitize_text_field( wp_unslash( $_GET[ $parameter ] ) ) )
			)
		);
		$value_queries = array();

		foreach ( $selected_slugs as $slug ) {
			if ( ! isset( $values[ $slug ] ) ) {
				continue;
			}

			$attribute_pattern = preg_quote( $filter_key, '/' );
			$value_pattern     = preg_quote( $values[ $slug ], '/' );
			$value_queries[]   = array(
				'key'     => '_product_attributes',
				'value'   => '"' . $attribute_pattern . '";a:[^}]*"value";s:[0-9]+:"([^"]*[|][[:space:]]*)?' . $value_pattern . '([[:space:]]*[|][^"]*)?"',
				'compare' => 'REGEXP',
			);
		}

		if ( $value_queries ) {
			$custom_attribute_filters[] = array_merge(
				array( 'relation' => 'OR' ),
				$value_queries
			);
		}
	}

	if ( $custom_attribute_filters ) {
		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array_merge(
			array( 'relation' => 'AND' ),
			$custom_attribute_filters
		);
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'woocommerce_product_query', 'nem_shop_filter_categories' );

function nem_shop_archive_setup() {
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}

	if ( ! nem_is_shop_archive() ) {
		return;
	}

	remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
	remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31 );
}
add_action( 'wp', 'nem_shop_archive_setup', 20 );

function nem_shop_newsletter() {
	if ( nem_is_shop_archive() ) {
		get_template_part( 'template-parts/collection-newsletter' );
	}
}
add_action( 'storefront_before_footer', 'nem_shop_newsletter', 10 );

function nem_shop_breadcrumbs( $crumbs ) {
	if ( ! is_shop() || ! $crumbs ) {
		return $crumbs;
	}

	$crumbs[0][0] = 'TRANG CHỦ';
	$last         = count( $crumbs ) - 1;
	$crumbs[ $last ][0] = 'TẤT CẢ SẢN PHẨM';
	return $crumbs;
}
add_filter( 'woocommerce_get_breadcrumb', 'nem_shop_breadcrumbs' );

function nem_product_search_template( $template ) {
	if ( ! nem_is_product_search() ) {
		return $template;
	}

	$search_template = get_template_directory() . '/search.php';
	return is_readable( $search_template ) ? $search_template : $template;
}
add_filter( 'template_include', 'nem_product_search_template', 99 );

function nem_enqueue_shop_assets() {
	if ( ! nem_is_shop_archive() && ! nem_is_product_search() ) {
		return;
	}

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$shop_css  = $theme_dir . '/assets/css/nem-shop.css';
	$shop_js   = $theme_dir . '/assets/js/nem-shop.js';

	wp_enqueue_style(
		'nem-shop',
		$theme_uri . '/assets/css/nem-shop.css',
		array( 'storefront-style', 'nem-collections' ),
		filemtime( $shop_css )
	);
	wp_enqueue_script(
		'nem-shop',
		$theme_uri . '/assets/js/nem-shop.js',
		array(),
		filemtime( $shop_js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nem_enqueue_shop_assets', 101 );

/**
 * Update WooCommerce Cart Fragment dynamically.
 */
function nem_cart_fragments( $fragments ) {
    $count = ( function_exists( 'WC' ) && is_object( WC()->cart ) ) ? WC()->cart->get_cart_contents_count() : 0;
    $fragments['.nem-cart-count'] = '<span class="nem-cart-badge nem-cart-count">' . esc_html( $count ) . '</span>';
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'nem_cart_fragments' );

/**
 * Clean up default Storefront header and footer elements.
 */
function nem_clean_storefront_defaults() {
    // Header cleanups
    remove_action( 'storefront_header', 'storefront_header_container', 0 );
    remove_action( 'storefront_header', 'storefront_skip_links', 5 );
    remove_action( 'storefront_header', 'storefront_social_icons', 10 );
    remove_action( 'storefront_header', 'storefront_site_branding', 20 );
    remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
    remove_action( 'storefront_header', 'storefront_product_search', 40 );
    remove_action( 'storefront_header', 'storefront_header_container_close', 41 );
    remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper', 42 );
    remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
    remove_action( 'storefront_header', 'storefront_header_cart', 60 );
    remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper_close', 68 );

    // Footer cleanups
    remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
    remove_action( 'storefront_footer', 'storefront_credit', 20 );
    remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
}
add_action( 'wp_loaded', 'nem_clean_storefront_defaults' );

/**
 * Tweaks for WooCommerce Account page layout.
 */
function nem_account_page_tweaks() {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
        remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
    }
}
add_action( 'wp', 'nem_account_page_tweaks' );

/**
 * Format sale price: sale price on the left, regular (old) price on the right.
 */
add_filter( 'woocommerce_format_sale_price', function ( $price, $regular_price, $sale_price ) {
    $formatted_reg  = is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price;
    $formatted_sale = is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price;
    return '<ins>' . $formatted_sale . '</ins> <del aria-hidden="true">' . $formatted_reg . '</del>';
}, 10, 3 );

