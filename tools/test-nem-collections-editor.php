<?php
/** Read-only tests for editable collection stories. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';
$checks = 0;
function nem_collection_design_assert( $condition, $message ) {
	global $checks;
	if ( ! $condition ) throw new RuntimeException( $message );
	++$checks;
}
$design_page = get_page_by_path( 'bo-suu-tap' );
nem_collection_design_assert( $design_page && has_blocks( $design_page->post_content ), 'The collection design exists.' );
$original_content = $design_page->post_content;
$normalized = serialize_blocks( nem_collections_normalize_editor_blocks( parse_blocks( $original_content ) ) );
nem_collection_design_assert( $normalized === serialize_blocks( nem_collections_normalize_editor_blocks( parse_blocks( $normalized ) ) ), 'Normalizing the design twice does not change it again.' );
$inspect = function ( $blocks ) use ( &$inspect ) {
	foreach ( $blocks as $block ) {
		if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
			$file = get_attached_file( $block['attrs']['id'] );
			nem_collection_design_assert( $file && is_file( $file ), 'Editor image exists: ' . basename( $file ) );
			foreach ( wp_get_attachment_metadata( $block['attrs']['id'] )['sizes'] ?? [] as $size ) {
				nem_collection_design_assert( is_file( dirname( $file ) . '/' . $size['file'] ), 'Editor image thumbnail exists: ' . $size['file'] );
			}
		}
		if ( nem_collections_block_has_class( $block, 'nem-collection-newsletter' ) ) nem_collection_design_assert( 'section' === ( $block['attrs']['tagName'] ?? '' ), 'The newsletter group has the correct saved HTML tag.' );
		if ( nem_collections_block_has_class( $block, 'nem-collection-socials' ) ) nem_collection_design_assert( count( $block['innerBlocks'] ) === 3, 'Social links are three independently editable blocks.' );
		$inspect( $block['innerBlocks'] ?? [] );
	}
};
$inspect( parse_blocks( $normalized ) );
global $wp_query, $post;
$wp_query = new WP_Query( [ 'page_id' => $design_page->ID ] );
$post = clone $design_page;
$post->post_content = $normalized;
$wp_query->queried_object = $post;
$wp_query->queried_object_id = $post->ID;
$wp_query->in_the_loop = true;
setup_postdata( $post );
$_GET = [];
$baseline = apply_filters( 'the_content', $post->post_content );
preg_match_all( '/data-product-id="(\d+)"/', $baseline, $cards );
nem_collection_design_assert( count( $cards[1] ) === 8 && count( array_unique( $cards[1] ) ) === 8, 'Both collection stories render all eight unique product cards.' );
nem_collection_design_assert( substr_count( $baseline, 'nem-collection-image-secondary' ) === 8, 'All eight cards retain their collection hover photograph.' );
nem_collection_design_assert( substr_count( $baseline, 'name="email"' ) === 1 && str_contains( $baseline, 'nem_collection_nonce' ), 'The design contains one real newsletter form with nonce protection.' );
foreach ( nem_collections_catalog() as $slug => $collection ) {
	nem_collection_design_assert( str_contains( $baseline, esc_url( nem_collection_url( $slug ) ) ), 'Collection buttons keep the real shop filter: ' . $slug );
	foreach ( $collection['editorials'] as $filename ) nem_collection_design_assert( str_contains( $baseline, pathinfo( $filename, PATHINFO_FILENAME ) . '.webp' ), 'Editorial photo remains visible: ' . $filename );
}

// Native Gutenberg changes must be reflected without altering the database.
$blocks = parse_blocks( $normalized );
$edit = function ( $items ) use ( &$edit ) {
	foreach ( $items as &$block ) {
		if ( nem_collections_block_has_class( $block, 'nem-collections-container' ) ) {
			$indices = [];
			foreach ( $block['innerBlocks'] as $index => $child ) if ( nem_collections_block_has_class( $child, 'nem-collection-story' ) ) $indices[] = $index;
			if ( count( $indices ) === 2 ) {
				$temp = $block['innerBlocks'][ $indices[0] ];
				$block['innerBlocks'][ $indices[0] ] = $block['innerBlocks'][ $indices[1] ];
				$block['innerBlocks'][ $indices[1] ] = $temp;
			}
		}
		if ( nem_collections_block_has_class( $block, 'nem-collection-layout' ) || nem_collections_block_has_class( $block, 'nem-collection-products' ) ) $block['innerBlocks'] = array_reverse( $block['innerBlocks'] );
		$block['innerBlocks'] = $edit( $block['innerBlocks'] ?? [] );
	}
	unset( $block );
	return $items;
};
$post->post_content = serialize_blocks( $edit( $blocks ) );
$post->post_content = str_replace( 'banner_section_coll_1.webp', 'banner_section_coll_1.webp?design-test=1', $post->post_content );
$post->post_content = str_replace( 'Thời Nhã gợi lại', 'Nội dung đã chỉnh sửa', $post->post_content );
$post->post_content = str_replace( 'ĐĂNG KÝ</p>', 'NHẬN BẢN TIN</p>', $post->post_content );
$edited = apply_filters( 'the_content', $post->post_content );
nem_collection_design_assert( strpos( $edited, 'id="nhe-buoc-moi-ngay"' ) < strpos( $edited, 'id="thoi-nha"' ), 'Dragging collection sections changes their frontend order.' );
preg_match_all( '/data-product-id="(\d+)"/', $edited, $edited_cards );
nem_collection_design_assert( $edited_cards[1] === array_reverse( $cards[1] ), 'Dragging product cards changes their frontend order.' );
nem_collection_design_assert( str_contains( $edited, 'design-test=1' ) && str_contains( $edited, 'Nội dung đã chỉnh sửa' ), 'Edited banner and description are retained.' );
nem_collection_design_assert( str_contains( $edited, '<button type="submit">NHẬN BẢN TIN</button>' ), 'The functional newsletter button retains its edited label.' );

$post->post_content = $normalized;
$_GET = [ 'newsletter' => 'error' ];
$with_error = apply_filters( 'the_content', $post->post_content );
preg_match( '/<section\b[^>]*id="nem-collection-newsletter"[\s\S]*?<\/section>/', $with_error, $newsletter );
nem_collection_design_assert( isset( $newsletter[0] ) && str_contains( $newsletter[0], 'Vui lòng nhập email hợp lệ và thử lại.' ), 'Newsletter feedback stays inside the section.' );
nem_collection_design_assert( get_post_field( 'post_content', $design_page->ID ) === $original_content, 'The tests did not change the saved page.' );
echo "PASS: $checks collection editor integration checks.\n";
