<?php
/** Product card: a second photograph and a sliding detail overlay. */
defined( 'ABSPATH' ) || exit;
$product = $args['product'];
$sku = $args['sku'];
$image_files = $product->get_meta( '_nem_collection_images' );
$image_files = is_array( $image_files ) ? array_values( array_filter( $image_files, function ( $filename ) { return is_string( $filename ) && '' !== trim( $filename ); } ) ) : array();
$images = array_map( 'nem_collection_asset', $image_files );
$primary = $images[0] ?? ( wp_get_attachment_image_url( $product->get_image_id(), 'large' ) ?: wc_placeholder_img_src( 'large' ) );
$gallery = $product->get_gallery_image_ids();
$secondary = $images[1] ?? ( ! empty( $gallery ) ? wp_get_attachment_image_url( $gallery[0], 'large' ) : $primary );
$secondary = $secondary ?: $primary;
$is_online_only = 'yes' === $product->get_meta( '_nem_online_only' );
$show_quantity = isset( $args['show_quantity'] ) ? (bool) $args['show_quantity'] : (
	( function_exists( 'nem_is_plus_page' ) && nem_is_plus_page() ) ||
	( function_exists( 'nem_plus_uses_editor' ) && nem_plus_uses_editor() ) ||
	( function_exists( 'nem_plus_is_editor_screen' ) && nem_plus_is_editor_screen() )
);
?>
<article class="nem-collection-product" data-sku="<?php echo esc_attr( $sku ); ?>">
	<a class="nem-collection-product-image" href="<?php echo esc_url( $product->get_permalink() ); ?>" aria-label="<?php echo esc_attr( 'Xem thêm ' . $product->get_name() ); ?>">
		<img class="nem-collection-image-primary" src="<?php echo esc_url( $primary ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" width="600" height="900" loading="lazy" decoding="async" />
		<img class="nem-collection-image-secondary" src="<?php echo esc_url( $secondary ); ?>" alt="" aria-hidden="true" width="600" height="900" loading="lazy" decoding="async" />
		<?php if ( $is_online_only ) : ?>
			<span class="nem-badge-online-only">ONLINE ONLY</span>
		<?php endif; ?>
		<?php if ( $product->is_on_sale() && $product->get_regular_price() ) : ?>
			<span class="nem-collection-sale">-<?php echo esc_html( round( ( 1 - (float) $product->get_price() / (float) $product->get_regular_price() ) * 100 ) ); ?>%</span>
		<?php endif; ?>
		<span class="nem-collection-product-overlay"><span class="nem-collection-view-more">XEM THÊM</span></span>
	</a>
	<h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
	<div class="nem-collection-product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
	<?php if ( $show_quantity ) : ?>
		<div class="nem-product-quantity-wrapper">
			<label class="nem-product-quantity-label" for="nem-qty-<?php echo esc_attr( $product->get_id() ); ?>">Số lượng:</label>
			<div class="nem-product-quantity-control">
				<button type="button" class="nem-qty-btn nem-qty-minus" aria-label="Giảm số lượng">-</button>
				<input type="number" id="nem-qty-<?php echo esc_attr( $product->get_id() ); ?>" class="nem-qty-input" name="quantity" value="1" min="1" <?php echo $product->get_max_purchase_quantity() > 0 ? 'max="' . esc_attr( $product->get_max_purchase_quantity() ) . '"' : ''; ?> step="1" inputmode="numeric" />
				<button type="button" class="nem-qty-btn nem-qty-plus" aria-label="Tăng số lượng">+</button>
			</div>
			<?php
			$stock_qty = $product->get_stock_quantity();
			if ( null !== $stock_qty ) : ?>
				<span class="nem-product-stock-hint">(Còn <?php echo esc_html( $stock_qty ); ?>)</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</article>
