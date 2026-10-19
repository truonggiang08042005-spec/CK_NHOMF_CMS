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

/**
 * Load the custom product search layout instead of WooCommerce's shop archive.
 */
function nem_product_search_template( $template ) {
	if ( nem_is_product_search() ) {
		$search_template = get_template_directory() . '/search.php';

		if ( file_exists( $search_template ) ) {
			return $search_template;
		}
	}

	return $template;
}
add_filter( 'template_include', 'nem_product_search_template', 99 );

/**
 * Enqueue the product search stylesheet only on product search pages.
 */
function nem_enqueue_product_search_styles() {
	if ( ! nem_is_product_search() ) {
		return;
	}

	$search_css = get_template_directory() . '/assets/css/search-results.css';

	if ( file_exists( $search_css ) ) {
		wp_enqueue_style(
			'nem-product-search',
			get_template_directory_uri() . '/assets/css/search-results.css',
			array( 'storefront-style', 'storefront-woocommerce-style' ),
			filemtime( $search_css )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nem_enqueue_product_search_styles', 100 );

/**
 * Show the sale percentage on product search results.
 */
function nem_product_search_sale_flash( $html, $post, $product ) {
	if ( ! nem_is_product_search() || ! ( $product instanceof WC_Product ) ) {
		return $html;
	}

	$discount_percentage = 0;

	if ( $product instanceof WC_Product_Variable ) {
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof WC_Product ) {
				continue;
			}

			$regular_price = (float) $variation->get_regular_price();
			$sale_price    = (float) $variation->get_sale_price();

			if ( $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price ) {
				$discount_percentage = max(
					$discount_percentage,
					round( ( $regular_price - $sale_price ) / $regular_price * 100 )
				);
			}
		}
	} else {
		$regular_price = (float) $product->get_regular_price();
		$sale_price    = (float) $product->get_sale_price();

		if ( $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price ) {
			$discount_percentage = round( ( $regular_price - $sale_price ) / $regular_price * 100 );
		}
	}

	if ( $discount_percentage < 1 ) {
		return $html;
	}

	return '<span class="onsale">-' . esc_html( $discount_percentage ) . '%</span>';
}
add_filter( 'woocommerce_sale_flash', 'nem_product_search_sale_flash', 10, 3 );

/**
 * Add the image overlay and view-more label to product search cards.
 */
function nem_product_search_image_overlay() {
	if ( ! nem_is_product_search() ) {
		return;
	}
	?>
	<span class="nem-search-image-overlay" aria-hidden="true">
		<span class="nem-search-view-more"><?php esc_html_e( 'Xem thêm', 'storefront' ); ?></span>
	</span>
	<?php
}
add_action( 'woocommerce_before_shop_loop_item_title', 'nem_product_search_image_overlay', 11 );

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
