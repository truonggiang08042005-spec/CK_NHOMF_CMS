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
