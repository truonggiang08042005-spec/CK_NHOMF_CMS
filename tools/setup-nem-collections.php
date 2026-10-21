<?php
/** Run once (and safely rerun): php tools/setup-nem-collections.php */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_raise_memory_limit( 'image' );
$manifest = json_decode( file_get_contents( __DIR__ . '/nem-collection-assets.json' ), true, 512, JSON_THROW_ON_ERROR );
$target = get_template_directory() . '/assets/images/nem/collections/';
wp_mkdir_p( $target );
function nem_setup_download( $url, $filename ) {
	global $target;
	if ( ! in_array( wp_parse_url( $url, PHP_URL_HOST ), array( 'cdn.hstatic.net', 'theme.hstatic.net' ), true ) ) { throw new RuntimeException( 'Unexpected asset host.' ); }
	$path = $target . pathinfo( basename( $filename ), PATHINFO_FILENAME ) . '.webp';
	if ( is_file( $path ) && getimagesize( $path ) ) { return $path; }
	$original = $target . basename( $filename );
	if ( is_file( $original ) ) {
		$bytes = file_get_contents( $original );
	} else {
		$response = wp_remote_get( $url, array( 'timeout' => 60 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { throw new RuntimeException( 'Unable to download ' . $filename ); }
		$bytes = wp_remote_retrieve_body( $response );
	}
	if ( ! getimagesizefromstring( $bytes ) ) { throw new RuntimeException( 'Invalid image: ' . $filename ); }
	$image = imagecreatefromstring( $bytes );
	if ( imagesx( $image ) > 1600 ) { $image = imagescale( $image, 1600 ); }
	if ( ! imagewebp( $image, $path, 86 ) ) { throw new RuntimeException( 'Cannot write ' . $filename ); }
	imagedestroy( $image );
	// Remove only original downloads created inside this asset directory.
	$resolved = realpath( $original );
	if ( $resolved && str_starts_with( $resolved, realpath( $target ) . DIRECTORY_SEPARATOR ) && $resolved !== realpath( $path ) ) { unlink( $resolved ); }
	echo 'Ready image ' . basename( $path ) . PHP_EOL;
	return $path;
}
function nem_setup_attachment( $source, $name ) {
	$uploads = wp_upload_dir();
	$directory = $uploads['basedir'] . '/nem-collections/';
	wp_mkdir_p( $directory );
	$file = $directory . basename( $source );
	$url = $uploads['baseurl'] . '/nem-collections/' . basename( $source );
	$id = attachment_url_to_postid( $url );
	if ( $id && is_file( $file ) ) { return $id; }
	if ( ! copy( $source, $file ) ) { throw new RuntimeException( 'Cannot copy image into media library.' ); }
	$id = wp_insert_attachment( array( 'post_mime_type' => wp_check_filetype( $file )['type'], 'post_title' => $name, 'post_status' => 'inherit', 'guid' => $url ), $file, 0, true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $name );
	return $id;
}
foreach ( $manifest['banners'] as $name => $url ) { nem_setup_download( $url, $name ); }
foreach ( $manifest['products'] as $item ) {
	$sku = $item['sku'];
	$images = array();
	$attachment_ids = array();
	foreach ( $item['images'] as $index => $url ) {
		$filename = strtolower( $sku ) . '-' . ( $index + 1 ) . '.' . pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
		$file = nem_setup_download( $url, $filename );
		$images[] = basename( $file );
		$attachment_ids[] = nem_setup_attachment( $file, $item['name'] );
	}
	$existing_id = wc_get_product_id_by_sku( 'NEM-' . $sku ) ?: wc_get_product_id_by_sku( $sku );
	$product = $existing_id ? wc_get_product( $existing_id ) : false;
	if ( ! $product ) {
		$product = new WC_Product_Simple();
		$product->set_name( $item['name'] );
		$product->set_sku( 'NEM-' . $sku );
		$product->set_status( 'publish' );
		$product->set_regular_price( $item['price'] );
		$product->set_stock_status( 'instock' );
	}
	$old_image = $product->get_image_id() ? get_attached_file( $product->get_image_id() ) : '';
	$managed = $old_image && str_contains( str_replace( '\\', '/', $old_image ), '/nem-collections/' );
	if ( ! $old_image || ! is_file( $old_image ) || $managed ) {
		$product->set_image_id( $attachment_ids[0] );
		$product->set_gallery_image_ids( array_slice( $attachment_ids, 1 ) );
	} elseif ( ! $product->get_gallery_image_ids() ) {
		$product->set_gallery_image_ids( array_slice( $attachment_ids, 1 ) );
	}
	$product->update_meta_data( '_nem_collection_images', $images );
	$id = $product->save();
	$slug = str_starts_with( $sku, 'AD' ) ? 'thoi-nha' : 'nhe-buoc-moi-ngay';
	$term = term_exists( 'bst-' . $slug, 'product_tag' );
	if ( ! $term ) { $term = wp_insert_term( 'thoi-nha' === $slug ? 'Áo dài THỜI NHÃ' : 'NHẸ BƯỚC MỖI NGÀY', 'product_tag', array( 'slug' => 'bst-' . $slug ) ); }
	if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
	wp_set_object_terms( $id, array( (int) $term['term_id'] ), 'product_tag', true );
	wc_delete_product_transients( $id );
	echo 'Ready product ' . $sku . ' (#' . $id . ')' . PHP_EOL;
}
$page = get_page_by_path( 'bo-suu-tap' );
if ( ! $page ) {
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Bộ sưu tập', 'post_name' => 'bo-suu-tap', 'post_status' => 'publish' ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
} else { $id = $page->ID; }
update_post_meta( $id, '_wp_page_template', 'page-bo-suu-tap.php' );
echo 'Ready collection page: ' . get_permalink( $id ) . PHP_EOL;
