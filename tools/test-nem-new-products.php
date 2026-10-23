<?php
/** Read-only checks: php tools/test-nem-new-products.php */
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__ ) . '/wp-load.php';
$checks = 0;
function nem_new_assert( $condition, $message ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
}
$page = get_page_by_path( 'san-pham-moi' );
nem_new_assert( $page && 'publish' === $page->post_status, 'Published new arrivals page' );
nem_new_assert( 'page-san-pham-moi.php' === get_post_meta( $page->ID, '_wp_page_template', true ), 'New arrivals template assigned' );
$manifest = json_decode( file_get_contents( __DIR__ . '/nem-new-products-assets.json' ), true );
$new_ids = array();
foreach ( $manifest['products'] as $item ) {
	$id = wc_get_product_id_by_sku( 'NEM-' . $item['sku'] ) ?: wc_get_product_id_by_sku( $item['sku'] );
	$product = $id ? wc_get_product( $id ) : false;
	nem_new_assert( $product && $product->is_type( 'variable' ), 'Variable product exists: ' . $item['sku'] );
	$new_ids[] = $id;
	nem_new_assert( count( $product->get_children() ) === count( $item['variants'] ), 'Source size/color variants preserved: ' . $item['sku'] );
	nem_new_assert( (float) $product->get_price() === (float) $item['price'], 'Source product price: ' . $item['sku'] );
	nem_new_assert( is_file( get_attached_file( $product->get_image_id() ) ), 'Primary image: ' . $item['sku'] );
	$gallery = $product->get_gallery_image_ids();
	nem_new_assert( $gallery && is_file( get_attached_file( $gallery[0] ) ), 'Alternate hover image: ' . $item['sku'] );
	ob_start();
	get_template_part( 'template-parts/collection-product', null, array( 'product' => $product, 'sku' => $item['sku'] ) );
	$card = ob_get_clean();
	nem_new_assert( str_contains( $card, 'class="nem-collection-image-primary" src="' . esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'large' ) ) . '"' ), 'Rendered card uses real main photo without collection-specific metadata' );
	nem_new_assert( str_contains( $card, 'class="nem-collection-image-secondary" src="' . esc_url( wp_get_attachment_image_url( $gallery[0], 'large' ) ) . '"' ), 'Rendered card uses real gallery photo' );
	foreach ( $item['variants'] as $source ) {
		nem_new_assert( has_term( sanitize_title( $source['size'] ), 'pa_kich-co', $id ), 'Size taxonomy matches source' );
		nem_new_assert( has_term( sanitize_title( $source['color'] ), 'pa_mau-sac', $id ), 'Color taxonomy matches source' );
	}
}
$state = nem_new_filter_state( array() );
$first = new WP_Query( nem_new_query_args( $state ) );
nem_new_assert( 12 === count( $first->posts ), '12 products per page' );
nem_new_assert( count( array_unique( $first->posts ) ) === count( $first->posts ), 'No duplicate cards on page' );
nem_new_assert( $first->max_num_pages === (int) ceil( $first->found_posts / 12 ), 'Pagination uses real product count' );
$second_state = array_merge( $state, array( 'trang' => 2 ) );
$second = new WP_Query( nem_new_query_args( $second_state ) );
nem_new_assert( $second->posts && ! array_intersect( $first->posts, $second->posts ), 'Second page has different products' );

$filtered = nem_new_filter_state( array( 'danh_muc' => 'chan-vay', 'kich_co' => array( 'size-4' ), 'mau_sac' => array( 'nau' ), 'gia' => '500-1000' ) );
$query = new WP_Query( nem_new_query_args( $filtered ) );
nem_new_assert( in_array( $new_ids[0], $query->posts, true ), 'Combined category, size, color and price finds brown skirt' );
foreach ( $query->posts as $id ) {
	$p = wc_get_product( $id );
	nem_new_assert( has_term( 'nau', 'pa_mau-sac', $id ) && has_term( 'size-4', 'pa_kich-co', $id ), 'Combined filters do not include unrelated variants' );
	nem_new_assert( (float) $p->get_price() >= 500000 && (float) $p->get_price() <= 1000000, 'Price range respected' );
}
$none = new WP_Query( nem_new_query_args( array_merge( $filtered, array( 'mau_sac' => array( 'nonexistent-color' ) ) ) ) );
nem_new_assert( ! $none->posts, 'No-match combination returns empty state' );
$size_query = new WP_Query( nem_new_query_args( nem_new_filter_state( array( 'kich_co' => array( 'size-4', 'size-6' ) ) ) ) );
nem_new_assert( $size_query->found_posts > 0, 'Multiple sizes supported' );
foreach ( $size_query->posts as $id ) { nem_new_assert( has_term( array( 'size-4', 'size-6' ), 'pa_kich-co', $id ), 'Multiple size selections use OR within size filter' ); }
nem_new_assert( has_term( 'chan-vay-xep-li', 'product_cat', $new_ids[4] ), 'Pleated skirt source tag maps to sidebar category' );
nem_new_assert( has_term( 'dam-dang-a', 'product_cat', $new_ids[2] ), 'Flared dress source tag maps to dress category' );
$trousers = new WP_Query( nem_new_query_args( nem_new_filter_state( array( 'danh_muc' => 'quan' ) ) ) );
nem_new_assert( $trousers->found_posts > 0, 'Existing trousers participate in category browsing' );
foreach ( nem_new_price_ranges() as $key => $range ) {
	$result = new WP_Query( nem_new_query_args( nem_new_filter_state( array( 'gia' => $key ) ) ) );
	foreach ( $result->posts as $id ) { $price = (float) wc_get_product( $id )->get_price(); nem_new_assert( $price >= $range['min'] && ( null === $range['max'] || $price <= $range['max'] ), 'Price preset ' . $key ); }
}
$url = nem_new_filter_url( $filtered, array( 'cot' => 2, 'trang' => 2 ) );
parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $url_state );
nem_new_assert( '2' === $url_state['cot'] && '2' === $url_state['trang'], 'Grid and page links preserve selected values' );
nem_new_assert( $url_state['kich_co'] === $filtered['kich_co'] && $url_state['mau_sac'] === $filtered['mau_sac'] && $url_state['gia'] === $filtered['gia'], 'Filters retained in pagination/view URLs' );
$malformed = nem_new_filter_state( array( 'danh_muc' => array( 'bad' ), 'gia' => array( 'bad' ), 'cot' => array(), 'trang' => array(), 'kich_co' => array( array( 'bad' ), 'size-4', 'size-4' ) ) );
nem_new_assert( '' === $malformed['danh_muc'] && '' === $malformed['gia'] && 3 === $malformed['cot'] && 1 === $malformed['trang'] && array( 'size-4' ) === $malformed['kich_co'], 'Malformed and repeated inputs handled safely' );
echo 'PASS: ' . $checks . ' new arrivals integration checks.' . PHP_EOL;
