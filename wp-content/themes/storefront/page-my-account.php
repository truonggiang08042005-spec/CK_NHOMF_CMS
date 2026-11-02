<?php
/** My account uses the saved design while WooCommerce owns customer actions. */
defined( 'ABSPATH' ) || exit;
if ( ! nem_account_uses_editor() ) {
	require __DIR__ . '/page.php';
	return;
}
get_header();
?>
<main id="main" class="site-main nem-account-main">
	<?php
	if ( ! function_exists( 'WC' ) ) {
		echo '<p>Hệ thống tài khoản đang được bảo trì.</p>';
	} elseif ( is_wc_endpoint_url() ) {
		// Password recovery, order details, payments and address forms stay with WooCommerce.
		if ( ! is_user_logged_in() || is_wc_endpoint_url( 'lost-password' ) ) {
			echo do_shortcode( '[woocommerce_my_account]' );
		} else {
			wc_print_notices();
			do_action( 'woocommerce_account_content' );
		}
		echo '<div class="nem-account-design">' . nem_account_endpoint_newsletter() . '</div>';
	} else {
		wc_print_notices();
		while ( have_posts() ) {
			the_post();
			the_content();
		}
		if ( is_user_logged_in() ) get_template_part( 'template-parts/account-address-modal' );
	}
	?>
</main>
<?php get_footer();
