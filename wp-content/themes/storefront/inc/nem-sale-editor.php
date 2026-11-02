<?php
/**
 * Render the saved NEM SALE design from Gutenberg editor, enhancing interactive controls and WooCommerce sync.
 */
defined( 'ABSPATH' ) || exit;

function nem_sale_uses_editor() {
	$page = get_queried_object();
	if ( ! is_admin() && $page instanceof WP_Post && 'page' === $page->post_type
		&& ( 'sale' === $page->post_name || 'san-pham-sale' === $page->post_name || 217 === (int) $page->ID ) && has_blocks( $page->post_content ) ) {
		return true;
	}
	if ( ! is_admin() && is_page_template( 'page-sale.php' ) ) {
		global $post;
		if ( $post && has_blocks( $post->post_content ) ) {
			return true;
		}
	}
	return false;
}

function nem_sale_block_has_class( $block, $class ) {
	return in_array( $class, preg_split( '/\s+/', $block['attrs']['className'] ?? '' ), true );
}

function nem_sale_editor_product_id( $block ) {
	return preg_match( '/\bnem-product-id-(\d+)\b/', $block['attrs']['className'] ?? '', $match ) ? absint( $match[1] ) : 0;
}

function nem_sale_editor_category_slug( $slug ) {
	return 'ao-mang-to' === $slug ? 'mang-to' : ( 'all' === $slug ? '' : $slug );
}

/**
 * Keep the designer's card order ahead of the remaining catalog products.
 */
add_filter( 'posts_orderby', function ( $orderby, $query ) {
	$ids = array_filter( array_map( 'absint', (array) $query->get( 'nem_sale_editor_order' ) ) );
	if ( ! $ids ) {
		return $orderby;
	}
	global $wpdb;
	$list = implode( ',', $ids );
	return "CASE WHEN {$wpdb->posts}.ID IN ($list) THEN 0 ELSE 1 END, FIELD({$wpdb->posts}.ID,$list), " . $orderby;
}, 10, 2 );

function nem_sale_editor_catalog() {
	static $cache = array();
	$page = get_queried_object();
	if ( ! ( $page instanceof WP_Post ) ) {
		global $post;
		$page = $post;
	}
	$state   = nem_sale_filter_state();
	$content = ( $page && isset( $page->post_content ) ) ? $page->post_content : '';
	$key     = md5( $content . wp_json_encode( $state ) );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$cards = array();
	$walk  = function ( $blocks, $in_grid = false ) use ( &$walk, &$cards ) {
		foreach ( $blocks as $block ) {
			$id = nem_sale_editor_product_id( $block );
			if ( $id && $in_grid ) {
				$cards[ $id ] = $block;
			} else {
				$walk( $block['innerBlocks'] ?? array(), $in_grid || nem_sale_block_has_class( $block, 'nem-new-products-grid' ) );
			}
		}
	};
	if ( ! empty( $content ) ) {
		$walk( parse_blocks( $content ) );
	}
	$args = nem_sale_query_args( $state );
	$args['nem_sale_editor_order'] = array_keys( $cards );
	$args['posts_per_page']        = $cards ? count( $cards ) : 12;
	return $cache[ $key ] = array(
		'state' => $state,
		'cards' => $cards,
		'query' => new WP_Query( $args ),
	);
}

function nem_sale_editor_toolbar( $state ) {
	ob_start();
	echo '<div class="nem-new-toolbar-controls">';
	get_template_part( 'template-parts/catalog-filters', null, array( 'state' => $state, 'url' => nem_sale_products_url() ) );
	echo '<div class="nem-new-grid-choices" aria-label="Số cột sản phẩm">';
	foreach ( array( 3, 2 ) as $columns ) {
		echo '<a href="' . esc_url( nem_sale_filter_url( $state, array( 'cot' => $columns ) ) ) . '" aria-label="Hiển thị ' . $columns . ' cột"' . ( $state['cot'] === $columns ? ' class="is-active" aria-current="true"' : '' ) . '>';
		get_template_part( 'template-parts/catalog-grid-icon', null, array( 'columns' => $columns ) );
		echo '</a>';
	}
	echo '</div></div>';
	return ob_get_clean();
}

/**
 * Replace card slots without discarding row/group styles or other user-added blocks.
 */
