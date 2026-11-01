<?php
defined('ABSPATH') || exit;

function npe_checkout_text($key, $default = '') {
    return function_exists('npe_setting') ? npe_setting('checkout', $key, $default) : $default;
}

function npe_checkout_has_bank_account() {
    foreach ((array) get_option('woocommerce_bacs_accounts', []) as $account) {
        if (is_array($account) && !empty($account['account_name']) && !empty($account['account_number']) && !empty($account['bank_name'])) return true;
    }
    return false;
}

add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (isset($gateways['bacs']) && !npe_checkout_has_bank_account()) unset($gateways['bacs']);
    return $gateways;
}, 100);

function npe_payment_icon($method) {
    if ($method === 'momo') return '<svg viewBox="0 0 28 28" aria-hidden="true"><rect width="28" height="28" rx="6" fill="#A50064"/><g text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="13" font-weight="700"><text x="14" y="12">mo</text><text x="14" y="23">mo</text></g></svg>';
    if ($method === 'payoo') return '<svg class="nem-payoo-logo" viewBox="0 0 28 28" aria-hidden="true"><text x="14" y="17" text-anchor="middle" fill="#268ac2" font-family="Arial,sans-serif" font-size="7" font-style="italic">Payoo</text></svg>';
    if ($method === 'bacs') return '<svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>';
    if (in_array($method,['momo_atm','momo_card'],true)) return '<svg viewBox="0 0 28 28" fill="none" stroke="#2563eb" stroke-width="1.6" aria-hidden="true"><rect x="2" y="5" width="24" height="18" rx="3"/><path d="M2 11h24M6 18h5M16 18h5"/></svg>';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 9 5v10l-9 5-9-5V7l9-5Zm-9 5 9 5 9-5M12 12v10"/></svg>';
}

function npe_checkout_payment_labels() {
    return ['momo'=>'Ví MoMo','momo_atm'=>'Thẻ ATM nội địa / NAPAS qua MoMo','momo_card'=>'Thẻ quốc tế qua MoMo','bacs'=>'Chuyển khoản qua ngân hàng','cod'=>'Thanh toán khi nhận hàng (COD)'];
}

function npe_payment_badges($id) {
    $badges = [
        'bacs' => ['b-qr' => 'VietQR', 'b-napas' => 'Napas 247'],
        'momo' => ['b-qr' => 'Quét QR', '' => 'Ứng dụng MoMo'],
        'momo_atm' => ['b-napas' => 'NAPAS', '' => 'Vietcombank · BIDV · Techcombank · MB · 40+ ngân hàng'],
        'momo_card' => ['b-visa' => 'VISA', 'b-master' => 'Mastercard', 'b-jcb' => 'JCB', 'b-amex' => 'AMEX'],
        'payoo' => ['b-napas' => 'ATM', 'b-visa' => 'VISA', 'b-master' => 'Mastercard', 'b-jcb' => 'JCB', 'b-qr' => 'QR Pay'],
    ][$id] ?? [];
    if (!$badges) return '';
    $html = '<span class="nem-payment-badges" aria-label="'.esc_attr(implode(', ', $badges)).'">';
    foreach ($badges as $class => $label) $html .= '<i class="'.esc_attr($class).'">'.esc_html($label).'</i>';
    return $html.'</span>';
}

function npe_checkout_payments() {
    $available = WC()->payment_gateways()->get_available_payment_gateways();
    $all = WC()->payment_gateways()->payment_gateways();
    $titles = npe_checkout_payment_labels();
    $chosen = WC()->session ? WC()->session->get('chosen_payment_method') : '';
    if (!isset($available[$chosen])) $chosen = isset($available['bacs']) ? 'bacs' : (string) array_key_first($available);
    ob_start(); ?>
    <div id="payment" class="woocommerce-checkout-payment">
    <?php if (!WC()->cart->needs_payment()): ?><p>Đơn hàng này không cần thanh toán.</p><?php else: ?>
    <ul class="nem-payment-options payment_methods methods">
    <?php foreach (array_unique(array_merge(array_keys($titles), array_keys($available))) as $id):
        $gateway = $all[$id] ?? null;
        $enabled = isset($available[$id]);
        $selected = $enabled && $chosen === $id;
        $title = $gateway ? $gateway->get_title() : ($titles[$id] ?? $id);
        // Keep the reference labels for WooCommerce's generic English defaults.
        if (in_array($id, ['bacs','cod'], true) && $gateway && $title === $gateway->method_title) $title = $titles[$id];
    ?>
        <li class="wc_payment_method payment_method_<?php echo esc_attr($id); ?><?php echo $enabled ? '' : ' is-unavailable'; ?>">
            <label class="nem-payment-item<?php echo $selected ? ' is-selected' : ''; ?>" for="payment_method_<?php echo esc_attr($id); ?>">
                <input id="payment_method_<?php echo esc_attr($id); ?>" type="radio" class="nem-payment-radio input-radio" name="<?php echo $enabled ? 'payment_method' : 'npe_unavailable_payment'; ?>" value="<?php echo esc_attr($id); ?>" <?php checked($selected); ?> <?php disabled(!$enabled); ?> data-order_button_text="<?php echo esc_attr(npe_checkout_text('place_order_text', 'Đặt hàng')); ?>">
                <span class="nem-payment-icon"><?php echo npe_payment_icon($id); ?></span>
                <span class="nem-payment-text"><?php echo esc_html($title); ?>
                <?php echo npe_payment_badges($id); ?>
                <?php if (!$enabled): ?><small class="nem-payment-status"><?php echo $id === 'bacs' && !npe_checkout_has_bank_account() ? 'Chưa có thông tin tài khoản nhận tiền' : (in_array($id, ['momo','momo_atm','momo_card','payoo'], true) ? 'Chưa kết nối tài khoản thanh toán' : 'Tạm chưa khả dụng'); ?></small><?php elseif ($gateway && method_exists($gateway, 'is_demo') && $gateway->is_demo()): ?><small class="nem-payment-status">Mô phỏng — không trừ tiền</small><?php elseif ($gateway && $gateway->get_option('testmode') === 'yes'): ?><small class="nem-payment-status">Môi trường thử nghiệm</small><?php endif; ?>
                </span>
            </label>
            <?php if ($enabled && $gateway && ($gateway->has_fields() || $gateway->get_description())): ?>
                <div class="payment_box payment_method_<?php echo esc_attr($id); ?>"<?php if (!$selected) echo ' style="display:none"'; ?>><?php $gateway->payment_fields(); ?></div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if (!$available): ?><p class="woocommerce-info">Cửa hàng chưa kích hoạt phương thức thanh toán. Vui lòng liên hệ cửa hàng.</p><?php endif; ?>
    <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}

