<?php
/** Editable collection stories share their saved structure and storefront styles. */
defined( 'ABSPATH' ) || exit;

function nem_collections_uses_editor() {
	if ( is_admin() ) return false;
	$page = get_queried_object();
	return $page instanceof WP_Post && 'page' === $page->post_type && 'bo-suu-tap' === $page->post_name && has_blocks( $page->post_content );
}

function nem_collections_editor_screen() {
	if ( ! is_admin() ) return false;
	$id = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$page = $id ? get_post( $id ) : null;
	return $page && 'page' === $page->post_type && 'bo-suu-tap' === $page->post_name;
}

function nem_collections_block_has_class( $block, $class ) {
	return in_array( $class, preg_split( '/\s+/', $block['attrs']['className'] ?? '' ), true );
}

/** Repair imported block attributes without replacing the user's stories or images. */
function nem_collections_normalize_editor_blocks( $blocks ) {
	foreach ( $blocks as &$block ) {
		$block['innerBlocks'] = nem_collections_normalize_editor_blocks( $block['innerBlocks'] ?? [] );
		if ( in_array( $block['blockName'], [ 'core/group', 'core/heading' ], true ) ) {
			$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );
			if ( $tags->next_tag() ) {
				$id = $tags->get_attribute( 'id' );
				if ( is_string( $id ) && '' !== $id ) $block['attrs']['anchor'] = $id;
				if ( 'core/group' === $block['blockName'] ) {
					$tag = strtolower( $tags->get_tag() );
					if ( 'div' !== $tag ) $block['attrs']['tagName'] = $tag;
				}
			}
		}
		if ( nem_collections_block_has_class( $block, 'nem-collection-socials' ) && empty( $block['innerBlocks'] ) ) {
			$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );
			$links = [];
			while ( $tags->next_tag( 'A' ) ) {
				$class = (string) $tags->get_attribute( 'class' );
				$label = str_contains( $class, 'facebook' ) ? 'Facebook' : ( str_contains( $class, 'instagram' ) ? 'Instagram' : 'YouTube' );
				$href = esc_url( (string) $tags->get_attribute( 'href' ) );
				$html = '<p class="nem-collection-social-item"><a class="' . esc_attr( $class ) . '" href="' . $href . '" target="_blank" rel="noreferrer noopener">' . $label . '</a></p>';
				$links[] = [ 'blockName' => 'core/paragraph', 'attrs' => [ 'className' => 'nem-collection-social-item' ], 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [ $html ] ];
			}
			$block['attrs']['layout'] = [ 'type' => 'flex', 'flexWrap' => 'nowrap', 'justifyContent' => 'center' ];
			$block['innerBlocks'] = $links;
			$block['innerContent'] = array_merge( [ '<div class="wp-block-group nem-collection-socials">' ], array_fill( 0, count( $links ), null ), [ '</div>' ] );
			$block['innerHTML'] = '<div class="wp-block-group nem-collection-socials"></div>';
		}
		// Group and heading accessibility attributes are applied at render time.
		if ( 'core/group' === $block['blockName'] ) {
			$clean_root = function ( $html ) {
				$tags = new WP_HTML_Tag_Processor( $html );
				if ( $tags->next_tag() ) {
					$tags->remove_attribute( 'aria-labelledby' );
					$tags->remove_attribute( 'aria-label' );
				}
				return $tags->get_updated_html();
			};
			$block['innerHTML'] = $clean_root( $block['innerHTML'] );
			if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) $block['innerContent'][0] = $clean_root( $block['innerContent'][0] );
		}
	}
	unset( $block );
	return $blocks;
}

