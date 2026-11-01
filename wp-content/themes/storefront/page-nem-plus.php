<?php
/**
 * Template Name: NEM - NEM PLUS
 *
 * Synchronized with the NEM new products layout:
 * full-width hero banner, category sidebar, toolbar with attribute & price filters,
 * 2/3 columns toggle, product card badges, and pagination.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) {
	get_header();
	echo '<p>Danh mục sản phẩm đang được cập nhật.</p>';
	get_footer();
	return;
}

$state   = nem_plus_filter_state();
$catalog = new WP_Query( nem_plus_query_args( $state ) );

get_header();
?>
<main id="main" class="site-main nem-new-main nem-plus-main">
	<nav class="nem-new-container nem-new-breadcrumb" aria-label="Đường dẫn">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a>
		<span aria-hidden="true">/</span>
		<span aria-current="page">NEM PLUS</span>
	</nav>

	<div class="nem-new-banner">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/nem/nem-plus/banner.webp' ); ?>" alt="NEM PLUS+ In her Moment - Special Collection" width="1920" height="500" fetchpriority="high" decoding="async" />
	</div>

	<div class="nem-new-container nem-new-layout">
		<aside class="nem-new-sidebar" aria-label="Danh mục sản phẩm">
			<details class="nem-new-categories" open>
				<summary>Danh mục</summary>
				<nav aria-label="Lọc theo danh mục">
					<a class="nem-new-category-all <?php echo '' === $state['danh_muc'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_plus_filter_url( $state, array( 'danh_muc' => '', 'trang' => 1 ) ) ); ?>">Tất cả sản phẩm</a>
					<?php foreach ( nem_new_product_categories() as $slug => $category ) : ?>
					<div class="nem-new-category-group">
						<a class="nem-new-category-parent <?php echo $state['danh_muc'] === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_plus_filter_url( $state, array( 'danh_muc' => $slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $category['label'] ); ?></a>
						<?php foreach ( $category['children'] as $child_slug => $label ) : ?>
						<a class="nem-new-category-child <?php echo $state['danh_muc'] === $child_slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_plus_filter_url( $state, array( 'danh_muc' => $child_slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>
				</nav>
			</details>
		</aside>

		<section class="nem-new-catalog" aria-labelledby="nem-new-title">
			<div class="nem-new-toolbar">
				<h1 id="nem-new-title">NEM PLUS</h1>
				<?php get_template_part( 'template-parts/catalog-filters', null, array( 'state' => $state, 'url' => nem_plus_products_url() ) ); ?>

				<div class="nem-new-grid-choices" aria-label="Số cột sản phẩm">
					<?php foreach ( array( 3, 2 ) as $columns ) : ?>
						<a class="<?php echo $state['cot'] === $columns ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_plus_filter_url( $state, array( 'cot' => $columns ) ) ); ?>" aria-label="<?php echo esc_attr( 'Hiển thị ' . $columns . ' cột' ); ?>" <?php echo $state['cot'] === $columns ? 'aria-current="true"' : ''; ?>>
							<?php get_template_part( 'template-parts/catalog-grid-icon', null, array( 'columns' => $columns ) ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $state['danh_muc'] || $state['gia'] || ! empty( $state['kich_co'] ) || ! empty( $state['mau_sac'] ) ) : ?>
				<div class="nem-new-filter-status" role="status">
					<span><?php echo esc_html( $catalog->found_posts . ' sản phẩm phù hợp' ); ?></span>
					<a href="<?php echo esc_url( nem_plus_products_url() ); ?>">Xóa bộ lọc</a>
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
									'base'      => str_replace( '999999999', '%#%', esc_url( nem_plus_filter_url( $state, array( 'trang' => 999999999 ) ) ) ),
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
					<a href="<?php echo esc_url( nem_plus_products_url() ); ?>">Xóa bộ lọc và xem tất cả sản phẩm NEM PLUS</a>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
get_footer();
