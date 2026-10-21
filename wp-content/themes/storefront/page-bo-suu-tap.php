<?php
/**
 * Template Name: NEM - Bộ sưu tập
 * Collection stories with live WooCommerce products.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main nem-collections-main">
	<div class="nem-collections-container">
		<nav class="nem-collections-breadcrumb" aria-label="Đường dẫn">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a><span aria-hidden="true">/</span><span aria-current="page">BỘ SƯU TẬP</span>
		</nav>
		<h1 class="screen-reader-text">Bộ sưu tập NEM</h1>
		<?php foreach ( nem_collections_catalog() as $slug => $collection ) : ?>
		<section class="nem-collection-story" id="<?php echo esc_attr( $slug ); ?>" aria-labelledby="title-<?php echo esc_attr( $slug ); ?>">
			<a class="nem-collection-banner" href="<?php echo esc_url( nem_collection_url( $slug ) ); ?>" aria-label="<?php echo esc_attr( 'Khám phá ' . $collection['title'] ); ?>">
				<img src="<?php echo esc_url( nem_collection_asset( $collection['banner'] ) ); ?>" alt="<?php echo esc_attr( $collection['title'] ); ?>" <?php echo 'thoi-nha' === $slug ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async" />
			</a>
			<div class="nem-collection-layout <?php echo 'nhe-buoc-moi-ngay' === $slug ? 'nem-collection-layout-reverse' : ''; ?>">
				<div class="nem-collection-products-and-copy">
					<div class="nem-collection-products">
						<?php foreach ( $collection['skus'] as $sku ) :
							$product = nem_collection_product( $sku );
							if ( ! $product ) { continue; }
							get_template_part( 'template-parts/collection-product', null, array( 'product' => $product, 'sku' => $sku ) );
						endforeach; ?>
					</div>
					<div class="nem-collection-copy">
						<h2 id="title-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $collection['title'] ); ?></h2>
						<p><?php echo esc_html( $collection['description'] ); ?></p>
						<a class="nem-collection-more" href="<?php echo esc_url( nem_collection_url( $slug ) ); ?>">XEM THÊM<span class="screen-reader-text"> <?php echo esc_html( $collection['title'] ); ?></span></a>
					</div>
				</div>
				<div class="nem-collection-editorials">
					<?php foreach ( $collection['editorials'] as $index => $image ) : ?>
					<a href="<?php echo esc_url( nem_collection_url( $slug ) ); ?>" aria-label="<?php echo esc_attr( 'Xem ' . $collection['title'] ); ?>">
						<img src="<?php echo esc_url( nem_collection_asset( $image ) ); ?>" alt="<?php echo esc_attr( $collection['title'] . ' - Phong cách ' . ( $index + 1 ) ); ?>" loading="lazy" decoding="async" />
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endforeach; ?>
	</div>
</main>
<?php get_footer(); ?>