function nem_sale_editor_grid( $block, $catalog ) {
	$ids   = $catalog['query']->posts;
	$index = 0;
	$walk  = function ( $item ) use ( &$walk, &$index, $ids, $catalog ) {
		if ( nem_sale_editor_product_id( $item ) ) {
			$id = $ids[ $index++ ] ?? 0;
			if ( ! $id ) {
				return array(
					'blockName'    => 'core/html',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array( '' ),
				);
			}
			if ( isset( $catalog['cards'][ $id ] ) ) {
				return $catalog['cards'][ $id ];
			}
			$product = wc_get_product( $id );
			ob_start();
			get_template_part( 'template-parts/collection-product', null, array( 'product' => $product, 'sku' => $product->get_sku() ) );
			$html = '<div class="wp-block-column nem-editor-live-card" data-product-id="' . absint( $id ) . '">' . ob_get_clean() . '</div>';
			return array(
				'blockName'    => 'core/html',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => $html,
				'innerContent' => array( $html ),
			);
		}
		$item['innerBlocks'] = array_map( $walk, $item['innerBlocks'] ?? array() );
		$is_row              = nem_sale_block_has_class( $item, 'nem-new-products-row' ) || nem_sale_block_has_class( $item, 'nem-sale-products-row' ) || nem_sale_block_has_class( $item, 'nem-plus-products-row' );
		if ( $is_row && ! array_filter( $item['innerBlocks'], function ( $child ) {
			return 'core/html' !== $child['blockName'] || '' !== $child['innerHTML'];
		} ) ) {
			return array(
				'blockName'    => 'core/html',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array( '' ),
			);
		}
		return $item;
	};
	$html = render_block( $walk( $block ) );
	if ( isset( $_GET['cot'] ) && is_string( $_GET['cot'] ) && in_array( $_GET['cot'], array( '2', '3' ), true ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->add_class( 'nem-editor-columns-override' );
			$tags->set_attribute( 'data-columns', $catalog['state']['cot'] );
		}
		$html = $tags->get_updated_html();
	}
	if ( ! $ids ) {
		$html .= '<div class="nem-new-empty" role="status"><p>Không tìm thấy sản phẩm phù hợp.</p><a href="' . esc_url( nem_sale_products_url() ) . '">Xóa bộ lọc và xem tất cả sản phẩm SALE</a></div>';
	}
	return $html;
}

/**
 * Turn an editable paragraph into a link, retaining its text and formatting.
 */
function nem_sale_editor_category( $html, $block, $state ) {
	if ( ! preg_match( '/\bnem-cat-slug-([a-z0-9-]+)/', $block['attrs']['className'] ?? '', $match ) ) {
		return $html;
	}
	$slug = nem_sale_editor_category_slug( $match[1] );
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag() ) {
		$tags->remove_class( 'is-active' );
		if ( $slug === $state['danh_muc'] ) {
			$tags->add_class( 'is-active' );
		}
	}
	$html = $tags->get_updated_html();
	$tags = new WP_HTML_Tag_Processor( $html );
	$url  = nem_sale_filter_url( $state, array( 'danh_muc' => $slug, 'trang' => 1 ) );
	if ( $tags->next_tag( 'A' ) ) {
		$tags->set_attribute( 'href', $url );
		return $tags->get_updated_html();
	}
	return preg_replace( '/(<p\b[^>]*>)(.*?)(<\/p>)/s', '$1<a href="' . esc_url( $url ) . '">$2</a>$3', $html, 1 );
}

