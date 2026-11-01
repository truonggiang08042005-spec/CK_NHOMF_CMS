<?php
/** Render the saved new-arrivals design, enhancing only its interactive controls. */
defined( 'ABSPATH' ) || exit;

function nem_new_uses_editor() {
	$page = get_queried_object();
	return ! is_admin() && $page instanceof WP_Post && 'page' === $page->post_type
		&& 'san-pham-moi' === $page->post_name && has_blocks( $page->post_content );
}

function nem_new_block_has_class( $block, $class ) {
	return in_array( $class, preg_split( '/\s+/', $block['attrs']['className'] ?? '' ), true );
}

function nem_new_editor_product_id( $block ) {
	return preg_match( '/\bnem-product-id-(\d+)\b/', $block['attrs']['className'] ?? '', $match ) ? absint( $match[1] ) : 0;
}

function nem_new_editor_category_slug( $slug ) {
	// The saved design used an older name for the existing Măng tô category.
	return 'ao-mang-to' === $slug ? 'mang-to' : ( 'all' === $slug ? '' : $slug );
}

/** Keep the designer's card order ahead of the remaining newest catalog products. */
add_filter( 'posts_orderby', function ( $orderby, $query ) {
	$ids = array_filter( array_map( 'absint', (array) $query->get( 'nem_new_editor_order' ) ) );
	if ( ! $ids ) return $orderby;
	global $wpdb;
	$list = implode( ',', $ids );
	return "CASE WHEN {$wpdb->posts}.ID IN ($list) THEN 0 ELSE 1 END, FIELD({$wpdb->posts}.ID,$list), " . $orderby;
}, 10, 2 );

function nem_new_editor_catalog() {
	static $cache = [];
	$page = get_queried_object();
	$state = nem_new_filter_state();
	$key = md5( $page->post_content . wp_json_encode( $state ) );
	if ( isset( $cache[ $key ] ) ) return $cache[ $key ];
	$cards = [];
	$walk = function ( $blocks, $in_grid = false ) use ( &$walk, &$cards ) {
		foreach ( $blocks as $block ) {
			$id = nem_new_editor_product_id( $block );
			if ( $id && $in_grid ) $cards[ $id ] = $block;
			else $walk( $block['innerBlocks'] ?? [], $in_grid || nem_new_block_has_class( $block, 'nem-new-products-grid' ) );
		}
	};
	$walk( parse_blocks( $page->post_content ) );
	$args = nem_new_query_args( $state );
	$args['nem_new_editor_order'] = array_keys( $cards );
	$args['posts_per_page'] = $cards ? count( $cards ) : 12;
	return $cache[ $key ] = [ 'state' => $state, 'cards' => $cards, 'query' => new WP_Query( $args ) ];
}

function nem_new_editor_toolbar( $state ) {
	ob_start();
	echo '<div class="nem-new-toolbar-controls">';
	get_template_part( 'template-parts/catalog-filters', null, [ 'state' => $state, 'url' => nem_new_products_url() ] );
	echo '<div class="nem-new-grid-choices" aria-label="Số cột sản phẩm">';
	foreach ( [ 3, 2 ] as $columns ) {
		echo '<a href="' . esc_url( nem_new_filter_url( $state, [ 'cot' => $columns ] ) ) . '" aria-label="Hiển thị ' . $columns . ' cột"' . ( $state['cot'] === $columns ? ' class="is-active" aria-current="true"' : '' ) . '>';
		get_template_part( 'template-parts/catalog-grid-icon', null, [ 'columns' => $columns ] );
		echo '</a>';
	}
	echo '</div></div>';
	return ob_get_clean();
}

