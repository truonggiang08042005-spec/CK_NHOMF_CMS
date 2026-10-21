<?php
/** Read-only integration checks against the current WordPress database. */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__ ) . '/wp-load.php';
$checks = 0;
function nem_collection_assert( $condition, $message ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
}
$page = get_page_by_path( 'bo-suu-tap' );
nem_collection_assert( $page && 'publish' === $page->post_status, 'Published collection page exists' );
nem_collection_assert( 'page-bo-suu-tap.php' === get_post_meta( $page->ID, '_wp_page_template', true ), 'Correct page template' );
$old_get = $_GET;
try {
	foreach ( nem_collections_catalog() as $slug => $collection ) {
		$expected = array();
		foreach ( array_merge( array( $collection['banner'] ), $collection['editorials'] ) as $image ) {
			$path = get_template_directory() . '/assets/images/nem/collections/' . pathinfo( $image, PATHINFO_FILENAME ) . '.webp';
			nem_collection_assert( is_file( $path ) && getimagesize( $path ), 'Banner/editorial image exists: ' . $image );
		}
		foreach ( $collection['skus'] as $sku ) {
			$product = nem_collection_product( $sku );
			nem_collection_assert( $product && $product->get_id(), 'Published product exists: ' . $sku );
			$expected[] = $product->get_id();
			$images = (array) $product->get_meta( '_nem_collection_images' );
			nem_collection_assert( 2 === count( $images ) && $images[0] !== $images[1], 'Separate hover photograph: ' . $sku );
			foreach ( $images as $image ) {
				$path = get_template_directory() . '/assets/images/nem/collections/' . $image;
				nem_collection_assert( is_file( $path ) && getimagesize( $path ), 'Local product image exists: ' . $image );
			}
			nem_collection_assert( is_file( get_attached_file( $product->get_image_id() ) ), 'Detail page main image exists: ' . $sku );
			ob_start();
			get_template_part( 'template-parts/collection-product', null, array( 'sku' => $sku, 'product' => $product ) );
			$card = ob_get_clean();
			nem_collection_assert( str_contains( $card, esc_url( $product->get_permalink() ) ), 'Detail button links to WooCommerce: ' . $sku );
			nem_collection_assert( str_contains( $card, 'nem-collection-image-secondary' ) && str_contains( $card, 'XEM THÊM' ), 'Hover card markup: ' . $sku );
		}
		$_GET['collection'] = $slug;
		$query = new WP_Query();
		$query->set( 'post_type', 'product' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', -1 );
		$query->set( 'fields', 'ids' );
		do_action( 'woocommerce_product_query', $query );
		$actual = array_map( 'intval', $query->get_posts() );
		sort( $actual ); sort( $expected );
		nem_collection_assert( $actual === $expected, 'Shop filter returns only matching collection: ' . $slug );
	}
	$_GET['collection'] = array( 'invalid' );
	$query = new WP_Query();
	do_action( 'woocommerce_product_query', $query );
	nem_collection_assert( ! $query->get( 'tax_query' ), 'Malformed filter is ignored safely' );
} finally {
	$_GET = $old_get;
}
echo 'PASS: ' . $checks . ' collection integration checks.' . PHP_EOL;
