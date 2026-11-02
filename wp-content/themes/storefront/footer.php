<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<?php if ( ( ! function_exists( 'nem_new_uses_editor' ) || ! nem_new_uses_editor() ) && ( ! function_exists( 'nem_plus_uses_editor' ) || ! nem_plus_uses_editor() ) && ( ! function_exists( 'nem_sale_uses_editor' ) || ! nem_sale_uses_editor() ) ) : ?>
	<footer id="colophon" class="site-footer nem-site-footer" role="contentinfo">
		<?php
		get_template_part( 'template-parts/footer', 'nem' );
		do_action( 'storefront_footer' );
		?>
	</footer><!-- #colophon -->
	<?php else : ?>
		<?php get_template_part( 'template-parts/floating-actions' ); ?>
	<?php endif; ?>

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
