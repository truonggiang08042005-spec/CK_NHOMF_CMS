<?php
/** php -d memory_limit=512M tools/setup-nem-new-products.php */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_raise_memory_limit( 'image' );
$manifest = json_decode( file_get_contents( __DIR__ . '/nem-new-products-assets.json' ), true, 512, JSON_THROW_ON_ERROR );

function nem_new_import_image( $url, $filename ) {
	if ( ! in_array( wp_parse_url( $url, PHP_URL_HOST ), array( 'cdn.hstatic.net', 'theme.hstatic.net' ), true ) ) { throw new RuntimeException( 'Unexpected source host.' ); }
	$folder = get_template_directory() . '/assets/images/nem/new-products/';
	wp_mkdir_p( $folder );
	$file = $folder . basename( $filename );
	if ( is_file( $file ) && getimagesize( $file ) ) { return $file; }
	$response = wp_remote_get( $url, array( 'timeout' => 60 ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { throw new RuntimeException( 'Image download failed: ' . $filename ); }
	$raw = wp_remote_retrieve_body( $response );
	if ( ! getimagesizefromstring( $raw ) ) { throw new RuntimeException( 'Invalid image.' ); }
	$image = imagecreatefromstring( $raw );
	if ( imagesx( $image ) > 1920 ) { $image = imagescale( $image, 1920 ); }
	if ( ! imagewebp( $image, $file, 86 ) ) { throw new RuntimeException( 'Cannot write image.' ); }
	unset( $image, $raw );
	echo 'Image ' . $filename . PHP_EOL;
	return $file;
}

function nem_new_import_attachment( $source, $name ) {
	$uploads = wp_upload_dir(); $folder = $uploads['basedir'] . '/nem-new-products/'; wp_mkdir_p( $folder );
	$file = $folder . basename( $source ); $url = $uploads['baseurl'] . '/nem-new-products/' . basename( $source );
	$id = attachment_url_to_postid( $url );
	if ( $id && is_file( $file ) ) { return $id; }
	if ( ! copy( $source, $file ) ) { throw new RuntimeException( 'Cannot copy media.' ); }
	$id = wp_insert_attachment( array( 'post_title' => $name, 'post_mime_type' => 'image/webp', 'post_status' => 'inherit', 'guid' => $url ), $file, 0, true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $name );
	return $id;
}

function nem_new_import_term( $label, $slug, $taxonomy, $parent = 0 ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) { return $term; }
	$result = wp_insert_term( $label, $taxonomy, array( 'slug' => $slug, 'parent' => $parent ) );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	return get_term( $result['term_id'], $taxonomy );
}

function nem_new_import_category_ids( $item, $categories ) {
	$sku = $item['sku'];
	$parent = str_starts_with( $sku, 'AD' ) ? 'ao-dai' : ( str_starts_with( $sku, 'Z' ) ? 'chan-vay' : ( str_starts_with( $sku, 'D' ) ? 'dam' : ( str_starts_with( $sku, 'Q' ) ? 'quan' : 'ao' ) ) );
	$ids = array( $categories[ $parent ] );
	$aliases = array( 'dam-chu-a' => 'dam-dang-a', 'dam-xoe' => 'dam-dang-a', 'chan-vay-dang-a' => 'chan-vay-chu-a', 'chan-vay-xep-ly' => 'chan-vay-xep-li', 'ao-coc-tay' => 'ao-ngan-tay', 'ao-so-mi-coc-tay' => 'ao-ngan-tay', 'ao-so-mi-ngan-tay' => 'ao-ngan-tay', 'ao-so-mi-dai-tay' => 'ao-dai-tay' );
	foreach ( $item['tags'] ?? array() as $tag ) { $tag_slug = sanitize_title( trim( $tag ) ); $tag_slug = $aliases[ $tag_slug ] ?? $tag_slug; if ( isset( $categories[ $tag_slug ] ) ) { $ids[] = $categories[ $tag_slug ]; } }
	$name = mb_strtoupper( $item['name'], 'UTF-8' );
	if ( 'ao' === $parent && ( str_contains( $item['source'] ?? '', 'coc-tay' ) || str_contains( $name, 'NGẮN TAY' ) ) ) { $ids[] = $categories['ao-ngan-tay']; }
	if ( 'dam' === $parent && str_contains( $name, 'SUÔNG' ) ) { $ids[] = $categories['dam-suong']; }
	if ( 'quan' === $parent ) {
		if ( str_contains( $name, 'JEANS' ) ) { $ids[] = $categories['quan-jeans']; }
		elseif ( str_contains( $name, 'SHORT' ) ) { $ids[] = $categories['quan-short']; }
		elseif ( str_contains( $name, 'DÀI' ) || str_contains( $name, 'ỐNG SUÔNG' ) ) { $ids[] = $categories['quan-dai']; }
	}
	return array_values( array_unique( $ids ) );
}

$attribute_ids = array();
foreach ( array( 'kich-co' => 'Kích cỡ', 'mau-sac' => 'Màu sắc' ) as $name => $label ) {
	$id = wc_attribute_taxonomy_id_by_name( $name );
	if ( ! $id ) { $id = wc_create_attribute( array( 'name' => $label, 'slug' => $name, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) ); }
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$taxonomy = wc_attribute_taxonomy_name( $name );
	$attribute_ids[ $taxonomy ] = (int) $id;
	if ( ! taxonomy_exists( $taxonomy ) ) { register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'public' => false, 'rewrite' => false ) ); }
}