/** Replace card slots without discarding row/group styles or other user-added blocks. */
function nem_new_editor_grid( $block, $catalog ) {
	$ids = $catalog['query']->posts;
	$index = 0;
	$walk = function ( $item ) use ( &$walk, &$index, $ids, $catalog ) {
		if ( nem_new_editor_product_id( $item ) ) {
			$id = $ids[ $index++ ] ?? 0;
			if ( ! $id ) return [ 'blockName' => 'core/html', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => [ '' ] ];
			if ( isset( $catalog['cards'][ $id ] ) ) return $catalog['cards'][ $id ];
			$product = wc_get_product( $id );
			ob_start();
			get_template_part( 'template-parts/collection-product', null, [ 'product' => $product, 'sku' => $product->get_sku() ] );
			$html = '<div class="wp-block-column nem-editor-live-card" data-product-id="' . absint( $id ) . '">' . ob_get_clean() . '</div>';
			return [ 'blockName' => 'core/html', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [ $html ] ];
		}
		$item['innerBlocks'] = array_map( $walk, $item['innerBlocks'] ?? [] );
		if ( nem_new_block_has_class( $item, 'nem-new-products-row' ) && ! array_filter( $item['innerBlocks'], function ( $child ) { return 'core/html' !== $child['blockName'] || '' !== $child['innerHTML']; } ) ) {
			return [ 'blockName' => 'core/html', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => [ '' ] ];
		}
		return $item;
	};
	$html = render_block( $walk( $block ) );
	if ( isset( $_GET['cot'] ) && is_string( $_GET['cot'] ) && in_array( $_GET['cot'], [ '2', '3' ], true ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->add_class( 'nem-editor-columns-override' );
			$tags->set_attribute( 'data-columns', $catalog['state']['cot'] );
		}
		$html = $tags->get_updated_html();
	}
	if ( ! $ids ) {
		$html .= '<div class="nem-new-empty" role="status"><p>Không tìm thấy sản phẩm phù hợp.</p><a href="' . esc_url( nem_new_products_url() ) . '">Xóa bộ lọc và xem tất cả sản phẩm mới</a></div>';
	}
	return $html;
}

/** Turn an editable paragraph into a link, retaining its text and formatting. */
function nem_new_editor_category( $html, $block, $state ) {
	if ( ! preg_match( '/\bnem-cat-slug-([a-z0-9-]+)/', $block['attrs']['className'] ?? '', $match ) ) return $html;
	$slug = nem_new_editor_category_slug( $match[1] );
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag() ) {
		$tags->remove_class( 'is-active' );
		if ( $slug === $state['danh_muc'] ) $tags->add_class( 'is-active' );
	}
	$html = $tags->get_updated_html();
	$tags = new WP_HTML_Tag_Processor( $html );
	$url = nem_new_filter_url( $state, [ 'danh_muc' => $slug, 'trang' => 1 ] );
	if ( $tags->next_tag( 'A' ) ) {
		$tags->set_attribute( 'href', $url );
		return $tags->get_updated_html();
	}
	return preg_replace( '/(<p\b[^>]*>)(.*?)(<\/p>)/s', '$1<a href="' . esc_url( $url ) . '">$2</a>$3', $html, 1 );
}