add_filter( 'render_block', function ( $html, $block ) {
	if ( ! nem_collections_uses_editor() ) return $html;
	if ( nem_collections_block_has_class( $block, 'nem-newsletter-input-box' ) ) {
		return '<label class="screen-reader-text" for="nem-collection-editor-email">Email</label><input id="nem-collection-editor-email" type="email" name="email" maxlength="250" placeholder="' . esc_attr( trim( wp_strip_all_tags( $html ) ) ) . '" required />';
	}
	if ( nem_collections_block_has_class( $block, 'nem-newsletter-submit-btn' ) ) return '<button type="submit">' . esc_html( trim( wp_strip_all_tags( $html ) ) ) . '</button>';
	if ( nem_collections_block_has_class( $block, 'nem-newsletter-input-row' ) ) {
		$fields = '<input type="hidden" name="action" value="nem_collection_newsletter" />' . wp_nonce_field( 'nem_collection_newsletter', 'nem_collection_nonce', true, false );
		$html = '<form class="nem-collection-editor-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">' . $fields . $html . '</form>';
		$messages = [ 'ok' => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!', 'exists' => 'Email này đã đăng ký nhận bản tin.', 'error' => 'Vui lòng nhập email hợp lệ và thử lại.' ];
		$status = isset( $_GET['newsletter'] ) && is_string( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
		if ( isset( $messages[ $status ] ) ) $html .= '<p role="status">' . esc_html( $messages[ $status ] ) . '</p>';
	}
	if ( nem_collections_block_has_class( $block, 'nem-collection-newsletter' ) ) {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'id', 'nem-collection-newsletter' );
			if ( str_contains( $html, 'id="nem-collection-newsletter-title"' ) ) $tags->set_attribute( 'aria-labelledby', 'nem-collection-newsletter-title' );
		}
		$html = $tags->get_updated_html();
	}
	if ( nem_collections_block_has_class( $block, 'nem-collection-product' ) && preg_match( '/\bnem-product-id-(\d+)\b/', $block['attrs']['className'] ?? '', $match ) ) {
		$id = absint( $match[1] );
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag() ) $tags->set_attribute( 'data-product-id', $id );
		$html = $tags->get_updated_html();
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
		$images = $product ? (array) $product->get_meta( '_nem_collection_images' ) : [];
		$gallery = $product ? $product->get_gallery_image_ids() : [];
		$secondary = ! empty( $images[1] ) ? nem_collection_asset( $images[1] ) : ( $gallery ? wp_get_attachment_image_url( $gallery[0], 'large' ) : '' );
		if ( $secondary ) {
			$html = preg_replace_callback( '/(<a\b[^>]*>)(\s*<img\b[^>]*>\s*)(<\/a>)/s', function ( $parts ) use ( $secondary ) {
				return $parts[1] . $parts[2] . '<img class="nem-collection-image-secondary" src="' . esc_url( $secondary ) . '" alt="" aria-hidden="true" loading="lazy" decoding="async" /><span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span>' . $parts[3];
			}, $html, 1 );
		}
	}
	return $html;
}, 20, 2 );

function nem_collections_enqueue_editor_design() {
	if ( ! nem_collections_uses_editor() && ! nem_collections_editor_screen() ) return;
	$dependencies = is_admin() ? [ 'nem-editor-collections' ] : [ 'nem-collections' ];
	wp_enqueue_style( 'nem-collection-block-design', get_template_directory_uri() . '/assets/css/nem-collections-blocks.css', $dependencies, filemtime( __DIR__ . '/../assets/css/nem-collections-blocks.css' ) );
	if ( is_admin() ) {
		wp_dequeue_style( 'nem-editor-sale' );
		wp_dequeue_script( 'nem-editor-sale-js' );
	}
}
add_action( 'wp_enqueue_scripts', 'nem_collections_enqueue_editor_design', 200 );
add_action( 'enqueue_block_assets', 'nem_collections_enqueue_editor_design', 200 );
add_action( 'enqueue_block_editor_assets', 'nem_collections_enqueue_editor_design', 200 );
add_action( 'after_setup_theme', function () {
	if ( ! nem_collections_editor_screen() ) return;
	global $editor_styles;
	$editor_styles = array_values( array_filter( (array) $editor_styles, function ( $style ) { return ! str_contains( $style, 'nem-sale-editor.css' ); } ) );
	add_editor_style( 'assets/css/nem-collections-blocks.css' );
}, 100 );