$categories = array();
foreach ( nem_new_product_categories() as $slug => $group ) {
	$term = nem_new_import_term( $group['label'], $slug, 'product_cat' ); $categories[ $slug ] = (int) $term->term_id;
	foreach ( $group['children'] as $child_slug => $label ) { $categories[ $child_slug ] = (int) nem_new_import_term( $label, $child_slug, 'product_cat', $term->term_id )->term_id; }
}

nem_new_import_image( $manifest['banner'], 'banner.webp' );
foreach ( $manifest['products'] as $index => $item ) {
	$sku = $item['sku']; $existing = wc_get_product_id_by_sku( 'NEM-' . $sku ) ?: wc_get_product_id_by_sku( $sku );
	$images = array();
	foreach ( $item['images'] as $image_index => $url ) { $images[] = nem_new_import_attachment( nem_new_import_image( $url, strtolower( $sku ) . '-' . ( $image_index + 1 ) . '.webp' ), $item['name'] ); }
	if ( $existing ) {
		$product = wc_get_product( $existing );
		if ( ! $product->get_image_id() || ! is_file( get_attached_file( $product->get_image_id() ) ) ) { $product->set_image_id( $images[0] ); $product->set_gallery_image_ids( array_slice( $images, 1 ) ); $product->save(); }
		wp_set_object_terms( $existing, nem_new_import_category_ids( $item, $categories ), 'product_cat', true );
		echo 'Kept existing ' . $sku . PHP_EOL; continue;
	}
	if ( empty( $item['variants'] ) ) { throw new RuntimeException( 'Missing source variants: ' . $sku ); }
	$options = array( 'pa_kich-co' => array(), 'pa_mau-sac' => array() );
	foreach ( $item['variants'] as $variant ) {
		foreach ( array( 'pa_kich-co' => 'size', 'pa_mau-sac' => 'color' ) as $taxonomy => $key ) {
			if ( '' === $variant[ $key ] ) { continue; }
			$term = nem_new_import_term( $variant[ $key ], sanitize_title( $variant[ $key ] ), $taxonomy ); $options[ $taxonomy ][ $term->slug ] = (int) $term->term_id;
		}
	}
	$attributes = array();
	foreach ( $options as $taxonomy => $terms ) {
		if ( ! $terms ) { continue; }
		$attribute = new WC_Product_Attribute(); $attribute->set_id( $attribute_ids[ $taxonomy ] ); $attribute->set_name( $taxonomy ); $attribute->set_options( array_values( $terms ) ); $attribute->set_visible( true ); $attribute->set_variation( true ); $attributes[] = $attribute;
	}
	$product = new WC_Product_Variable(); $product->set_name( $item['name'] ); $product->set_sku( 'NEM-' . $sku ); $product->set_status( 'publish' ); $product->set_catalog_visibility( 'visible' );
	$product->set_image_id( $images[0] ); $product->set_gallery_image_ids( array_slice( $images, 1 ) ); $product->set_attributes( $attributes ); $product->set_date_created( time() - $index * 60 );
	$product->set_category_ids( nem_new_import_category_ids( $item, $categories ) ); $product->update_meta_data( '_nem_new_arrival_source', $item['source'] );
	$id = $product->save();
	foreach ( $item['variants'] as $source_variant ) {
		$variation_attributes = array();
		foreach ( array( 'pa_kich-co' => 'size', 'pa_mau-sac' => 'color' ) as $taxonomy => $key ) { if ( $source_variant[ $key ] ) { $variation_attributes[ $taxonomy ] = sanitize_title( $source_variant[ $key ] ); } }
		$variation = new WC_Product_Variation(); $variation->set_parent_id( $id ); $variation->set_status( 'publish' ); $variation->set_attributes( $variation_attributes ); $variation->set_regular_price( $source_variant['price'] ); $variation->set_manage_stock( false ); $variation->set_stock_status( $source_variant['available'] ? 'instock' : 'outofstock' ); $variation->save();
	}
	WC_Product_Variable::sync( $id ); wc_delete_product_transients( $id );
	echo 'Product ' . $sku . ' #' . $id . PHP_EOL;
}
// Append matching browse categories to existing catalog items, retaining their original categories.
foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) ) as $catalog_product ) {
	if ( ! preg_match( '/^(?:NEM-)?((?:AD|AK|AL|SM|D|Q|Z)[0-9]+)$/', $catalog_product->get_sku(), $sku_match ) ) { continue; }
	$category_item = array( 'sku' => $sku_match[1], 'name' => $catalog_product->get_name() );
	wp_set_object_terms( $catalog_product->get_id(), nem_new_import_category_ids( $category_item, $categories ), 'product_cat', true );
}
$page = get_page_by_path( 'san-pham-moi' );
$page_id = $page ? $page->ID : wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Sản phẩm mới', 'post_name' => 'san-pham-moi', 'post_status' => 'publish' ), true );
if ( is_wp_error( $page_id ) ) { throw new RuntimeException( $page_id->get_error_message() ); }
update_post_meta( $page_id, '_wp_page_template', 'page-san-pham-moi.php' );
echo 'Ready: ' . get_permalink( $page_id ) . PHP_EOL;
