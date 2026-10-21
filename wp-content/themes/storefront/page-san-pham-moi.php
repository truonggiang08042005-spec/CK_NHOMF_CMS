<?php
/** Template Name: NEM - Sản phẩm mới */
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'WC' ) ) { get_header(); echo '<p>Danh mục sản phẩm đang được cập nhật.</p>'; get_footer(); return; }
$state = nem_new_filter_state();
$catalog = new WP_Query( nem_new_query_args( $state ) );
get_header();
?>
<main id="main" class="site-main nem-new-main">
	<nav class="nem-new-container nem-new-breadcrumb" aria-label="Đường dẫn"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a><span aria-hidden="true">/</span><span aria-current="page">SẢN PHẨM MỚI</span></nav>
	<div class="nem-new-banner"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/nem/new-products/banner.webp' ); ?>" alt="Nhẹ bước mỗi ngày - Casual Collection NEM" width="1920" height="500" fetchpriority="high" decoding="async" /></div>
	<div class="nem-new-container nem-new-layout">
		<aside class="nem-new-sidebar" aria-label="Danh mục sản phẩm">
			<details class="nem-new-categories" open>
				<summary>Danh mục</summary>
				<nav aria-label="Lọc theo danh mục">
					<a class="nem-new-category-all <?php echo '' === $state['danh_muc'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_new_filter_url( $state, array( 'danh_muc' => '', 'trang' => 1 ) ) ); ?>">Tất cả sản phẩm</a>
					<?php foreach ( nem_new_product_categories() as $slug => $category ) : ?>
					<div class="nem-new-category-group">
						<a class="nem-new-category-parent <?php echo $state['danh_muc'] === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_new_filter_url( $state, array( 'danh_muc' => $slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $category['label'] ); ?></a>
						<?php foreach ( $category['children'] as $child_slug => $label ) : ?>
						<a class="nem-new-category-child <?php echo $state['danh_muc'] === $child_slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_new_filter_url( $state, array( 'danh_muc' => $child_slug, 'trang' => 1 ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>
				</nav>
			</details>
		</aside>
		<section class="nem-new-catalog" aria-labelledby="nem-new-title">
			<div class="nem-new-toolbar">
				<h1 id="nem-new-title">SẢN PHẨM MỚI</h1>
				<form class="nem-new-filters" method="get" action="<?php echo esc_url( nem_new_products_url() ); ?>">
					<input type="hidden" name="cot" value="<?php echo esc_attr( $state['cot'] ); ?>" />
					<?php if ( $state['danh_muc'] ) : ?><input type="hidden" name="danh_muc" value="<?php echo esc_attr( $state['danh_muc'] ); ?>" /><?php endif; ?>
					<?php foreach ( array( 'kich_co' => array( 'label' => 'Kích cỡ', 'taxonomy' => 'pa_kich-co' ), 'mau_sac' => array( 'label' => 'Màu sắc', 'taxonomy' => 'pa_mau-sac' ) ) as $key => $filter ) :
						$terms = get_terms( array( 'taxonomy' => $filter['taxonomy'], 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
						if ( ! is_wp_error( $terms ) ) { usort( $terms, function ( $a, $b ) { return strnatcasecmp( $a->name, $b->name ); } ); } ?>
					<details class="nem-new-filter <?php echo $state[ $key ] ? 'has-selection' : ''; ?>">
						<summary><?php echo esc_html( $filter['label'] ); ?><?php if ( $state[ $key ] ) { echo ' (' . count( $state[ $key ] ) . ')'; } ?><span aria-hidden="true">⌄</span></summary>
						<div class="nem-new-filter-panel"><fieldset><legend class="screen-reader-text"><?php echo esc_html( $filter['label'] ); ?></legend>
						<?php if ( ! is_wp_error( $terms ) && $terms ) : foreach ( $terms as $term ) : ?>
							<label><input type="checkbox" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $state[ $key ], true ) ); ?> /><span><?php echo esc_html( $term->name ); ?></span></label>
						<?php endforeach; else : ?><p>Chưa có lựa chọn.</p><?php endif; ?>
						</fieldset><button type="submit">Áp dụng</button></div>
					</details>
					<?php endforeach; ?>
					<details class="nem-new-filter <?php echo $state['gia'] ? 'has-selection' : ''; ?>"><summary>Giá<span aria-hidden="true">⌄</span></summary>
						<div class="nem-new-filter-panel"><fieldset><legend class="screen-reader-text">Khoảng giá</legend>
							<label><input type="radio" name="gia" value="" <?php checked( '', $state['gia'] ); ?> /><span>Tất cả</span></label>
							<?php foreach ( nem_new_price_ranges() as $key => $range ) : ?><label><input type="radio" name="gia" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $state['gia'] ); ?> /><span><?php echo esc_html( $range['label'] ); ?></span></label><?php endforeach; ?>
						</fieldset><button type="submit">Áp dụng</button></div>
					</details>
				</form>
				<div class="nem-new-grid-choices" aria-label="Số cột sản phẩm">
					<?php foreach ( array( 3, 2 ) as $columns ) : ?><a class="<?php echo $state['cot'] === $columns ? 'is-active' : ''; ?>" href="<?php echo esc_url( nem_new_filter_url( $state, array( 'cot' => $columns ) ) ); ?>" aria-label="<?php echo esc_attr( 'Hiển thị ' . $columns . ' cột' ); ?>" <?php echo $state['cot'] === $columns ? 'aria-current="true"' : ''; ?>><svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true"><?php for ( $row = 0; $row < 3; ++$row ) : for ( $column = 0; $column < $columns; ++$column ) : ?><rect x="<?php echo esc_attr( 1 + $column * ( 16 / $columns ) ); ?>" y="<?php echo esc_attr( 1 + $row * 5.5 ); ?>" width="<?php echo esc_attr( 14 / $columns ); ?>" height="4.5" /><?php endfor; endfor; ?></svg></a><?php endforeach; ?>
				</div>
			</div>
			<?php if ( $state['danh_muc'] || $state['gia'] || $state['kich_co'] || $state['mau_sac'] ) : ?><div class="nem-new-filter-status" role="status"><span><?php echo esc_html( $catalog->found_posts . ' sản phẩm phù hợp' ); ?></span><a href="<?php echo esc_url( nem_new_products_url() ); ?>">Xóa bộ lọc</a></div><?php endif; ?>
			<?php if ( $catalog->posts ) : ?>
			<div class="nem-new-products-grid nem-new-columns-<?php echo esc_attr( $state['cot'] ); ?>">
				<?php foreach ( $catalog->posts as $id ) : $product = wc_get_product( $id ); if ( ! $product ) { continue; } get_template_part( 'template-parts/collection-product', null, array( 'product' => $product, 'sku' => $product->get_sku() ) ); endforeach; ?>
			</div>
			<?php if ( $catalog->max_num_pages > 1 ) : ?><nav class="nem-new-pagination" aria-label="Phân trang sản phẩm"><?php echo wp_kses_post( paginate_links( array( 'base' => str_replace( '999999999', '%#%', esc_url( nem_new_filter_url( $state, array( 'trang' => 999999999 ) ) ) ), 'format' => '', 'current' => $state['trang'], 'total' => $catalog->max_num_pages, 'mid_size' => 1, 'end_size' => 1, 'prev_text' => '«', 'next_text' => '»', 'type' => 'list' ) ) ); ?></nav><?php endif; ?>
			<?php else : ?><div class="nem-new-empty" role="status"><p>Không tìm thấy sản phẩm phù hợp.</p><a href="<?php echo esc_url( nem_new_products_url() ); ?>">Xóa bộ lọc và xem tất cả sản phẩm mới</a></div><?php endif; ?>
		</section>
	</div>
</main>
<?php get_footer(); ?>
