<?php
/**
 * The template for displaying search results pages.
 *
 * @package storefront
 */

$search_post_type = get_query_var( 'post_type' );
$is_product_search = function_exists( 'WC' ) && (
	'product' === $search_post_type ||
	( is_array( $search_post_type ) && in_array( 'product', $search_post_type, true ) )
);

get_header();
?>

<div id="primary" class="content-area<?php echo $is_product_search ? ' nem-product-search' : ''; ?>">
	<main id="main" class="site-main" role="main">
		<?php if ( $is_product_search ) : ?>
			<nav class="nem-search-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'storefront' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Trang chủ', 'storefront' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php esc_html_e( 'Tìm kiếm', 'storefront' ); ?></span>
			</nav>

			<header class="nem-search-header">
				<h1>
					<?php esc_html_e( 'Kết quả tìm kiếm:', 'storefront' ); ?>
					<span><?php echo esc_html( get_search_query() ); ?></span>
				</h1>
			</header>

			<?php if ( have_posts() ) : ?>
				<?php
				wc_set_loop_prop( 'columns', 4 );
				woocommerce_product_loop_start();

				while ( have_posts() ) :
					the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;

				woocommerce_product_loop_end();
				woocommerce_pagination();
				wc_reset_loop();
				?>
			<?php else : ?>
				<div class="nem-search-empty">
					<p>
						<?php
						printf(
							/* translators: %s: search query */
							esc_html__( 'Không tìm thấy kết quả tìm kiếm phù hợp cho: %s', 'storefront' ),
							'<strong>' . esc_html( get_search_query() ) . '</strong>'
						);
						?>
					</p>
					<form role="search" method="get" class="nem-search-empty-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="nem-search-empty-input"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'storefront' ); ?></label>
						<input type="search" id="nem-search-empty-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Tìm kiếm sản phẩm...', 'storefront' ); ?>">
						<input type="hidden" name="post_type" value="product">
						<button type="submit"><?php esc_html_e( 'Tìm kiếm', 'storefront' ); ?></button>
					</form>
				</div>
			<?php endif; ?>
		<?php elseif ( have_posts() ) : ?>
			<header class="page-header">
				<h1 class="page-title">
					<?php
					/* translators: %s: search term */
					printf(
						esc_html__( 'Search Results for: %s', 'storefront' ),
						'<span>' . esc_html( get_search_query() ) . '</span>'
					);
					?>
				</h1>
			</header>

			<?php get_template_part( 'loop' ); ?>
		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>
		<?php endif; ?>
	</main>
</div>

<?php
if ( ! $is_product_search ) {
	do_action( 'storefront_sidebar' );
}
get_footer();
