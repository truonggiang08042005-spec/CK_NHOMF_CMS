<?php
/** Explicit local simulator. It never reports money received or calls payment_complete(). */
defined('ABSPATH') || exit;

function npe_payments_is_local_site() {
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
        || str_ends_with($host, '.local') || str_ends_with($host, '.test');
}

function npe_payments_demo_signature($order, array $attempt) {
    return hash_hmac('sha256', implode('|', [$order->get_id(), $order->get_order_key(), $attempt['gateway'], $attempt['attempt'], $attempt['created']]), wp_salt('nonce'));
}

function npe_payments_start_demo($order, $gateway_id) {
    $attempt = ['gateway' => $gateway_id, 'attempt' => wp_generate_uuid4(), 'created' => time()];
    $order->update_meta_data('_npe_payment_demo', $attempt);
    $order->update_meta_data('_npe_demo_result', 'pending');
    $order->add_order_note('MÔ PHỎNG LOCAL ' . strtoupper($gateway_id) . ': không trừ tiền; chưa nhận thanh toán thật.');
    $order->save();
    $url = add_query_arg(['order_id' => $order->get_id(), 'key' => $order->get_order_key(), 'signature' => npe_payments_demo_signature($order, $attempt)], WC()->api_request_url('npe_payment_demo'));
    if (WC()->cart) WC()->cart->empty_cart();
    return ['result' => 'success', 'redirect' => $url];
}

function npe_payments_valid_demo_request($order, array $request) {
    if (!$order || !npe_payments_is_local_site() || $order->is_paid()) return false;
    $attempt = $order->get_meta('_npe_payment_demo', true);
    if (!is_array($attempt) || !in_array($attempt['gateway'] ?? '', ['momo', 'momo_atm', 'momo_card', 'payoo'], true) || empty($attempt['attempt']) || empty($attempt['created']) || $attempt['created'] < time() - 3600
        || $order->get_payment_method() !== $attempt['gateway'] || !isset($request['key'], $request['signature']) || !is_string($request['key']) || !is_string($request['signature'])) return false;
    return hash_equals((string) $order->get_order_key(), $request['key']) && hash_equals(npe_payments_demo_signature($order, $attempt), $request['signature']);
}

function npe_payments_apply_demo_result($order, $result) {
    $attempt = $order ? $order->get_meta('_npe_payment_demo', true) : null;
    if (!npe_payments_is_local_site() || !is_array($attempt) || $order->is_paid() || !in_array($result, ['success', 'failure', 'cancel'], true)) return false;
    $current = $order->get_meta('_npe_demo_result', true);
    if ($current === $result) return true;
    if ($current !== 'pending') return false;
    $order->update_meta_data('_npe_demo_result', $result);
    $order->save();
    $status = ['success' => 'on-hold', 'failure' => 'failed', 'cancel' => 'cancelled'][$result];
    $label = ['success' => 'thành công', 'failure' => 'thất bại', 'cancel' => 'đã hủy'][$result];
    $order->update_status($status, 'KẾT QUẢ MÔ PHỎNG ' . strtoupper($attempt['gateway']) . ': ' . $label . '. KHÔNG TRỪ TIỀN; KHÔNG XÁC NHẬN ĐÃ NHẬN TIỀN.');
    return true;
}

// A successful simulation is on-hold for review, without reducing real stock.
add_filter('woocommerce_can_reduce_order_stock', function ($allowed, $order) {
    if (!$order) return $allowed;
    $attempt = $order->get_meta('_npe_momo_attempt', true);
    return $order->get_meta('_npe_payment_demo', true) || (is_array($attempt) && ($attempt['mode'] ?? '') === 'sandbox') ? false : $allowed;
}, 100, 2);

foreach (['new_order','failed_order','cancelled_order','customer_on_hold_order','customer_processing_order','customer_completed_order'] as $email_id) {
    add_filter('woocommerce_email_enabled_'.$email_id, function ($enabled, $order) {
        if (!$order || !is_a($order, 'WC_Order')) return $enabled;
        $attempt = $order->get_meta('_npe_momo_attempt', true);
        return $order->get_meta('_npe_payment_demo', true) || (is_array($attempt) && ($attempt['mode'] ?? '') === 'sandbox') ? false : $enabled;
    }, 100, 2);
}

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $attempt = $order->get_meta('_npe_momo_attempt', true);
    if ($order->get_meta('_npe_payment_demo', true) || (is_array($attempt) && ($attempt['mode'] ?? '') === 'sandbox')) {
        echo '<p style="padding:12px;border:1px solid #dba617;background:#fff8e5"><strong>ĐƠN THỬ NGHIỆM — KHÔNG NHẬN TIỀN THẬT.</strong><br>Không dùng kết quả mô phỏng hoặc sandbox để giao hàng.</p>';
    }
});

