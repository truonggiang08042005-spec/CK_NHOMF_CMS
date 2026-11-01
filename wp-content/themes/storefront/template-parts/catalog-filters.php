<?php
/** Attribute and price controls for new arrivals, NEM PLUS+ and sale pages. */
defined( 'ABSPATH' ) || exit;
$state = $args['state'];
$url   = $args['url'];
?>
<form class="nem-new-filters" method="get" action="<?php echo esc_url( $url ); ?>">
	<?php foreach ( array( 'cot', 'danh_muc', 'sale' ) as $key ) : ?>
		<?php if ( ! empty( $state[ $key ] ) ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $state[ $key ] ); ?>" />
		<?php endif; ?>
	<?php endforeach; ?>
	<?php foreach ( array( 'kich_co' => array( 'label' => 'Kích cỡ', 'type' => 'sizes', 'taxonomy' => 'pa_kich-co' ), 'mau_sac' => array( 'label' => 'Màu sắc', 'type' => 'colors', 'taxonomy' => 'pa_mau-sac' ) ) as $key => $filter ) :
		$terms = get_terms( array( 'taxonomy' => $filter['taxonomy'], 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
		if ( ! is_wp_error( $terms ) ) {
			usort( $terms, function ( $a, $b ) { return strnatcasecmp( $a->name, $b->name ); } );
		}
		?>
		<details class="nem-new-filter nem-new-filter--<?php echo esc_attr( $filter['type'] ); ?> <?php echo $state[ $key ] ? 'has-selection' : ''; ?>">
			<summary><?php echo esc_html( $filter['label'] ); ?><?php if ( $state[ $key ] ) { echo ' (' . count( $state[ $key ] ) . ')'; } ?><span aria-hidden="true"></span></summary>
			<div class="nem-new-filter-panel">
				<fieldset class="nem-catalog-options nem-catalog-options--<?php echo esc_attr( $filter['type'] ); ?>">
					<legend class="screen-reader-text"><?php echo esc_html( $filter['label'] ); ?></legend>
					<?php if ( ! is_wp_error( $terms ) && $terms ) : ?>
						<?php foreach ( $terms as $term ) : ?>
							<label class="nem-catalog-<?php echo 'colors' === $filter['type'] ? 'color' : 'size'; ?>" title="<?php echo esc_attr( $term->name ); ?>">
								<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $state[ $key ], true ) ); ?> />
								<?php if ( 'colors' === $filter['type'] ) : ?>
									<span class="nem-catalog-color-dot" style="<?php echo esc_attr( nem_catalog_swatch_style( $term ) ); ?>" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html( $term->name ); ?></span>
								<?php else : ?>
									<span><?php echo esc_html( preg_match( '/^\d+$/', $term->name ) ? 'Size ' . $term->name : $term->name ); ?></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					<?php else : ?><p>Chưa có lựa chọn.</p><?php endif; ?>
				</fieldset>
				<button type="submit" class="nem-catalog-apply">Áp dụng</button>
			</div>
		</details>
	<?php endforeach; ?>
	<details class="nem-new-filter nem-new-filter--prices <?php echo $state['gia'] ? 'has-selection' : ''; ?>">
		<summary>Giá<span aria-hidden="true"></span></summary>
		<div class="nem-new-filter-panel">
			<fieldset class="nem-catalog-options nem-catalog-options--prices">
				<legend class="screen-reader-text">Khoảng giá</legend>
				<label><input type="radio" name="gia" value="" <?php checked( '', $state['gia'] ); ?> /><span>Tất cả</span></label>
				<?php foreach ( nem_new_price_ranges() as $key => $range ) : ?>
					<label><input type="radio" name="gia" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $state['gia'] ); ?> /><span><?php echo esc_html( $range['label'] ); ?></span></label>
				<?php endforeach; ?>
			</fieldset>
			<button type="submit" class="nem-catalog-apply">Áp dụng</button>
		</div>
	</details>
</form>
