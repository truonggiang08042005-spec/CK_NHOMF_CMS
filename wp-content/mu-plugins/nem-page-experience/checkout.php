<?php
defined('ABSPATH') || exit;
if (null === WC()->cart) wc_load_cart();
$cart = WC()->cart;
$checkout = WC()->checkout();
$cart->calculate_totals();
$value = function ($key) use ($checkout) { return $checkout->get_value($key); };
$contact = function () use ($value) { ?>
    <section class="nem-card" data-npe-section="contact"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('contact_title', 'Thông tin liên hệ')); ?></h2>
        <p class="nem-form-group form-row"><label for="billing_first_name">Họ và tên <span aria-hidden="true">*</span></label><input id="billing_first_name" type="text" name="billing_first_name" class="nem-input input-text" autocomplete="name" value="<?php echo esc_attr($value('billing_first_name')); ?>" required></p>
        <p class="nem-form-group form-row"><label for="billing_phone">Số điện thoại <span aria-hidden="true">*</span></label><input id="billing_phone" type="tel" name="billing_phone" class="nem-input input-text" autocomplete="tel" value="<?php echo esc_attr($value('billing_phone')); ?>" required></p>
        <p class="nem-form-group form-row"><label for="billing_email">Email</label><input id="billing_email" type="email" name="billing_email" class="nem-input input-text" autocomplete="email" value="<?php echo esc_attr($value('billing_email')); ?>" placeholder="Email nhận thông tin đơn hàng"></p>
    </section>
<?php };
$address = function () use ($value) { ?>
    <section class="nem-card" data-npe-section="address"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('address_title', 'Địa chỉ giao hàng')); ?></h2>
        <input type="hidden" id="billing_country" name="billing_country" value="VN">
        <input type="hidden" id="billing_last_name" name="billing_last_name" value="">
        <p class="nem-form-group form-row address-field"><label for="billing_address_1">Số nhà, tên đường <span aria-hidden="true">*</span></label><input type="text" id="billing_address_1" name="billing_address_1" class="nem-input input-text" autocomplete="address-line1" value="<?php echo esc_attr($value('billing_address_1')); ?>" required></p>
        <p class="nem-form-group form-row address-field"><label for="billing_address_2">Phường/Xã, Quận/Huyện</label><input type="text" id="billing_address_2" name="billing_address_2" class="nem-input input-text" autocomplete="address-line2" value="<?php echo esc_attr($value('billing_address_2')); ?>"></p>
        <p class="nem-form-group form-row address-field update_totals_on_change"><label for="billing_city">Tỉnh/Thành phố <span aria-hidden="true">*</span></label><input type="text" id="billing_city" name="billing_city" class="nem-input input-text" autocomplete="address-level1" value="<?php echo esc_attr($value('billing_city')); ?>" required></p>
    </section>
