<?php
defined('ABSPATH') || exit;

global $wp;
$order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
$order = $order_id ? wc_get_order($order_id) : null;

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt hàng thành công — NEM FASHION</title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(content_url('/mu-plugins/nem-page-experience/checkout.css?v=' . filemtime(__DIR__ . '/checkout.css'))); ?>">
    <style>
        .nem-thankyou-box {
            background: #fff;
            border-radius: 12px;
            padding: 40px 30px;
            max-width: 640px;
            margin: 40px auto;
            text-align: center;
            border: 1px solid #e8e8ed;
        }
        .nem-thankyou-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #2e7d32;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .nem-thankyou-title {
            font-size: 22px;
            font-weight: 700;
            color: #111;
            margin-bottom: 10px;
        }
        .nem-thankyou-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .nem-order-details-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin: 20px 0;
            font-size: 13.5px;
        }
        .nem-order-details-table th, .nem-order-details-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f4;
        }
        .nem-home-btn {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
        }
    </style>
</head>
<body class="nem-checkout-body">
    <div class="nem-checkout-container">
        <header class="nem-checkout-header" style="text-align:center;">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nem-checkout-logo">NEM FASHION</a>
        </header>

        <div class="nem-thankyou-box">
            <div class="nem-thankyou-icon">✓</div>
            <h1 class="nem-thankyou-title">Đặt hàng thành công!</h1>
            <p class="nem-thankyou-desc">
                Cảm ơn bạn đã mua sắm tại <strong>NEM FASHION</strong>.<br>
                Đơn hàng của bạn đã được ghi nhận và đang được bộ phận chăm sóc khách hàng xử lý.
            </p>

            <?php if ($order) : ?>
            <table class="nem-order-details-table">
                <tr>
                    <th>Mã đơn hàng:</th>
                    <td><strong>#<?php echo esc_html($order->get_order_number()); ?></strong></td>
                </tr>
                <tr>
                    <th>Ngày đặt:</th>
                    <td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                </tr>
                <tr>
                    <th>Phương thức thanh toán:</th>
                    <td><?php echo esc_html($order->get_payment_method_title()); ?></td>
                </tr>
                <tr>
                    <th>Tổng thanh toán:</th>
                    <td><strong style="color:#111;"><?php echo $order->get_formatted_order_total(); ?></strong></td>
                </tr>
            </table>
            <?php endif; ?>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="nem-home-btn">Tiếp tục mua sắm</a>
        </div>

        <footer class="nem-checkout-footer">
            <span>Powered by</span> <span class="nem-haravan-brand"><svg class="nem-haravan-mark" width="24" height="24" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><g fill="currentColor"><path d="M7 8 23 6l2 18-17 4L7 8Zm-4 3 3-1 1 17-3-2L3 11Z"/><path d="m24 9 4 2-1 10-2 2-1-14Z"/></g><path d="M11 9V6a5 5 0 0 1 10 0v2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M12 12v10m7-11v10m-7-5 7-1" fill="none" stroke="#f5f5f7" stroke-width="2.2"/><path d="M2 24c-4 9 16 7 29-11M3 27c5 3 19-3 26-10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span>haravan</span></span>
        </footer>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