add_filter( 'render_block', function ( $html, $block ) {
	static $rendering_grid = false;
	if ( ! nem_sale_uses_editor() || ! function_exists( 'WC' ) ) {
		return $html;
	}
	$class = $block['attrs']['className'] ?? '';
	if ( ! $class ) {
		return $html;
	}
	$catalog = nem_sale_editor_catalog();
	$state   = $catalog['state'];
	if ( nem_sale_block_has_class( $block, 'nem-new-toolbar-placeholder' ) || nem_sale_block_has_class( $block, 'nem-sale-toolbar-placeholder' ) || nem_sale_block_has_class( $block, 'nem-plus-toolbar-placeholder' ) ) {
		return nem_sale_editor_toolbar( $state );
	}
	if ( nem_sale_block_has_class( $block, 'nem-new-products-grid' ) && ! $rendering_grid ) {
		$rendering_grid = true;
		try {
			return nem_sale_editor_grid( $block, $catalog );
		} finally {
			$rendering_grid = false;
		}
	}
	if ( nem_sale_block_has_class( $block, 'nem-new-toolbar' ) && ( $state['danh_muc'] || $state['gia'] || ! empty( $state['kich_co'] ) || ! empty( $state['mau_sac'] ) || ! empty( $state['sale'] ) ) ) {
		$html .= '<div class="nem-new-filter-status" role="status"><span>' . esc_html( $catalog['query']->found_posts . ' sản phẩm phù hợp' ) . '</span><a href="' . esc_url( nem_sale_products_url() ) . '">Xóa bộ lọc</a></div>';
	}
	if ( nem_sale_block_has_class( $block, 'nem-new-pagination' ) ) {
		if ( $catalog['query']->max_num_pages <= 1 ) {
			return '';
		}
		return '<nav class="nem-new-pagination" aria-label="Phân trang sản phẩm">' . paginate_links( array(
			'base'      => str_replace( '999999999', '%#%', esc_url( nem_sale_filter_url( $state, array( 'trang' => 999999999 ) ) ) ),
			'format'    => '',
			'current'   => $state['trang'],
			'total'     => $catalog['query']->max_num_pages,
			'mid_size'  => 1,
			'end_size'  => 1,
			'prev_text' => '«',
			'next_text' => '»',
			'type'      => 'list',
		) ) . '</nav>';
	}
	if ( str_contains( $class, 'nem-cat-slug-' ) ) {
		$html = nem_sale_editor_category( $html, $block, $state );
	}
	$id = nem_sale_editor_product_id( $block );
	if ( $id ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-product-id', $id );
		}
		$html      = $tags->get_updated_html();
		$product   = wc_get_product( $id );
		$gallery   = $product ? $product->get_gallery_image_ids() : array();
		$secondary = $gallery ? wp_get_attachment_image_url( $gallery[0], 'large' ) : '';
		if ( $secondary ) {
			$html = preg_replace_callback( '/(<a\b[^>]*>)(\s*<img\b[^>]*>\s*)(<\/a>)/s', function ( $match ) use ( $secondary ) {
				return $match[1] . $match[2] . '<img class="nem-collection-image-secondary" src="' . esc_url( $secondary ) . '" alt="" aria-hidden="true" loading="lazy" /><span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span>' . $match[3];
			}, $html, 1 );
		}
	}
	if ( nem_sale_block_has_class( $block, 'nem-newsletter-input-box' ) ) {
		$placeholder = wp_strip_all_tags( $html );
		return '<label class="screen-reader-text" for="nem-editor-email">Email</label><input id="nem-editor-email" type="email" name="email" maxlength="250" placeholder="' . esc_attr( $placeholder ) . '" required />';
	}
	if ( nem_sale_block_has_class( $block, 'nem-newsletter-submit-btn' ) ) {
		return '<button type="submit">' . esc_html( wp_strip_all_tags( $html ) ) . '</button>';
	}
	if ( nem_sale_block_has_class( $block, 'nem-newsletter-input-row' ) ) {
		$fields   = '<input type="hidden" name="action" value="nem_collection_newsletter" /><input type="hidden" name="return_to" value="sale" />' . wp_nonce_field( 'nem_collection_newsletter', 'nem_collection_nonce', true, false );
		$html     = '<form class="nem-editor-newsletter-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">' . $fields . $html . '</form>';
		$messages = array(
			'ok'     => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!',
			'exists' => 'Email này đã đăng ký nhận bản tin.',
			'error'  => 'Vui lòng nhập email hợp lệ và thử lại.',
		);
		$status   = isset( $_GET['newsletter'] ) && is_string( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
		if ( isset( $messages[ $status ] ) ) {
			$html .= '<p class="nem-editor-newsletter-status" role="status">' . esc_html( $messages[ $status ] ) . '</p>';
		}
	}
	if ( nem_sale_block_has_class( $block, 'nem-collection-newsletter' ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'id', 'nem-collection-newsletter' );
		}
		$html = $tags->get_updated_html();
	}
	if ( nem_sale_block_has_class( $block, 'nem-footer-wrapper' ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'role', 'contentinfo' );
		}
		$html = $tags->get_updated_html();
	}
	return $html;
}, 20, 2 );

function nem_sale_is_editor_screen() {
	if ( ! is_admin() ) {
		return false;
	}
	$id   = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$page = get_post( $id );
	return $id && $page && 'page' === $page->post_type && ( 'sale' === $page->post_name || 'san-pham-sale' === $page->post_name || 217 === $id );
}

/**
 * Share layout rules between frontend and editor canvas.
 */
function nem_sale_enqueue_block_design() {
	if ( ! nem_sale_uses_editor() && ! nem_sale_is_editor_screen() ) {
		return;
	}
	$dependencies = is_admin() ? array( 'nem-editor-new-products' ) : array( 'nem-new-products', 'nem-catalog-controls' );
	wp_enqueue_style( 'nem-new-block-design', get_template_directory_uri() . '/assets/css/nem-new-products-blocks.css', $dependencies, filemtime( __DIR__ . '/../assets/css/nem-new-products-blocks.css' ) );
	if ( is_admin() ) {
		if ( file_exists( __DIR__ . '/../assets/js/nem-sale-editor.js' ) ) {
			wp_enqueue_script( 'nem-editor-sale-js', get_template_directory_uri() . '/assets/js/nem-sale-editor.js', array(), filemtime( __DIR__ . '/../assets/js/nem-sale-editor.js' ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'nem_sale_enqueue_block_design', 200 );
add_action( 'enqueue_block_assets', 'nem_sale_enqueue_block_design', 200 );
add_action( 'after_setup_theme', function () {
	if ( ! nem_sale_is_editor_screen() ) {
		return;
	}
	add_editor_style( 'assets/css/nem-new-products-blocks.css' );
}, 100 );

