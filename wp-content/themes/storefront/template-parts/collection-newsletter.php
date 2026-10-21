<?php
defined( 'ABSPATH' ) || exit;
$status = isset( $_GET['newsletter'] ) && is_string( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
$messages = array( 'ok' => 'Cảm ơn bạn đã đăng ký nhận bản tin NEM!', 'exists' => 'Email này đã đăng ký nhận bản tin.', 'error' => 'Vui lòng nhập email hợp lệ và thử lại.' );
?>
<section class="nem-collection-newsletter" id="nem-collection-newsletter" aria-labelledby="nem-collection-newsletter-title">
	<h2 id="nem-collection-newsletter-title">ĐĂNG KÝ BẢN TIN</h2>
	<p>Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất</p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="nem_collection_newsletter" />
		<?php wp_nonce_field( 'nem_collection_newsletter', 'nem_collection_nonce' ); ?>
		<label class="screen-reader-text" for="nem-collection-email">Email</label>
		<input type="email" id="nem-collection-email" name="email" maxlength="250" placeholder="Vui lòng nhập email..." required />
		<button type="submit">ĐĂNG KÝ</button>
	</form>
	<?php if ( isset( $messages[ $status ] ) ) : ?><p role="status"><?php echo esc_html( $messages[ $status ] ); ?></p><?php endif; ?>
	<div class="nem-collection-socials" aria-label="Mạng xã hội NEM">
		<a class="nem-collection-facebook" href="https://www.facebook.com/nemfashion.official" aria-label="Facebook NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.7-1.5H18V3.2c-.4-.1-1.8-.2-3.1-.2C11.9 3 10 4.8 10 8v2H7v3h3v8z"/></svg></a>
		<a class="nem-collection-instagram" href="https://www.instagram.com/nemfashion.official/" aria-label="Instagram NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="7" r="1"/></svg></a>
		<a class="nem-collection-youtube" href="https://www.youtube.com/@nemfashion" aria-label="YouTube NEM" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 8 6 4-6 4z" fill="#ff0000"/></svg></a>
	</div>
</section>
