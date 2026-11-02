<?php
/** Normalize imported collection blocks once; preserve all story content and media. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';
$design_page = get_page_by_path( 'bo-suu-tap' );
if ( ! $design_page || ! has_blocks( $design_page->post_content ) ) throw new RuntimeException( 'The collection block design was not found.' );
$normalized = serialize_blocks( nem_collections_normalize_editor_blocks( parse_blocks( $design_page->post_content ) ) );
if ( $normalized === $design_page->post_content ) {
	echo "Collection blocks are already normalized; no content changed.\n";
	exit;
}
add_post_meta( $design_page->ID, '_nem_collection_editor_original_content', $design_page->post_content, true );
$result = wp_update_post( wp_slash( [ 'ID' => $design_page->ID, 'post_content' => $normalized ] ), true );
if ( is_wp_error( $result ) ) throw new RuntimeException( $result->get_error_message() );
echo 'Normalized collection page #' . $design_page->ID . "; original content backed up in post metadata.\n";
