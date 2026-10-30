<?php
/**
 * Template Name: NEM - SALE
 *
 * Renders the editable Gutenberg block content of the SALE page (if populated in All Pages),
 * enhanced with live WooCommerce category/attribute/price filters, column switcher, and pagination.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) {
	get_header();
	echo '<p>Danh mục sản phẩm đang được cập nhật.</p>';
	get_footer();
	return;
}

$state   = nem_sale_filter_state();
$catalog = new WP_Query( nem_sale_query_args( $state ) );

get_header();
?>
<main id="main" class="site-main nem-new-main nem-sale-main">
	<nav class="nem-new-container nem-new-breadcrumb" aria-label="Đường dẫn">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a>
		<span aria-hidden="true">/</span>
		<span aria-current="page">SALE</span>
	</nav>

	<div class="nem-new-banner">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/nem/sale/banner.webp' ); ?>" alt="NEM SALE - GIẢM 70% HÀNG NGÀN SẢN PHẨM HÈ" width="1920" height="500" fetchpriority="high" decoding="async" />
	</div>

	<div class="nem-new-container nem-new-layout">
		<aside class="nem-new-sidebar" aria-label="Danh mục sản phẩm">
			<details class="nem-new-categories" open>
				<summary>Danh mục</summary>
				<nav aria-label="Lọc theo danh mục">
					<a class="nem-new-category-all <?php echo '' === $state['danh_muc'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_sale_filter_url( $state, array( 'danh_muc' => '', 'trang' => 1 ) ) ); ?>">Tất cả sản phẩm</a>
					<?php foreach ( nem_new_product_categories() as $slug => $category ) : ?>
					<div class="nem-new-category-group">
						<a class="nem-new-category-parent <?php echo $state['danh_muc'] === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_sale_filter_url( $state, array( 'danh_muc' => $slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $category['label'] ); ?></a>
						<?php foreach ( $category['children'] as $child_slug => $label ) : ?>
						<a class="nem-new-category-child <?php echo $state['danh_muc'] === $child_slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_sale_filter_url( $state, array( 'danh_muc' => $child_slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>
				</nav>
			</details>
		</aside>

		<section class="nem-new-catalog" aria-labelledby="nem-new-title">
			<div class="nem-new-toolbar">
				<h1 id="nem-new-title">SALE</h1>
				<?php echo nem_sale_render_toolbar_controls( $state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php if ( nem_sale_has_active_filters( $state ) ) : ?>
				<div class="nem-new-filter-status" role="status">
					<span><?php echo esc_html( $catalog->found_posts . ' sản phẩm phù hợp' ); ?></span>
					<a href="<?php echo esc_url( nem_sale_products_url() ); ?>">Xóa bộ lọc</a>
				</div>
			<?php endif; ?>

			<?php if ( $catalog->posts ) : ?>
				<div class="nem-new-products-grid nem-new-columns-<?php echo esc_attr( $state['cot'] ); ?>">
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

				<?php if ( $catalog->max_num_pages > 1 ) : ?>
					<nav class="nem-new-pagination" aria-label="Phân trang sản phẩm">
						<?php
						echo wp_kses_post(
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
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="nem-new-empty" role="status">
					<p>Không tìm thấy sản phẩm phù hợp.</p>
					<a href="<?php echo esc_url( nem_sale_products_url() ); ?>">Xóa bộ lọc và xem tất cả sản phẩm SALE</a>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
get_footer();
