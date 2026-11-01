<?php
/** Read-only integration checks: no page/product changes are written to the database. */
if ( PHP_SAPI !== 'cli' ) exit;
require dirname( __DIR__ ) . '/wp-load.php';

$checks = 0;
function nem_design_assert( $condition, $message ) {
	global $checks;
	if ( ! $condition ) throw new RuntimeException( $message );
	++$checks;
}

$page = get_page_by_path( 'san-pham-moi' );
nem_design_assert( $page && has_blocks( $page->post_content ), 'The published page has a saved block design.' );
$original_content = $page->post_content;
$design_page_id = $page->ID;
global $wp_query, $post;
$wp_query = new WP_Query( [ 'page_id' => $page->ID ] );
$post = clone $page;
$wp_query->queried_object = $post;
$wp_query->queried_object_id = $post->ID;
$wp_query->in_the_loop = true;
setup_postdata( $post );
$_GET = [];

$baseline = apply_filters( 'the_content', $post->post_content );
// The editor and srcset use thumbnail variants even when the full banner loads.
$find_banner = function ( $blocks ) use ( &$find_banner ) {
	foreach ( $blocks as $block ) {
		if ( nem_new_block_has_class( $block, 'nem-new-banner' ) ) return $block['attrs']['id'] ?? 0;
		$id = $find_banner( $block['innerBlocks'] ?? [] );
		if ( $id ) return $id;
	}
	return 0;
};
$banner_id = $find_banner( parse_blocks( $original_content ) );
$banner_metadata = wp_get_attachment_metadata( $banner_id );
nem_design_assert( $banner_id && is_file( get_attached_file( $banner_id ) ), 'The editor banner has its original image file.' );
foreach ( $banner_metadata['sizes'] ?? [] as $name => $size ) {
	nem_design_assert( is_file( dirname( get_attached_file( $banner_id ) ) . '/' . $size['file'] ), 'The editor banner thumbnail exists: ' . $name );
}
preg_match_all( '/data-product-id="(\d+)"/', $baseline, $baseline_ids );
nem_design_assert( count( $baseline_ids[1] ) === 12, 'Saved design renders twelve product cards.' );
nem_design_assert( count( array_unique( $baseline_ids[1] ) ) === 12, 'Each product appears once.' );
nem_design_assert( str_contains( $baseline, 'name="kich_co[]"' ) && str_contains( $baseline, 'name="mau_sac[]"' ), 'Editable toolbar becomes real size/color filters.' );
nem_design_assert( str_contains( $baseline, 'name="gia"' ), 'Real price filter is available.' );
nem_design_assert( str_contains( $baseline, 'name="email"' ) && str_contains( $baseline, 'nem_collection_nonce' ), 'Saved newsletter design contains a working email form and nonce.' );
nem_design_assert( substr_count( $baseline, 'name="email"' ) === 1, 'Newsletter form is not duplicated.' );
nem_design_assert( str_contains( $baseline, 'nem-collection-image-secondary' ), 'Product hover gallery remains available.' );
nem_design_assert( str_contains( $baseline, 'danh_muc=mang-to' ), 'The saved Măng tô link uses the real taxonomy slug.' );

$_GET = [ 'newsletter' => 'error' ];
$newsletter_error = apply_filters( 'the_content', $post->post_content );
$newsletter_section = [];
preg_match( '/<section\b[^>]*id="nem-collection-newsletter"[\s\S]*?<\/section>/', $newsletter_error, $newsletter_section );
nem_design_assert( isset( $newsletter_section[0] ) && str_contains( $newsletter_section[0], 'Vui lòng nhập email hợp lệ và thử lại.' ), 'Newsletter feedback stays inside its section, as on the other pages.' );
nem_design_assert( strpos( $newsletter_section[0], 'nem-editor-newsletter-status' ) < strpos( $newsletter_section[0], 'nem-newsletter-socials-row' ), 'Newsletter feedback appears between the form and social links.' );
$_GET = [];