add_filter( 'render_block', function ( $html, $block ) {
	static $rendering_grid = false;
	if ( ! nem_new_uses_editor() || ! function_exists( 'WC' ) ) return $html;
	$class = $block['attrs']['className'] ?? '';
	if ( ! $class ) return $html;
	$catalog = nem_new_editor_catalog();
	$state = $catalog['state'];
	if ( nem_new_block_has_class( $block, 'nem-new-toolbar-placeholder' ) ) return nem_new_editor_toolbar( $state );
	if ( nem_new_block_has_class( $block, 'nem-new-products-grid' ) && ! $rendering_grid ) {
		$rendering_grid = true;
		try { return nem_new_editor_grid( $block, $catalog ); }
		finally { $rendering_grid = false; }
	}
	if ( nem_new_block_has_class( $block, 'nem-new-toolbar' ) && ( $state['danh_muc'] || $state['gia'] || $state['kich_co'] || $state['mau_sac'] ) ) {
		$html .= '<div class="nem-new-filter-status" role="status"><span>' . esc_html( $catalog['query']->found_posts . ' sản phẩm phù hợp' ) . '</span><a href="' . esc_url( nem_new_products_url() ) . '">Xóa bộ lọc</a></div>';
	}
	if ( nem_new_block_has_class( $block, 'nem-new-pagination' ) ) {
		if ( $catalog['query']->max_num_pages <= 1 ) return '';
		return '<nav class="nem-new-pagination" aria-label="Phân trang sản phẩm">' . paginate_links( [
			'base' => str_replace( '999999999', '%#%', esc_url( nem_new_filter_url( $state, [ 'trang' => 999999999 ] ) ) ),
			'format' => '', 'current' => $state['trang'], 'total' => $catalog['query']->max_num_pages,
			'mid_size' => 1, 'end_size' => 1, 'prev_text' => '«', 'next_text' => '»', 'type' => 'list',
		] ) . '</nav>';
	}
	if ( str_contains( $class, 'nem-cat-slug-' ) ) $html = nem_new_editor_category( $html, $block, $state );
	$id = nem_new_editor_product_id( $block );
	if ( $id ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) $tags->set_attribute( 'data-product-id', $id );
		$html = $tags->get_updated_html();
		$product = wc_get_product( $id );
		$gallery = $product ? $product->get_gallery_image_ids() : [];
		$secondary = $gallery ? wp_get_attachment_image_url( $gallery[0], 'large' ) : '';
		if ( $secondary ) {
			$html = preg_replace_callback( '/(<a\b[^>]*>)(\s*<img\b[^>]*>\s*)(<\/a>)/s', function ( $match ) use ( $secondary ) {
				return $match[1] . $match[2] . '<img class="nem-collection-image-secondary" src="' . esc_url( $secondary ) . '" alt="" aria-hidden="true" loading="lazy" /><span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span>' . $match[3];
			}, $html, 1 );
		}
	}
	if ( nem_new_block_has_class( $block, 'nem-newsletter-input-box' ) ) {
		$placeholder = wp_strip_all_tags( $html );
		return '<label class="screen-reader-text" for="nem-editor-email">Email</label><input id="nem-editor-email" type="email" name="email" maxlength="250" placeholder="' . esc_attr( $placeholder ) . '" required />';
	}
	if ( nem_new_block_has_class( $block, 'nem-newsletter-submit-btn' ) ) return '<button type="submit">' . esc_html( wp_strip_all_tags( $html ) ) . '</button>';
	if ( nem_new_block_has_class( $block, 'nem-newsletter-input-row' ) ) {
		$fields = '<input type="hidden" name="action" value="nem_collection_newsletter" /><input type="hidden" name="return_to" value="san-pham-moi" />' . wp_nonce_field( 'nem_collection_newsletter', 'nem_collection_nonce', true, false );
		$html = '<form class="nem-editor-newsletter-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">' . $fields . $html . '</form>';
		$messages = [ 'ok' => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!', 'exists' => 'Email này đã đăng ký nhận bản tin.', 'error' => 'Vui lòng nhập email hợp lệ và thử lại.' ];
		$status = isset( $_GET['newsletter'] ) && is_string( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
		if ( isset( $messages[ $status ] ) ) $html .= '<p class="nem-editor-newsletter-status" role="status">' . esc_html( $messages[ $status ] ) . '</p>';
	}
	if ( nem_new_block_has_class( $block, 'nem-collection-newsletter' ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) $tags->set_attribute( 'id', 'nem-collection-newsletter' );
		$html = $tags->get_updated_html();
	}
	if ( nem_new_block_has_class( $block, 'nem-footer-wrapper' ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) $tags->set_attribute( 'role', 'contentinfo' );
		$html = $tags->get_updated_html();
	}
	return $html;
}, 20, 2 );

function nem_new_is_editor_screen() {
	if ( ! is_admin() ) return false;
	$id = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$page = get_post( $id );
	return $id && $page && 'page' === $page->post_type && 'san-pham-moi' === $page->post_name;
}

/** Share actual layout rules between the frontend and the editor canvas. */
function nem_new_enqueue_block_design() {
	if ( ! nem_new_uses_editor() && ! nem_new_is_editor_screen() ) return;
	$dependencies = is_admin() ? [ 'nem-editor-new-products' ] : [ 'nem-new-products', 'nem-catalog-controls' ];
	wp_enqueue_style( 'nem-new-block-design', get_template_directory_uri() . '/assets/css/nem-new-products-blocks.css', $dependencies, filemtime( __DIR__ . '/../assets/css/nem-new-products-blocks.css' ) );
	if ( is_admin() ) wp_dequeue_style( 'nem-editor-sale' );
}
add_action( 'wp_enqueue_scripts', 'nem_new_enqueue_block_design', 200 );
add_action( 'enqueue_block_assets', 'nem_new_enqueue_block_design', 200 );
add_action( 'after_setup_theme', function () {
	if ( ! nem_new_is_editor_screen() ) return;
	global $editor_styles;
	$editor_styles = array_values( array_filter( (array) $editor_styles, function ( $style ) { return ! str_contains( $style, 'nem-sale-editor.css' ); } ) );
	add_editor_style( 'assets/css/nem-new-products-blocks.css' );
}, 100 );
