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
    $theme_version = wp_get_theme()->get( 'Version' );

    // NEM Header & Footer Styles
    wp_enqueue_style(
        'nem-header-footer-style',
        get_template_directory_uri() . '/assets/css/nem-header-footer.css',
        array(),
        $theme_version
    );

    // NEM Header & Footer Script
    wp_enqueue_script(
        'nem-header-footer-script',
        get_template_directory_uri() . '/assets/js/nem-header-footer.js',
        array( 'jquery' ),
        $theme_version,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'nem_enqueue_assets', 99 );

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
