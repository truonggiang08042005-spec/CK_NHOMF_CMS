<?php
/**
 * NEM SALE catalog page with persistent filters, Gutenberg block editor integration,
 * and two-way synchronization between the SALE Page editor and WooCommerce products.
 */
defined( 'ABSPATH' ) || exit;

function nem_is_sale_page() {
	return is_page( 'sale' ) || is_page_template( 'page-sale.php' ) || is_page( 'san-pham-sale' ) ||
		( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['on_sale'] ) && '1' === (string) $_GET['on_sale'] ) ||
		( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['sale'] ) && '' !== $_GET['sale'] );
}

function nem_sale_products_url() {
	return home_url( '/sale/' );
}

function nem_sale_filter_state( $input = null ) {
	$input = null === $input ? wp_unslash( $_GET ) : $input;
	$scalar = function ( $key ) use ( $input ) {
		return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_title( $input[ $key ] ) : '';
	};
	$state = array(
		'danh_muc' => $scalar( 'danh_muc' ),
		'gia'      => $scalar( 'gia' ),
		'cot'      => '2' === $scalar( 'cot' ) ? 2 : 3,
		'trang'    => max( 1, min( 1000, absint( $scalar( 'trang' ) ) ) ),
		'sale'     => $scalar( 'sale' ),
	);

	if ( function_exists( 'nem_new_product_categories' ) ) {
		$categories = array();
		foreach ( nem_new_product_categories() as $slug => $group ) {
			$categories[] = $slug;
			$categories   = array_merge( $categories, array_keys( $group['children'] ) );
		}
		if ( ! in_array( $state['danh_muc'], $categories, true ) ) {
			$state['danh_muc'] = '';
		}
	}

	if ( function_exists( 'nem_new_price_ranges' ) && ! isset( nem_new_price_ranges()[ $state['gia'] ] ) ) {
		$state['gia'] = '';
	}

	if ( ! in_array( $state['sale'], array( '199k', '70' ), true ) ) {
		$state['sale'] = '';
	}

	foreach ( array( 'kich_co', 'mau_sac' ) as $key ) {
		$values        = $input[ $key ] ?? array();
		$values        = is_array( $values ) ? array_slice( $values, 0, 30 ) : array( $values );
		$state[ $key ] = array_values( array_unique( array_filter( array_map( 'sanitize_title', array_filter( $values, 'is_string' ) ) ) ) );
	}

	return $state;
}

function nem_sale_has_active_filters( $state ) {
	return ! empty( $state['danh_muc'] ) || ! empty( $state['gia'] ) || ! empty( $state['kich_co'] ) || ! empty( $state['mau_sac'] ) || ! empty( $state['sale'] );
}

function nem_sale_filter_url( $state, $changes = array() ) {
	$state = array_merge( $state, $changes );
	if ( 1 === (int) $state['trang'] ) {
		unset( $state['trang'] );
	}
	return add_query_arg( array_filter( $state ), nem_sale_products_url() );
}

