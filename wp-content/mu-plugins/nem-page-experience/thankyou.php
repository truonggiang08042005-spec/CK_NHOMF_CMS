<?php
defined('ABSPATH') || exit;
global $wp;
$order_id = absint($wp->query_vars['order-received'] ?? 0);
$order = $order_id ? wc_get_order($order_id) : false;
$key = isset($_GET['key']) && is_string($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
$valid = $order && $key !== '' && hash_equals($order->get_order_key(), $key);
if (!$valid) { status_header(404); $order = false; }
$title = 'Đơn hàng đã được ghi nhận';
$message = 'Cảm ơn bạn đã mua sắm tại NEM FASHION.';
if ($order) {
    $method = $order->get_payment_method();
    $demo = $order->get_meta('_npe_payment_demo');
    $momo_attempt = $order->get_meta('_npe_momo_attempt');
    $sandbox = is_array($momo_attempt) && ($momo_attempt['mode'] ?? '') === 'sandbox';
    if ($demo) {
        $title = 'Đơn hàng thử nghiệm';
        $result = $order->get_meta('_npe_demo_result');
        $message = 'Đây là mô phỏng thanh toán, không phát sinh giao dịch hoặc trừ tiền thật.';
        if ($result) $message .= ' Kết quả mô phỏng: ' . (['success'=>'thành công', 'failed'=>'thất bại', 'failure'=>'thất bại', 'cancel'=>'đã hủy', 'cancelled'=>'đã hủy'][$result] ?? $result) . '.';
    } elseif ($sandbox) {
        $title = 'Đơn hàng thử nghiệm MoMo Sandbox';
        $message = $order->get_meta('_npe_momo_sandbox_transaction') ? 'Đã xác thực kết quả thử nghiệm từ MoMo. Không nhận tiền thật; đơn chờ kiểm tra và không được dùng để giao hàng.' : 'Đang chờ xác nhận thử nghiệm từ MoMo. Đây là môi trường sandbox, không dùng tiền thật.';
    } elseif ($order->has_status(['failed','cancelled'])) {
        $title = 'Thanh toán chưa hoàn tất';
        $message = 'Đơn hàng chưa được thanh toán. Bạn có thể thử lại hoặc liên hệ cửa hàng.';
    } elseif ($method === 'cod') {
        $message = 'Vui lòng thanh toán cho nhân viên giao hàng khi nhận sản phẩm.';
    } elseif ($order->get_date_paid()) {
        $title = 'Thanh toán đã được xác nhận';
        $message = 'Cửa hàng đã ghi nhận thanh toán của bạn và sẽ xử lý đơn hàng.';
    } elseif ($method === 'bacs') {
        $message = 'Vui lòng chuyển khoản theo thông tin bên dưới. Đơn hàng chờ cửa hàng xác nhận đã nhận tiền.';
    } else {
        $title = 'Đang chờ xác nhận thanh toán';
        $message = 'Cửa hàng sẽ cập nhật đơn hàng sau khi nhận xác nhận từ cổng thanh toán. Vui lòng không thanh toán thêm khi giao dịch đang được xử lý.';
        $attempt = $order->get_meta('_npe_momo_attempt');
        if (is_array($attempt) && ($attempt['mode'] ?? '') === 'sandbox') $message .= ' Đây là giao dịch trong môi trường sandbox.';
    }
} else { $title = 'Không tìm thấy thông tin đơn hàng'; $message = 'Liên kết đơn hàng không hợp lệ hoặc thiếu khóa xác nhận.'; }
?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?><link rel="stylesheet" href="<?php echo esc_url(content_url('/mu-plugins/nem-page-experience/checkout.css?v='.filemtime(__DIR__.'/checkout.css'))); ?>"></head>
<body <?php body_class('nem-checkout-body'); ?>><?php wp_body_open(); ?><div class="nem-checkout-container">
<header class="nem-checkout-header"><a class="nem-checkout-logo" href="<?php echo esc_url(home_url('/')); ?>">NEM FASHION</a></header>
<main class="nem-card nem-thankyou-box"><h1><?php echo esc_html($title); ?></h1><p><?php echo esc_html($message); ?></p>
<?php if ($order): ?>
    <table class="nem-order-details-table"><tbody>
        <tr><th>Mã đơn hàng</th><td>#<?php echo esc_html($order->get_order_number()); ?></td></tr>
        <tr><th>Ngày đặt</th><td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td></tr>
        <tr><th>Phương thức</th><td><?php echo esc_html($order->get_payment_method_title()); ?></td></tr>
        <tr><th>Trạng thái</th><td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td></tr>
        <tr><th>Tổng thanh toán</th><td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td></tr>
    </tbody></table>
    <?php do_action('woocommerce_thankyou_'.$order->get_payment_method(), $order->get_id()); ?>
    <?php if ($order->needs_payment() && !$order->get_meta('_npe_payment_demo')): ?><p><a class="button" href="<?php echo esc_url($order->get_checkout_payment_url()); ?>">Thử thanh toán lại</a></p><?php endif; ?>
    <?php do_action('woocommerce_thankyou', $order->get_id()); ?>
<?php endif; ?>
<a class="nem-checkout-auth-btn" href="<?php echo esc_url(home_url('/')); ?>">Tiếp tục mua sắm →</a>
</main></div><?php wp_footer(); ?></body></html>