<?php };
$shipping = function () { ?><section class="nem-card" data-npe-section="shipping"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('shipping_title', 'Phương thức giao hàng')); ?></h2><?php echo npe_checkout_shipping(); ?></section><?php };
$payment = function () { ?><section class="nem-card" data-npe-section="payment"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('payment_title', 'Phương thức thanh toán')); ?></h2><?php echo npe_checkout_payments(); ?></section><?php };
$notes = function () use ($value) { ?><section class="nem-card" data-npe-section="notes"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('order_notes_title', 'Ghi chú đơn hàng')); ?></h2><label class="screen-reader-text" for="order_comments">Ghi chú đơn hàng</label><textarea id="order_comments" name="order_comments" class="nem-input" rows="3" placeholder="Lưu ý cho cửa hàng và đơn vị giao hàng"><?php echo esc_textarea($value('order_comments')); ?></textarea></section><?php };
$coupon = function () { if (!wc_coupons_enabled()) return; ?><section class="nem-card" data-npe-section="coupon"><h2 class="nem-card-title">Mã khuyến mãi</h2><div class="nem-coupon-input-group"><label class="screen-reader-text" for="npe-coupon">Mã khuyến mãi</label><input id="npe-coupon" type="text" class="nem-coupon-input" placeholder="Nhập mã khuyến mãi"><button type="button" class="nem-coupon-btn">Áp dụng</button></div><p class="nem-coupon-notice" role="status" hidden></p></section><?php };
$summary = function () use ($cart) { ?>
    <section class="nem-card" id="order_review" data-npe-section="summary"><h2 class="nem-card-title"><?php echo esc_html(npe_checkout_text('summary_title', 'Tóm tắt đơn hàng')); ?></h2>
    <div class="nem-cart-items">
    <?php foreach ($cart->get_cart() as $key => $line):
        $product = apply_filters('woocommerce_cart_item_product', $line['data'], $line, $key);
        if (!$product || !$product->exists() || $line['quantity'] <= 0) continue;
    ?>
        <div class="nem-cart-item" data-key="<?php echo esc_attr($key); ?>"><div class="nem-cart-thumb"><?php echo $product->get_image('thumbnail'); ?></div><div class="nem-cart-details"><div class="nem-cart-top-row"><h3 class="nem-cart-item-title"><?php echo esc_html($product->get_name()); ?></h3><button type="button" class="nem-cart-delete-btn" aria-label="<?php echo esc_attr('Xóa '.$product->get_name()); ?>">×</button></div><div class="nem-cart-variant"><?php echo wc_get_formatted_cart_item_data($line); ?></div><div class="nem-cart-bottom-row"><span class="nem-cart-price"><?php echo wp_kses_post($cart->get_product_subtotal($product, $line['quantity'])); ?></span><div class="nem-stepper"><button type="button" class="nem-stepper-btn nem-stepper-minus" aria-label="Giảm số lượng">−</button><input type="number" class="nem-stepper-input" aria-label="Số lượng" value="<?php echo (int)$line['quantity']; ?>" min="1" readonly><button type="button" class="nem-stepper-btn nem-stepper-plus" aria-label="Tăng số lượng">+</button></div></div></div></div>
    <?php endforeach; ?>
    </div>
    <?php echo npe_checkout_totals(); ?>
    <div class="nem-checkout-legal"><?php wc_checkout_privacy_policy_text(); ?>
    <?php if (wc_get_page_id('terms') > 0): ?><p><label><input type="checkbox" id="terms" name="terms" required> Tôi đã đọc và đồng ý với <a href="<?php echo esc_url(get_permalink(wc_get_page_id('terms'))); ?>" target="_blank" rel="noopener">điều khoản mua hàng</a>.</label><input type="hidden" name="terms-field" value="1"></p><?php endif; ?></div>
    <button type="submit" class="nem-checkout-submit-btn button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php echo esc_attr(npe_checkout_text('place_order_text', 'Đặt hàng')); ?>" data-value="<?php echo esc_attr(npe_checkout_text('place_order_text', 'Đặt hàng')); ?>"><?php echo esc_html(npe_checkout_text('place_order_text', 'Đặt hàng')); ?></button>
    </section>
<?php };
$promo = function () { if (function_exists('npe_render_promo')) npe_render_promo('checkout'); };
$sections = compact('contact','address','shipping','payment','notes','coupon','summary','promo');
$order = function_exists('npe_section_order') ? npe_section_order('checkout') : array_keys($sections);
// The purchase flow must keep its required data and order button available.
foreach (['contact','address','payment','summary'] as $required) if (!in_array($required, $order, true)) $order[] = $required;
?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('nem-checkout-body'); ?>><?php wp_body_open(); ?>
<div class="nem-checkout-container">
    <header class="nem-checkout-header"><a href="<?php echo esc_url(home_url('/')); ?>" class="nem-checkout-logo">NEM FASHION</a><a class="nem-checkout-back" href="<?php echo esc_url(wc_get_cart_url()); ?>">← Giỏ hàng</a></header>
    <h1 class="nem-checkout-heading"><?php echo esc_html(npe_checkout_text('heading', 'Thanh toán')); ?></h1>
    <?php if (!is_user_logged_in()): ?><div class="nem-checkout-auth-banner"><span>Đăng nhập để mua hàng tiện lợi và nhận nhiều ưu đãi hơn</span><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="nem-checkout-auth-btn">Đăng nhập</a></div><?php endif; ?>
    <?php woocommerce_output_all_notices(); ?>
    <div class="nem-checkout-alert" role="alert" hidden></div>
    <?php if ($cart->is_empty()): ?><section class="nem-card"><p>Giỏ hàng của bạn đang trống.</p><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Tiếp tục mua sắm →</a></section>
    <?php elseif ($checkout->is_registration_required() && !is_user_logged_in()): ?><section class="nem-card"><p>Vui lòng đăng nhập để tiếp tục đặt hàng.</p></section>
    <?php else: ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout nem-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('nem_checkout_nonce')); ?>">
        <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?><input type="hidden" name="npe_checkout" value="1">
        <div class="nem-checkout-grid">
        <?php foreach (['full','main','sidebar'] as $column): ?>
            <div class="nem-checkout-<?php echo esc_attr($column); ?>">
            <?php foreach ($order as $section):
                $position = function_exists('npe_section_column') ? npe_section_column('checkout', $section) : (in_array($section, ['summary','coupon'], true) ? 'sidebar' : 'main');
                if ($position === $column && isset($sections[$section])) $sections[$section]();
            endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </form>
    <?php endif; ?>
    <footer class="nem-checkout-footer"><span>© <?php echo esc_html(wp_date('Y')); ?> NEM FASHION</span><span>Thanh toán qua kết nối bảo mật của đối tác</span></footer>
</div>
<?php wp_footer(); ?></body></html>