function nem_sale_query_args( $state ) {
	$visibility = wc_get_product_visibility_term_ids();
	$exclude    = array( $visibility['exclude-from-catalog'] );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$exclude[] = $visibility['outofstock'];
	}

	$on_sale_ids = wc_get_product_ids_on_sale();
	if ( empty( $on_sale_ids ) ) {
		$on_sale_ids = array( 0 );
	}

	// Filter by sale parameter if present (199k or 70%)
	if ( '70' === $state['sale'] ) {
		$sale70_ids = array();
		foreach ( $on_sale_ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$reg = (float) $p->get_regular_price();
			$sal = (float) $p->get_price();
			if ( $reg > 0 && round( ( ( $reg - $sal ) / $reg ) * 100 ) >= 70 ) {
				$sale70_ids[] = $id;
			}
		}
		$on_sale_ids = ! empty( $sale70_ids ) ? $sale70_ids : array( 0 );
	} elseif ( '199k' === $state['sale'] ) {
		$sale199_ids = array();
		foreach ( $on_sale_ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$sal = (float) $p->get_price();
			if ( $sal <= 300000 ) {
				$sale199_ids[] = $id;
			}
		}
		$on_sale_ids = ! empty( $sale199_ids ) ? $sale199_ids : array( 0 );
	}

	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => $exclude,
			'operator' => 'NOT IN',
		),
	);

	// Subcategory filter if chosen by user
	if ( ! empty( $state['danh_muc'] ) ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $state['danh_muc'],
			'include_children' => true,
		);
	}

	foreach ( array( 'kich_co' => 'pa_kich-co', 'mau_sac' => 'pa_mau-sac' ) as $key => $taxonomy ) {
		if ( ! empty( $state[ $key ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $state[ $key ],
				'operator' => 'IN',
			);
		}
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => $state['trang'],
		'post__in'            => $on_sale_ids,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
			'ID'         => 'DESC',
		),
		'fields'              => 'ids',
		'tax_query'           => $tax_query,
		'ignore_sticky_posts' => true,
	);

	if ( function_exists( 'nem_new_price_ranges' ) ) {
		$ranges = nem_new_price_ranges();
		if ( isset( $ranges[ $state['gia'] ] ) ) {
			$range = $ranges[ $state['gia'] ];
			$args['meta_query'] = array(
				array(
					'key'     => '_price',
					'value'   => $range['min'],
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			);
			if ( null !== $range['max'] ) {
				$args['meta_query'][] = array(
					'key'     => '_price',
					'value'   => $range['max'],
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
		}
	}

	return $args;
}

/**
 * Render the interactive filter form and 3/2 column toggle buttons for the SALE toolbar.
 */
function nem_sale_render_toolbar_controls( $state ) {
	ob_start();
	?>
	<form class="nem-new-filters" method="get" action="<?php echo esc_url( nem_sale_products_url() ); ?>">
		<input type="hidden" name="cot" value="<?php echo esc_attr( $state['cot'] ); ?>" />
		<?php if ( $state['danh_muc'] ) : ?>
			<input type="hidden" name="danh_muc" value="<?php echo esc_attr( $state['danh_muc'] ); ?>" />
		<?php endif; ?>
		<?php if ( $state['sale'] ) : ?>
			<input type="hidden" name="sale" value="<?php echo esc_attr( $state['sale'] ); ?>" />
		<?php endif; ?>

		<?php
		$filter_taxonomies = array(
			'kich_co' => array(
				'label'    => 'Kích cỡ',
				'taxonomy' => 'pa_kich-co',
			),
			'mau_sac' => array(
				'label'    => 'Màu sắc',
				'taxonomy' => 'pa_mau-sac',
			),
		);
		foreach ( $filter_taxonomies as $key => $filter ) :
			$terms = get_terms(
				array(
					'taxonomy'   => $filter['taxonomy'],
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				usort(
					$terms,
					function ( $a, $b ) {
						return strnatcasecmp( $a->name, $b->name );
					}
				);
			}
			?>
			<details class="nem-new-filter <?php echo ! empty( $state[ $key ] ) ? 'has-selection' : ''; ?>">
				<summary>
					<?php echo esc_html( $filter['label'] ); ?>
					<?php
					if ( ! empty( $state[ $key ] ) ) {
						echo ' (' . count( $state[ $key ] ) . ')';
					}
					?>
					<span aria-hidden="true">⌄</span>
				</summary>
				<div class="nem-new-filter-panel">
					<fieldset>
						<legend class="screen-reader-text"><?php echo esc_html( $filter['label'] ); ?></legend>
						<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
							<?php foreach ( $terms as $term ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $state[ $key ], true ) ); ?> />
									<span><?php echo esc_html( $term->name ); ?></span>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p>Chưa có lựa chọn.</p>
						<?php endif; ?>
					</fieldset>
					<button type="submit">Áp dụng</button>
				</div>
			</details>
		<?php endforeach; ?>

		<details class="nem-new-filter <?php echo '' !== $state['gia'] ? 'has-selection' : ''; ?>">
			<summary>Giá<span aria-hidden="true">⌄</span></summary>
			<div class="nem-new-filter-panel">
				<fieldset>
					<legend class="screen-reader-text">Khoảng giá</legend>
					<label>
						<input type="radio" name="gia" value="" <?php checked( '', $state['gia'] ); ?> />
						<span>Tất cả</span>
					</label>
					<?php if ( function_exists( 'nem_new_price_ranges' ) ) : ?>
						<?php foreach ( nem_new_price_ranges() as $key => $range ) : ?>
							<label>
								<input type="radio" name="gia" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $state['gia'] ); ?> />
								<span><?php echo esc_html( $range['label'] ); ?></span>
							</label>
						<?php endforeach; ?>
					<?php endif; ?>
				</fieldset>
				<button type="submit">Áp dụng</button>
			</div>
		</details>
	</form>

	<div class="nem-new-grid-choices" aria-label="Số cột sản phẩm">
		<?php foreach ( array( 3, 2 ) as $columns ) : ?>
			<a class="<?php echo $state['cot'] === $columns ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_sale_filter_url( $state, array( 'cot' => $columns ) ) ); ?>" aria-label="<?php echo esc_attr( 'Hiển thị ' . $columns . ' cột' ); ?>" <?php echo $state['cot'] === $columns ? 'aria-current="true"' : ''; ?>>
				<svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true">
					<?php
					for ( $row = 0; $row < 3; ++$row ) :
						for ( $column = 0; $column < $columns; ++$column ) :
							?>
							<rect x="<?php echo esc_attr( 1 + $column * ( 16 / $columns ) ); ?>" y="<?php echo esc_attr( 1 + $row * 5.5 ); ?>" width="<?php echo esc_attr( 14 / $columns ); ?>" height="4.5" />
							<?php
						endfor;
					endfor;
					?>
				</svg>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render the newsletter subscription form and social links inside the newsletter block.
 */
function nem_sale_render_newsletter_controls() {
	$status   = isset( $_GET['newsletter'] ) && is_string( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
	$messages = array(
		'ok'     => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!',
		'exists' => 'Email này đã đăng ký nhận bản tin.',
		'error'  => 'Vui lòng nhập email hợp lệ và thử lại.',
	);
	ob_start();
	?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="nem_collection_newsletter" />
		<input type="hidden" name="return_to" value="sale" />
		<?php wp_nonce_field( 'nem_collection_newsletter', 'nem_collection_nonce' ); ?>
		<label class="screen-reader-text" for="nem-collection-email">Email</label>
		<input type="email" id="nem-collection-email" name="email" maxlength="250" placeholder="Vui lòng nhập email..." required />
		<button type="submit">ĐĂNG KÝ</button>
	</form>
	<?php if ( isset( $messages[ $status ] ) ) : ?>
		<p role="status"><?php echo esc_html( $messages[ $status ] ); ?></p>
	<?php endif; ?>
	<div class="nem-collection-socials" aria-label="Mạng xã hội NEM">
		<a class="nem-collection-facebook" href="https://www.facebook.com/nemfashion.official" aria-label="Facebook NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.7-1.5H18V3.2c-.4-.1-1.8-.2-3.1-.2C11.9 3 10 4.8 10 8v2H7v3h3v8z"/></svg></a>
		<a class="nem-collection-instagram" href="https://www.instagram.com/nemfashion.official/" aria-label="Instagram NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="7" r="1"/></svg></a>
		<a class="nem-collection-youtube" href="https://www.youtube.com/@nemfashion" aria-label="YouTube NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 8 6 4-6 4z" fill="#ff0000"/></svg></a>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Enhance Gutenberg blocks on the frontend SALE page with interactive filters,
 * hover secondary product images, and dynamic filter results.
 */
add_filter( 'render_block', function ( $block_content, $block ) {
	if ( is_admin() || ! nem_is_sale_page() || ! function_exists( 'WC' ) ) {
		return $block_content;
	}

	$class_name = $block['attrs']['className'] ?? '';
	if ( '' === $class_name ) {
		return $block_content;
	}

	$state = nem_sale_filter_state();

	// 1. Replace toolbar placeholder with live filter dropdowns & column switcher.
	if ( false !== strpos( $class_name, 'nem-sale-toolbar-placeholder' ) ) {
		return nem_sale_render_toolbar_controls( $state );
	}

	// 2. Append active filter count / reset bar after toolbar when filters are active.
	if ( false !== strpos( $class_name, 'nem-new-toolbar' ) && nem_sale_has_active_filters( $state ) ) {
		$catalog = new WP_Query( nem_sale_query_args( $state ) );
		$status_html = sprintf(
			'<div class="nem-new-filter-status" role="status"><span>%s</span><a href="%s">Xóa bộ lọc</a></div>',
			esc_html( $catalog->found_posts . ' sản phẩm phù hợp' ),
			esc_url( nem_sale_products_url() )
		);
		return $block_content . $status_html;
	}

	// 3. Highlight active category in sidebar/categories-box, preserve filter parameters, and wrap in <details> on frontend.
	$is_cat_box     = false !== strpos( $class_name, 'nem-categories-box' );
	$is_raw_sidebar = false !== strpos( $class_name, 'nem-new-sidebar' ) && false === strpos( $block_content, 'nem-categories-box' );
	if ( $is_cat_box || $is_raw_sidebar ) {
		$block_content = preg_replace( '/\bis-active\b/', '', $block_content );
		$block_content = preg_replace_callback(
			'/<p class="([^"]*nem-new-category-[^"]*)"[^>]*>(.*?)<\/p>/is',
			function ( $matches ) use ( $state ) {
				$p_class = trim( $matches[1] );
				$inner   = trim( $matches[2] );
				$label   = wp_strip_all_tags( $inner );
				$slug    = '';

				if ( preg_match( '/nem-cat-slug-([a-z0-9_-]+)/i', $p_class, $sm ) ) {
					$slug = sanitize_title( $sm[1] );
				} elseif ( preg_match( '/[?&]danh_muc=([^&#"]+)/', html_entity_decode( $inner, ENT_QUOTES, 'UTF-8' ), $um ) ) {
					$slug = sanitize_title( urldecode( $um[1] ) );
				} elseif ( false === strpos( $p_class, 'nem-new-category-all' ) ) {
					$slug = sanitize_title( $label );
				}

				if ( 'all' === $slug || false !== strpos( $p_class, 'nem-new-category-all' ) ) {
					$slug = '';
				}

				if ( $slug === $state['danh_muc'] ) {
					$p_class .= ' is-active';
				}
				$new_url = nem_sale_filter_url( $state, array( 'danh_muc' => $slug, 'trang' => 1 ) );
				return '<p class="' . esc_attr( $p_class ) . '"><a href="' . esc_url( $new_url ) . '">' . esc_html( $label ) . '</a></p>';
			},
			$block_content
		);
		$summary_title = 'Danh mục';
		if ( preg_match( '/<h[1-6][^>]*nem-new-categories-title[^>]*>(.*?)<\/h[1-6]>/is', $block_content, $hm ) ) {
			$summary_title = trim( wp_strip_all_tags( $hm[1] ) ) ?: 'Danh mục';
			$block_content = preg_replace( '/<h[1-6][^>]*nem-new-categories-title[^>]*>.*?<\/h[1-6]>/is', '', $block_content, 1 );
		}
		$wrapper_class = $is_cat_box ? 'nem-categories-box' : 'nem-new-sidebar';
		$block_content = preg_replace(
			'/^\s*(<div[^>]*' . $wrapper_class . '[^>]*>)(.*)(<\/div>\s*)$/is',
			'$1<details class="nem-new-categories" open><summary>' . esc_html( $summary_title ) . '</summary><nav aria-label="Lọc theo danh mục">$2</nav></details>$3',
			$block_content
		);
		return $block_content;
	}

	// 4. Add secondary hover image & "XEM THÊM" overlay to each product card block.
	if ( false !== strpos( $class_name, 'nem-collection-product' ) && false === strpos( $class_name, 'nem-collection-product-' ) ) {
		$product_id = 0;
		if ( preg_match( '/nem-product-id-(\d+)/', $class_name, $m ) ) {
			$product_id = absint( $m[1] );
		}
		$secondary_html = '';
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$gallery   = $product->get_gallery_image_ids();
				$primary   = wp_get_attachment_image_url( $product->get_image_id(), 'large' ) ?: wc_placeholder_img_src( 'large' );
				$secondary = ! empty( $gallery ) ? wp_get_attachment_image_url( $gallery[0], 'large' ) : $primary;
				$secondary = $secondary ?: $primary;
				$secondary_html = '<img class="nem-collection-image-secondary" src="' . esc_url( $secondary ) . '" alt="" aria-hidden="true" width="600" height="900" loading="lazy" decoding="async" />';
			}
		}
		$overlay_html  = $secondary_html . '<span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span>';
		$block_content = preg_replace(
			'/(<figure[^>]*nem-collection-product-image[^>]*>\s*<a[^>]*>.*?)(<\/a>\s*<\/figure>)/is',
			'$1' . $overlay_html . '$2',
			$block_content,
			1
		);
		return $block_content;
	}

	// 5. Handle product grid columns (2 vs 3) and dynamic filtered results.
	if ( false !== strpos( $class_name, 'nem-new-products-grid' ) ) {
		if ( 2 === (int) $state['cot'] ) {
			$block_content = str_replace( 'nem-new-columns-3', 'nem-new-columns-2', $block_content );
		}
		if ( nem_sale_has_active_filters( $state ) || $state['trang'] > 1 ) {
			$catalog = new WP_Query( nem_sale_query_args( $state ) );
			if ( ! empty( $catalog->posts ) ) {
				ob_start();
				?>
				<div class="wp-block-group nem-new-products-grid nem-new-columns-<?php echo esc_attr( $state['cot'] ); ?>">
					<?php
					foreach ( $catalog->posts as $id ) :
						$product = wc_get_product( $id );
						if ( ! $product ) {
							continue;
						}
						get_template_part(
							'template-parts/collection-product',
							null,
							array(
								'product' => $product,
								'sku'     => $product->get_sku(),
							)
						);
					endforeach;
					?>
				</div>
				<?php
				return ob_get_clean();
			} else {
				return '<div class="nem-new-empty" role="status"><p>Không tìm thấy sản phẩm phù hợp.</p><a href="' . esc_url( nem_sale_products_url() ) . '">Xóa bộ lọc và xem tất cả sản phẩm SALE</a></div>';
			}
		}
		return $block_content;
	}

	// 6. Dynamic pagination when filters are active.
	if ( false !== strpos( $class_name, 'nem-new-pagination' ) ) {
		if ( nem_sale_has_active_filters( $state ) || $state['trang'] > 1 ) {
			$catalog = new WP_Query( nem_sale_query_args( $state ) );
			if ( $catalog->max_num_pages <= 1 ) {
				return '';
			}
			return '<nav class="nem-new-pagination" aria-label="Phân trang sản phẩm">' . wp_kses_post(
				paginate_links(
					array(
						'base'      => str_replace( '999999999', '%#%', esc_url( nem_sale_filter_url( $state, array( 'trang' => 999999999 ) ) ) ),
						'format'    => '',
						'current'   => $state['trang'],
						'total'     => $catalog->max_num_pages,
						'mid_size'  => 1,
						'end_size'  => 1,
						'prev_text' => '«',
						'next_text' => '»',
						'type'      => 'list',
					)
				)
			) . '</nav>';
		}
		return $block_content;
	}

	// 7. Replace newsletter placeholder with live form & social links.
	if ( false !== strpos( $class_name, 'nem-newsletter-form-placeholder' ) ) {
		return nem_sale_render_newsletter_controls();
	}

	return $block_content;
}, 10, 2 );

/**
 * Sync edits made to product cards inside the SALE Page editor back to WooCommerce products.
 */
add_action( 'save_post_page', function ( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	if ( 'sale' !== $post->post_name && 217 !== (int) $post_id ) {
		return;
	}
	if ( empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
		return;
	}

	$blocks      = parse_blocks( $post->post_content );
	$order_index = 0;
	$walk        = function ( $items ) use ( &$walk, &$order_index ) {
		foreach ( $items as $block ) {
			$block_name = $block['blockName'] ?? '';
			$class_name = $block['attrs']['className'] ?? '';
			if ( in_array( $block_name, array( 'core/group', 'core/column' ), true ) && preg_match( '/nem-product-id-(\d+)/', $class_name, $m ) ) {
				++$order_index;
				$product_id = absint( $m[1] );
				$product    = wc_get_product( $product_id );
				if ( $product && ! empty( $block['innerBlocks'] ) ) {
					$changed        = false;
					$has_online_tag = false;

					if ( (int) $product->get_menu_order() !== $order_index ) {
						$product->set_menu_order( $order_index );
						$changed = true;
					}

					foreach ( $block['innerBlocks'] as $inner ) {
						$inner_name  = $inner['blockName'] ?? '';
						$inner_class = $inner['attrs']['className'] ?? '';
						$inner_html  = $inner['innerHTML'] ?? '';

						if ( 'core/image' === $inner_name && ! empty( $inner['attrs']['id'] ) ) {
							$new_img_id = absint( $inner['attrs']['id'] );
							if ( $new_img_id && $new_img_id !== (int) $product->get_image_id() ) {
								$product->set_image_id( $new_img_id );
								$changed = true;
							}
						} elseif ( 'core/heading' === $inner_name ) {
							$title_text = trim( wp_strip_all_tags( $inner_html ) );
							if ( '' !== $title_text && $title_text !== $product->get_name() ) {
								$product->set_name( $title_text );
								$changed = true;
							}
						} elseif ( 'core/paragraph' === $inner_name && false !== strpos( $inner_class, 'nem-badge-online-only' ) ) {
							$badge_text = trim( wp_strip_all_tags( $inner_html ) );
							if ( '' !== $badge_text ) {
								$has_online_tag = true;
							}
						} elseif ( 'core/paragraph' === $inner_name && false !== strpos( $inner_class, 'nem-collection-product-price' ) ) {
							if ( preg_match( '/<strong>(.*?)<\/strong>/is', $inner_html, $pm_sale ) ) {
								$sale_num = preg_replace( '/[^\d]/', '', wp_strip_all_tags( $pm_sale[1] ) );
								if ( '' !== $sale_num && (float) $sale_num !== (float) $product->get_sale_price() ) {
									$product->set_sale_price( $sale_num );
									$product->set_price( $sale_num );
									$changed = true;
								}
							}
							if ( preg_match( '/<s>(.*?)<\/s>/is', $inner_html, $pm_reg ) ) {
								$reg_num = preg_replace( '/[^\d]/', '', wp_strip_all_tags( $pm_reg[1] ) );
								if ( '' !== $reg_num && (float) $reg_num !== (float) $product->get_regular_price() ) {
									$product->set_regular_price( $reg_num );
									$changed = true;
								}
							}
						}
					}

					$current_online = 'yes' === $product->get_meta( '_nem_online_only' );
					if ( $has_online_tag !== $current_online ) {
						$product->update_meta_data( '_nem_online_only', $has_online_tag ? 'yes' : 'no' );
						$changed = true;
					}

					if ( $changed ) {
						$product->save();
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};
	$walk( $blocks );
}, 10, 3 );

add_action( 'template_redirect', function () {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		if ( isset( $_GET['on_sale'] ) && '1' === (string) $_GET['on_sale'] ) {
			wp_safe_redirect( nem_sale_products_url(), 301 );
			exit;
		}
		if ( isset( $_GET['sale'] ) && in_array( $_GET['sale'], array( '199k', '70' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'sale', sanitize_title( $_GET['sale'] ), nem_sale_products_url() ), 301 );
			exit;
		}
	}
} );

add_filter( 'template_include', function ( $template ) {
	if ( nem_is_sale_page() && function_exists( 'WC' ) ) {
		return get_template_directory() . '/page-sale.php';
	}
	return $template;
}, 100 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! nem_is_sale_page() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'nem-collections', $uri . '/assets/css/nem-collections.css', array( 'nem-header-footer-style' ), filemtime( $dir . '/assets/css/nem-collections.css' ) );
	wp_enqueue_style( 'nem-new-products', $uri . '/assets/css/nem-new-products.css', array( 'nem-collections' ), filemtime( $dir . '/assets/css/nem-new-products.css' ) );
	wp_enqueue_script( 'nem-new-products', $uri . '/assets/js/nem-new-products.js', array(), filemtime( $dir . '/assets/js/nem-new-products.js' ), true );
}, 101 );

/**
 * Load NEM SALE styles inside the Gutenberg Block Editor (including the WP 7.x iframe canvas)
 * so editing Page "SALE" visually matches the live website (2-column layout + 3 products/row).
 */
add_action( 'after_setup_theme', function () {
	add_editor_style(
		array(
			'assets/css/nem-header-footer.css',
			'assets/css/nem-collections.css',
			'assets/css/nem-new-products.css',
			'assets/css/nem-sale-editor.css',
			'assets/css/nem-account.css',
		)
	);
}, 20 );

function nem_enqueue_sale_editor_assets() {
	if ( ! is_admin() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'nem-editor-header-footer', $uri . '/assets/css/nem-header-footer.css', array(), filemtime( $dir . '/assets/css/nem-header-footer.css' ) );
	wp_enqueue_style( 'nem-editor-collections', $uri . '/assets/css/nem-collections.css', array( 'nem-editor-header-footer' ), filemtime( $dir . '/assets/css/nem-collections.css' ) );
	wp_enqueue_style( 'nem-editor-new-products', $uri . '/assets/css/nem-new-products.css', array( 'nem-editor-collections' ), filemtime( $dir . '/assets/css/nem-new-products.css' ) );
	if ( file_exists( $dir . '/assets/css/nem-account.css' ) ) {
		wp_enqueue_style( 'nem-editor-account', $uri . '/assets/css/nem-account.css', array( 'nem-editor-header-footer' ), filemtime( $dir . '/assets/css/nem-account.css' ) );
	}
	if ( file_exists( $dir . '/assets/css/nem-sale-editor.css' ) ) {
		wp_enqueue_style( 'nem-editor-sale', $uri . '/assets/css/nem-sale-editor.css', array( 'nem-editor-new-products' ), filemtime( $dir . '/assets/css/nem-sale-editor.css' ) );
	}
	if ( file_exists( $dir . '/assets/js/nem-sale-editor.js' ) ) {
		wp_enqueue_script( 'nem-editor-sale-js', $uri . '/assets/js/nem-sale-editor.js', array(), filemtime( $dir . '/assets/js/nem-sale-editor.js' ), true );
	}
}
add_action( 'enqueue_block_editor_assets', 'nem_enqueue_sale_editor_assets' );
add_action( 'enqueue_block_assets', 'nem_enqueue_sale_editor_assets' );

add_filter( 'body_class', function ( $classes ) {
	if ( nem_is_sale_page() ) {
		$classes[] = 'nem-new-products-page';
		$classes[] = 'nem-sale-page';
	}
	return $classes;
} );

add_action( 'wp', function () {
	if ( nem_is_sale_page() ) {
		remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
		remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
	}
} );

add_action( 'storefront_before_footer', function () {
	if ( nem_is_sale_page() ) {
		get_template_part( 'template-parts/collection-newsletter', null, array( 'return_to' => 'sale' ) );
	}
} );
