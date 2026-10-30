<?php
/** Square layout icon shared by the catalog pages. */
defined( 'ABSPATH' ) || exit;
$columns = isset( $args['columns'] ) && 2 === (int) $args['columns'] ? 2 : 3;
$cell = ( 18 - ( $columns - 1 ) ) / $columns;
?>
<svg class="nem-catalog-grid-icon" viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false">
	<?php for ( $row = 0; $row < $columns; ++$row ) : ?>
		<?php for ( $column = 0; $column < $columns; ++$column ) : ?>
			<rect x="<?php echo esc_attr( 1 + $column * ( $cell + 1 ) ); ?>" y="<?php echo esc_attr( 1 + $row * ( $cell + 1 ) ); ?>" width="<?php echo esc_attr( $cell ); ?>" height="<?php echo esc_attr( $cell ); ?>" />
		<?php endfor; ?>
	<?php endfor; ?>
</svg>
