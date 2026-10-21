<?php
/**
 * NEM - Khối "ĐĂNG KÝ BẢN TIN"
 */

$nem_nl_status   = isset( $_GET['nem_newsletter'] ) ? sanitize_key( $_GET['nem_newsletter'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$nem_nl_messages = array(
	'ok'      => array( 'success', 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!' ),
	'exists'  => array( 'info', 'Email này đã đăng ký nhận bản tin trước đó.' ),
	'invalid' => array( 'error', 'Email không hợp lệ, vui lòng thử lại.' ),
	'error'   => array( 'error', 'Có lỗi xảy ra, vui lòng thử lại.' ),
);
?>
<section class="nem-nl" id="nem-newsletter" aria-labelledby="nem-nl-title">
	<div class="nem-nl__inner">
		<h2 class="nem-nl__title" id="nem-nl-title">Đăng ký bản tin</h2>
		<p class="nem-nl__desc">Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất</p>

		<form class="nem-nl__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="nem_newsletter_subscribe" />
			<?php wp_nonce_field( 'nem_newsletter', 'nem_newsletter_nonce' ); ?>
			<label class="screen-reader-text" for="nem-nl-email">Email</label>
			<input maxlength="250" type="email" class="nem-nl__input" id="nem-nl-email" name="nem_newsletter_email" placeholder="Vui lòng nhập email..." required />
			<button type="submit" class="nem-nl__btn" id="nem-nl-submit">Đăng ký</button>
		</form>

		<?php if ( isset( $nem_nl_messages[ $nem_nl_status ] ) ) : ?>
			<p class="nem-nl__msg nem-nl__msg--<?php echo esc_attr( $nem_nl_messages[ $nem_nl_status ][0] ); ?>" role="status">
				<?php echo esc_html( $nem_nl_messages[ $nem_nl_status ][1] ); ?>
			</p>
		<?php endif; ?>

		<div class="nem-nl__social">
			<a href="https://www.facebook.com/nemfashion.official" target="_blank" rel="noopener noreferrer" class="nem-nl__icon nem-nl__icon--fb" title="Facebook" aria-label="Facebook">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="#fff" aria-hidden="true"><path d="M13.5 22v-8.2h2.8l.4-3.2h-3.2V8.5c0-.9.3-1.6 1.6-1.6h1.7V4.1c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3v3.2h2.8V22h3.4z"/></svg>
			</a>
			<a href="https://www.instagram.com/nemfashion.official/" target="_blank" rel="noopener noreferrer" class="nem-nl__icon nem-nl__icon--ig" title="Instagram" aria-label="Instagram">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2.2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1" fill="#fff" stroke="none"/></svg>
			</a>
			<a href="https://www.youtube.com/channel/UCrDQ6euiL9CekivZmD9-MCA" target="_blank" rel="noopener noreferrer" class="nem-nl__icon nem-nl__icon--yt" title="YouTube" aria-label="YouTube">
				<svg viewBox="0 0 24 24" width="15" height="15" fill="#fff" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>
			</a>
		</div>
	</div>
</section>