function npe_checkout_shipping() {
    ob_start(); ?>
    <div id="npe-shipping-methods">
    <?php if (!WC()->cart->needs_shipping()): ?><p>Đơn hàng không yêu cầu giao hàng.</p><?php else:
        $packages = WC()->shipping()->get_packages();
        $chosen = (array) WC()->session->get('chosen_shipping_methods', []);
        $has_rates = false;
        foreach ($packages as $index => $package):
            if (empty($package['rates'])) continue;
            $has_rates = true;
            foreach ($package['rates'] as $rate_id => $rate): ?>
                <label class="nem-shipping-rate"><input type="radio" class="shipping_method" name="shipping_method[<?php echo (int)$index; ?>]" id="shipping_method_<?php echo (int)$index; ?>_<?php echo esc_attr(sanitize_title($rate_id)); ?>" data-index="<?php echo (int)$index; ?>" value="<?php echo esc_attr($rate_id); ?>" <?php checked(($chosen[$index] ?? '') === $rate_id); ?>> <span><?php echo wp_kses_post(wc_cart_totals_shipping_method_label($rate)); ?></span></label>
            <?php endforeach;
        endforeach;
        if (!$has_rates): ?><p class="nem-shipping-placeholder">Nhập địa chỉ để xem các phương thức giao hàng. Nếu chưa có phương thức phù hợp, vui lòng liên hệ cửa hàng.</p><?php endif;
    endif; ?>
    </div>
    <?php return ob_get_clean();
}

function npe_checkout_totals() {
    $cart = WC()->cart;
    ob_start(); ?>
    <div id="npe-order-totals" class="nem-summary-rows">
        <div class="nem-summary-row"><span>Tổng tiền hàng</span><span class="nem-summary-subtotal"><?php echo wp_kses_post($cart->get_cart_subtotal()); ?></span></div>
        <?php if ($cart->needs_shipping()): ?><div class="nem-summary-row"><span>Phí vận chuyển</span><span><?php echo wp_kses_post($cart->get_cart_shipping_total()); ?></span></div><?php endif; ?>
        <?php if ($cart->get_discount_total() > 0): ?><div class="nem-summary-row nem-summary-discount-row"><span>Giảm giá</span><span class="nem-summary-discount">−<?php echo wp_kses_post(wc_price($cart->get_discount_total() + $cart->get_discount_tax())); ?></span></div><?php endif; ?>
        <?php foreach ($cart->get_fees() as $fee): ?><div class="nem-summary-row"><span><?php echo esc_html($fee->name); ?></span><span><?php echo wp_kses_post(wc_price($fee->total + $fee->tax)); ?></span></div><?php endforeach; ?>
        <?php if (wc_tax_enabled() && !$cart->display_prices_including_tax()): foreach ($cart->get_tax_totals() as $tax): ?><div class="nem-summary-row"><span><?php echo esc_html($tax->label); ?></span><span><?php echo wp_kses_post($tax->formatted_amount); ?></span></div><?php endforeach; endif; ?>
        <div class="nem-summary-row is-total"><span>Tổng thanh toán</span><strong class="nem-summary-total nem-summary-value"><?php echo wp_kses_post($cart->get_total()); ?></strong></div>
    </div>
    <?php return ob_get_clean();
}

add_filter('woocommerce_update_order_review_fragments', function ($fragments) {
    $posted = [];
    parse_str(isset($_POST['post_data']) && is_string($_POST['post_data']) ? wp_unslash($_POST['post_data']) : '', $posted);
    if (($posted['npe_checkout'] ?? '') !== '1') return $fragments;
    unset($fragments['.woocommerce-checkout-review-order-table']);
    $fragments['.woocommerce-checkout-payment'] = npe_checkout_payments();
    $fragments['#npe-order-totals'] = npe_checkout_totals();
    $fragments['#npe-shipping-methods'] = npe_checkout_shipping();
    return $fragments;
});

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('npe_is_checkout') || !npe_is_checkout()) return;
    wp_enqueue_style('npe-checkout', content_url('/mu-plugins/nem-page-experience/checkout.css'), [], filemtime(__DIR__.'/checkout.css'));
    wp_enqueue_script('npe-checkout', content_url('/mu-plugins/nem-page-experience/checkout.js'), ['jquery','wc-checkout'], filemtime(__DIR__.'/checkout.js'), true);
}, 1000);
