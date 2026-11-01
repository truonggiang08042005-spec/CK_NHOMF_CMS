<?php
/** Restore generated banner media after cloning/importing this site's database. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$design_page = get_page_by_path( 'san-pham-moi' );
if ( ! $design_page ) throw new RuntimeException( 'New-arrivals page not found.' );
$find_banner = function ( $blocks ) use ( &$find_banner ) {
	foreach ( $blocks as $block ) {
		if ( nem_new_block_has_class( $block, 'nem-new-banner' ) ) return absint( $block['attrs']['id'] ?? 0 );
		$id = $find_banner( $block['innerBlocks'] ?? [] );
		if ( $id ) return $id;
	}
	return 0;
};
$banner_id = $find_banner( parse_blocks( $design_page->post_content ) );
$banner_file = $banner_id ? get_attached_file( $banner_id ) : '';
$uploads = wp_upload_dir();
$expected_file = $uploads['basedir'] . '/nem-new-products/banner.webp';
if ( ! $banner_file || wp_normalize_path( $banner_file ) !== wp_normalize_path( $expected_file ) ) {
	echo "The design does not use the generated new-arrivals banner; no media changed.\n";
	exit;
}
if ( ! is_file( $banner_file ) ) {
	$source = get_template_directory() . '/assets/images/nem/new-products/banner.webp';
	if ( ! is_file( $source ) || ! wp_mkdir_p( dirname( $banner_file ) ) || ! copy( $source, $banner_file ) ) {
		throw new RuntimeException( 'Cannot restore the banner from the tracked theme asset.' );
	}
}
$metadata = wp_get_attachment_metadata( $banner_id );
$missing = empty( $metadata['sizes'] );
foreach ( $metadata['sizes'] ?? [] as $size ) {
	if ( ! is_file( dirname( $banner_file ) . '/' . $size['file'] ) ) $missing = true;
}
if ( ! $missing ) {
	echo "Banner original and thumbnails are complete; no metadata changed.\n";
	exit;
}
add_post_meta( $banner_id, '_nem_banner_metadata_before_repair', $metadata, true );
$new_metadata = wp_generate_attachment_metadata( $banner_id, $banner_file );
if ( ! is_array( $new_metadata ) || empty( $new_metadata['sizes'] ) ) throw new RuntimeException( 'Cannot generate banner thumbnails.' );
wp_update_attachment_metadata( $banner_id, $new_metadata );
echo "Banner restored with its editor and responsive image sizes.\n";