add_action('woocommerce_api_npe_payment_demo', function () {
    $request = wp_unslash($_GET);
    $id = isset($request['order_id']) && is_scalar($request['order_id']) ? absint($request['order_id']) : 0;
    $order = $id ? wc_get_order($id) : false;
    if (!is_array($request) || !npe_payments_valid_demo_request($order, $request)) wp_die('Liên kết mô phỏng không hợp lệ hoặc đã hết hạn.', 'Mô phỏng thanh toán', ['response' => 403]);
    $attempt = $order->get_meta('_npe_payment_demo', true);
    $gateways = WC()->payment_gateways()->payment_gateways();
    $gateway = $gateways[$attempt['gateway']] ?? null;
    if (!$gateway || !method_exists($gateway, 'is_demo') || !$gateway->is_demo() || $gateway->enabled !== 'yes') wp_die('Mô phỏng local đã tắt.', 'Mô phỏng thanh toán', ['response' => 403]);
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $nonce = isset($_POST['demo_nonce']) && is_string($_POST['demo_nonce']) ? wp_unslash($_POST['demo_nonce']) : '';
        if (!wp_verify_nonce($nonce, 'npe_demo_' . $attempt['attempt'])) wp_die('Phiên mô phỏng đã hết hạn.', '', ['response' => 403]);
        $result = isset($_POST['demo_result']) && is_string($_POST['demo_result']) ? wp_unslash($_POST['demo_result']) : '';
        $lock = npe_payments_lock('demo-' . $id);
        if (!$lock) wp_die('Đang xử lý, vui lòng thử lại.', '', ['response' => 409]);
        try {
            $order = wc_get_order($id);
            if (!npe_payments_apply_demo_result($order, $result)) wp_die('Kết quả mô phỏng không hợp lệ.', '', ['response' => 409]);
        } finally {
            npe_payments_unlock($lock);
        }
        wp_safe_redirect($gateway->get_return_url($order));
        exit;
    }
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow', true);
    if (in_array($attempt['gateway'], ['momo', 'momo_atm', 'momo_card'], true)) {
        // Realistic gateway screens: MoMo QR, ATM/NAPAS card + OTP, Visa/Master/JCB + 3-D Secure.
        include __DIR__ . '/payment-sim-page.php';
        exit;
    }
    $name = ['momo'=>'Ví MoMo','momo_atm'=>'Thẻ ATM nội địa','momo_card'=>'Thẻ quốc tế','payoo'=>'Payoo'][$attempt['gateway']] ?? 'Thanh toán';
    ?>
    <!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Mô phỏng <?php echo esc_html($name); ?> — NEM</title>
    <style>body{margin:0;background:#f5f5f6;font:15px/1.6 Arial,sans-serif;color:#1d1d1f}.demo{max-width:560px;margin:8vh auto;padding:32px;background:#fff;border:1px solid #e1e1e3;border-radius:12px;box-shadow:0 12px 40px #00000008}.logo{font-size:32px;letter-spacing:6px;margin:0}.badge{display:inline-block;padding:6px 12px;background:#fff1c9;border-radius:5px;font-weight:bold;margin:12px 0}h1{font-size:24px;margin:10px 0}dl{display:grid;grid-template-columns:1fr 1fr;border-block:1px solid #eee;padding:18px 0}dd{text-align:right;margin:0}form{display:grid;gap:10px}button,a.back{padding:13px;border:1px solid #ccc;border-radius:5px;font:inherit;cursor:pointer;background:white;text-align:center;color:#111}button:first-of-type{background:#111;color:white;border-color:#111}.back{display:block;margin-top:15px;text-decoration:none}.hint{color:#666;font-size:13px}@media(max-width:640px){.demo{margin:24px 16px;padding:24px}}</style></head><body><main class="demo"><p class="logo">NEM</p><span class="badge">MÔ PHỎNG LOCAL — KHÔNG TRỪ TIỀN</span><h1>Thử thanh toán <?php echo esc_html($name); ?></h1><p>Chọn kết quả để kiểm tra luồng đặt hàng. Trang này không kết nối cổng <?php echo esc_html($name); ?> và không ghi nhận tiền thật.</p><dl><dt>Đơn hàng</dt><dd>#<?php echo esc_html($order->get_order_number()); ?></dd><dt>Tổng tiền mô phỏng</dt><dd><?php echo wp_kses_post($order->get_formatted_order_total()); ?></dd></dl><form method="post"><?php wp_nonce_field('npe_demo_' . $attempt['attempt'], 'demo_nonce'); ?><button type="submit" name="demo_result" value="success">Mô phỏng thành công</button><button type="submit" name="demo_result" value="failure">Mô phỏng thất bại</button><button type="submit" name="demo_result" value="cancel">Mô phỏng hủy thanh toán</button></form><p class="hint">Kết quả thành công mô phỏng sẽ để đơn ở trạng thái chờ kiểm tra, không chuyển sang đã nhận tiền hoặc hoàn tất.</p><a class="back" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Quay về cửa hàng</a></main></body></html>
    <?php
    exit;
});