// Simulate Gutenberg's saved output in memory: move the sidebar, reorder cards,
// edit labels and replace a banner. Rendering must follow all of those edits.
$blocks = parse_blocks( $post->post_content );
$edit_blocks = function ( $items ) use ( &$edit_blocks ) {
	foreach ( $items as &$block ) {
		if ( nem_new_block_has_class( $block, 'nem-new-layout' ) ) $block['innerBlocks'] = array_reverse( $block['innerBlocks'] );
		if ( nem_new_block_has_class( $block, 'nem-new-products-row' ) ) {
			if ( count( $block['innerBlocks'] ) >= 2 ) {
				$temp = $block['innerBlocks'][0];
				$block['innerBlocks'][0] = $block['innerBlocks'][1];
				$block['innerBlocks'][1] = $temp;
			}
		}
		if ( nem_new_block_has_class( $block, 'nem-new-banner' ) ) {
			foreach ( $block['innerContent'] as &$part ) if ( is_string( $part ) ) $part = str_replace( 'banner.webp', 'banner.webp?design-check=1', $part );
			unset( $part );
			$block['innerHTML'] = str_replace( 'banner.webp', 'banner.webp?design-check=1', $block['innerHTML'] );
		}
		if ( nem_new_block_has_class( $block, 'nem-newsletter-submit-btn' ) ) {
			$block['innerHTML'] = str_replace( 'ĐĂNG KÝ', 'NHẬN TIN MỚI', $block['innerHTML'] );
			$block['innerContent'] = [ $block['innerHTML'] ];
		}
		$block['innerBlocks'] = $edit_blocks( $block['innerBlocks'] ?? [] );
	}
	unset( $block );
	return $items;
};
$post->post_content = serialize_blocks( $edit_blocks( $blocks ) );
$post->post_content = str_replace( 'SẢN PHẨM MỚI</h1>', 'THIẾT KẾ KIỂM TRA</h1>', $post->post_content );
$post->post_content = str_replace( 'Đầm suông</p>', 'Nhãn danh mục đã sửa</p>', $post->post_content );
$post->post_content = str_replace( '© 2020 - Bản quyền NEM', 'Chân trang đã sửa', $post->post_content );
$edited = apply_filters( 'the_content', $post->post_content );
nem_design_assert( str_contains( $edited, 'THIẾT KẾ KIỂM TRA' ), 'Edited heading is rendered.' );
nem_design_assert( str_contains( $edited, 'design-check=1' ), 'Replacement banner URL is rendered.' );
nem_design_assert( str_contains( $edited, 'Nhãn danh mục đã sửa</a>' ), 'Edited category label retains a working link.' );
nem_design_assert( strpos( $edited, 'wp-block-column nem-new-catalog' ) < strpos( $edited, 'wp-block-column nem-new-sidebar' ), 'Moving the sidebar to the right changes frontend column order.' );
preg_match_all( '/data-product-id="(\d+)"/', $edited, $edited_ids );
nem_design_assert( $edited_ids[1][0] === $baseline_ids[1][1] && $edited_ids[1][1] === $baseline_ids[1][0], 'Dragged product order is reflected on the frontend.' );
nem_design_assert( str_contains( $edited, '<button type="submit">NHẬN TIN MỚI</button>' ), 'Edited newsletter button label is retained.' );
nem_design_assert( str_contains( $edited, 'Chân trang đã sửa' ), 'Edited footer content is retained.' );

$post->post_content = $original_content;
$_GET = [ 'trang' => '2' ];
$second = apply_filters( 'the_content', $post->post_content );
preg_match_all( '/data-product-id="(\d+)"/', $second, $second_ids );
nem_design_assert( count( $second_ids[1] ) === 12 && ! array_intersect( $baseline_ids[1], $second_ids[1] ), 'Second page renders different real products without repeats.' );

$_GET = [ 'danh_muc' => 'chan-vay', 'gia' => '500-1000', 'kich_co' => [ 'size-4' ], 'mau_sac' => [ 'nau' ], 'cot' => '2' ];
$filtered = apply_filters( 'the_content', $post->post_content );
preg_match_all( '/data-product-id="(\d+)"/', $filtered, $filtered_ids );
nem_design_assert( count( $filtered_ids[1] ) > 0, 'Combined filters render matching products.' );
foreach ( $filtered_ids[1] as $id ) {
	$product = wc_get_product( $id );
	nem_design_assert( has_term( 'chan-vay', 'product_cat', $id ) || has_term( [ 'chan-vay-xep-li', 'chan-vay-but-chi', 'chan-vay-chu-a' ], 'product_cat', $id ), 'Filtered card matches the selected category.' );
	nem_design_assert( has_term( 'nau', 'pa_mau-sac', $id ) && has_term( 'size-4', 'pa_kich-co', $id ), 'Filtered card matches the selected size and color.' );
	nem_design_assert( $product->get_price() >= 500000 && $product->get_price() <= 1000000, 'Filtered card matches the selected price range.' );
}
nem_design_assert( str_contains( $filtered, 'data-columns="2"' ), 'Two-column view is applied to the saved grid.' );
nem_design_assert( str_contains( $filtered, 'danh_muc=dam-suong' ) && str_contains( $filtered, 'gia=500-1000' ), 'Category links retain the other selected filters.' );

$_GET = [ 'mau_sac' => [ 'nonexistent-color' ] ];
$empty = apply_filters( 'the_content', $post->post_content );
nem_design_assert( str_contains( $empty, 'Không tìm thấy sản phẩm phù hợp.' ) && ! str_contains( $empty, 'data-product-id=' ), 'An empty result removes stale saved product cards and shows a message.' );

$_GET = [ 'danh_muc' => 'ao-da' ];
$missing_category = apply_filters( 'the_content', $post->post_content );
nem_design_assert( str_contains( $missing_category, 'Không tìm thấy sản phẩm phù hợp.' ), 'A saved category without products shows an empty result instead of silently returning all products.' );

$_GET = [];
$post->post_content = 'Legacy content without blocks';
nem_design_assert( ! nem_new_uses_editor(), 'Pages without a block design retain the legacy template fallback.' );
nem_design_assert( get_post_field( 'post_content', $design_page_id ) === $original_content, 'The saved page was not modified by the tests.' );
echo "PASS: $checks new-arrivals design integration checks.\n";
