<?php
/**
 * Local payment-gateway simulator page (MoMo wallet / ATM NAPAS / international card).
 * Card data typed here is validated in the browser only: inputs have no "name"
 * attribute, so nothing except the simulated result is ever sent to the server.
 *
 * @var WC_Order $order
 * @var array    $attempt
 */
defined('ABSPATH') || exit;
$npe_gw = $attempt['gateway'];
$npe_brand = ['momo' => 'Ví MoMo', 'momo_atm' => 'Thẻ ATM nội địa (NAPAS)', 'momo_card' => 'Thẻ quốc tế Visa / Mastercard / JCB', 'payoo' => 'Payoo'][$npe_gw] ?? 'Thanh toán';
$npe_dir = content_url('/mu-plugins/nem-page-experience/');
$npe_ver = function ($file) { return (string) filemtime(__DIR__ . '/' . $file); };
$npe_amount = (int) npe_payments_amount($order->get_total());
$npe_expires = (int) $attempt['created'] + 900;
$npe_banks = array_slice(npe_vn_banks(), 0, 20, true);
?><!doctype html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html($npe_brand); ?> — Cổng thanh toán NEM</title>
<link rel="stylesheet" href="<?php echo esc_url($npe_dir . 'payment-sim.css?v=' . $npe_ver('payment-sim.css')); ?>">
</head>
<body class="sim sim-<?php echo esc_attr($npe_gw); ?>">
<header class="sim-top"><div class="sim-top-in">
    <span class="sim-logo"><?php echo $npe_gw === 'momo' ? '<b class="sim-momo-mark">mo<br>mo</b> Cổng thanh toán MoMo' : '<b class="sim-napas-mark">' . ($npe_gw === 'momo_card' ? 'VISA' : 'NAPAS') . '</b> Cổng thanh toán an toàn'; ?></span>
    <span class="sim-lock" aria-hidden="true">🔒 Kết nối bảo mật</span>
</div></header>
<p class="sim-ribbon">CHẾ ĐỘ MÔ PHỎNG LOCAL — không kết nối ngân hàng, không trừ tiền. Thông tin thẻ chỉ được kiểm tra trên trình duyệt và không gửi lên máy chủ.</p>

<main class="sim-wrap">
    <aside class="sim-order">
        <h2>Thông tin đơn hàng</h2>
        <dl>
            <dt>Nhà cung cấp</dt><dd>NEM FASHION</dd>
            <dt>Mã đơn hàng</dt><dd>#<?php echo esc_html($order->get_order_number()); ?></dd>
            <dt>Mô tả</dt><dd>Thanh toán đơn hàng NEM <?php echo esc_html($order->get_order_number()); ?></dd>
            <dt>Phương thức</dt><dd><?php echo esc_html($npe_brand); ?></dd>
        </dl>
        <p class="sim-amount-label">Số tiền thanh toán</p>
        <p class="sim-amount"><?php echo esc_html(number_format($npe_amount, 0, ',', '.')); ?>đ</p>
        <p class="sim-expire">Giao dịch hết hạn sau <strong data-sim-countdown="<?php echo esc_attr($npe_expires - time()); ?>">15:00</strong></p>
    </aside>

    <section class="sim-panel" aria-live="polite">
    <?php if ($npe_gw === 'momo'): ?>
        <div class="sim-step" data-step="qr">
            <h1>Quét mã để thanh toán</h1>
            <div class="sim-qr-box"><div class="sim-qr" data-qr="<?php echo esc_attr('NEMDEMO|MOMO|' . $order->get_id() . '|' . $npe_amount . '|' . $attempt['attempt']); ?>"></div><span class="sim-qr-logo">mo<br>mo</span></div>
            <p class="sim-hint">Mở ứng dụng <b>MoMo</b> → chọn <b>Quét mã</b> → xác nhận thanh toán.<br><small>Đây là mã QR mô phỏng, không dùng được với ứng dụng thật.</small></p>
            <button type="button" class="sim-btn sim-primary" data-go="momo-confirm">Mô phỏng: đã quét mã trên điện thoại</button>
        </div>
        <div class="sim-step" data-step="momo-confirm" hidden>
            <h1>Xác nhận trên ứng dụng MoMo</h1>
            <div class="sim-phone"><p>Thanh toán cho <b>NEM FASHION</b></p><p class="sim-phone-amount"><?php echo esc_html(number_format($npe_amount, 0, ',', '.')); ?>đ</p><p>Nguồn tiền: Ví MoMo</p></div>
            <button type="button" class="sim-btn sim-primary" data-result="success">Xác nhận thanh toán</button>
            <button type="button" class="sim-btn" data-result="failure">Mô phỏng: số dư không đủ</button>
        </div>
    <?php elseif ($npe_gw === 'momo_atm'): ?>
        <div class="sim-step" data-step="bank">
            <h1>Chọn ngân hàng phát hành thẻ</h1>
            <div class="sim-banks">
            <?php foreach ($npe_banks as $bin => $label): ?>
                <button type="button" class="sim-bank" data-bin="<?php echo esc_attr($bin); ?>" data-bank="<?php echo esc_attr($label); ?>"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
            </div>
        </div>
        <form class="sim-step sim-card-form" data-step="card" data-kind="atm" hidden novalidate>
            <h1>Thông tin thẻ <span data-bank-name></span></h1>
            <div class="sim-card-visual sim-card-atm"><span data-bank-name>NAPAS</span><span class="sim-card-num" data-mirror="number">9704 •••• •••• ••••</span><span class="sim-card-name" data-mirror="name">TEN CHU THE</span><span class="sim-card-exp" data-mirror="date">MM/YY</span></div>
            <label>Số thẻ<input type="text" inputmode="numeric" autocomplete="off" data-field="number" placeholder="9704 0000 0000 0018" maxlength="23"></label>
            <label>Tên chủ thẻ (không dấu)<input type="text" autocomplete="off" data-field="name" placeholder="NGUYEN VAN A"></label>
            <label>Ngày phát hành (MM/YY)<input type="text" inputmode="numeric" autocomplete="off" data-field="date" placeholder="07/15" maxlength="5"></label>
            <p class="sim-error" role="alert" hidden></p>
            <button type="submit" class="sim-btn sim-primary">Tiếp tục</button>
            <button type="button" class="sim-btn sim-link" data-go="bank">← Chọn ngân hàng khác</button>
            <p class="sim-test">Thẻ thử: <code>9704 0000 0000 0018</code> · NGUYEN VAN A · 03/07 (thẻ bắt đầu bằng 9704, 16 hoặc 19 số)</p>
        </form>
    <?php else: ?>
        <form class="sim-step sim-card-form" data-step="card" data-kind="intl" novalidate>
            <h1>Thanh toán bằng thẻ</h1>
            <div class="sim-brands"><span data-brand="visa">VISA</span><span data-brand="mastercard">Mastercard</span><span data-brand="jcb">JCB</span><span data-brand="amex">AMEX</span></div>
            <div class="sim-card-visual sim-card-intl"><span class="sim-card-brand" data-mirror="brand">CARD</span><span class="sim-card-num" data-mirror="number">•••• •••• •••• ••••</span><span class="sim-card-name" data-mirror="name">CARDHOLDER NAME</span><span class="sim-card-exp" data-mirror="date">MM/YY</span></div>
            <label>Số thẻ<input type="text" inputmode="numeric" autocomplete="off" data-field="number" placeholder="4111 1111 1111 1111" maxlength="23"></label>
            <label>Tên in trên thẻ<input type="text" autocomplete="off" data-field="name" placeholder="NGUYEN VAN A"></label>
            <div class="sim-row">
                <label>Ngày hết hạn<input type="text" inputmode="numeric" autocomplete="off" data-field="date" placeholder="MM/YY" maxlength="5"></label>
                <label>CVV/CVC<input type="password" inputmode="numeric" autocomplete="off" data-field="cvv" placeholder="•••" maxlength="4"></label>
            </div>
            <p class="sim-error" role="alert" hidden></p>
            <button type="submit" class="sim-btn sim-primary">Thanh toán <?php echo esc_html(number_format($npe_amount, 0, ',', '.')); ?>đ</button>
            <p class="sim-test">Thẻ thử thành công: <code>4111 1111 1111 1111</code> / <code>5555 5555 5555 4444</code> / <code>3530 1113 3330 0000</code><br>Thẻ bị từ chối: <code>4000 0000 0000 0002</code> · Hạn bất kỳ trong tương lai · CVV bất kỳ</p>
        </form>
    <?php endif; ?>

    <?php if ($npe_gw !== 'momo'): ?>
        <form class="sim-step sim-otp-form" data-step="otp" hidden novalidate>
            <h1><?php echo $npe_gw === 'momo_card' ? 'Xác thực 3-D Secure' : 'Xác thực OTP'; ?></h1>
            <p>Mã OTP đã được gửi tới số điện thoại đăng ký với ngân hàng <b><?php echo esc_html(substr_replace(preg_replace('/\D/', '', $order->get_billing_phone()) ?: '0900000000', '****', 3, 4)); ?></b>.</p>
            <label>Nhập mã OTP<input type="text" inputmode="numeric" autocomplete="one-time-code" data-field="otp" maxlength="6" placeholder="••••••"></label>
            <p class="sim-error" role="alert" hidden></p>
            <button type="submit" class="sim-btn sim-primary">Xác nhận</button>
            <p class="sim-test">OTP thử nghiệm: <code>123456</code> (nhập sai 3 lần sẽ thất bại)</p>
        </form>
    <?php endif; ?>

        <div class="sim-step sim-processing" data-step="processing" hidden><span class="sim-spinner" aria-hidden="true"></span><h1>Đang xử lý giao dịch…</h1><p>Vui lòng không đóng trình duyệt.</p></div>

        <form method="post" class="sim-result-form">
            <?php wp_nonce_field('npe_demo_' . $attempt['attempt'], 'demo_nonce'); ?>
            <input type="hidden" name="demo_result" value="">
            <button type="button" class="sim-btn sim-cancel" data-result="cancel">Hủy giao dịch và quay lại cửa hàng</button>
        </form>
    </section>
</main>
<footer class="sim-foot">© <?php echo esc_html(wp_date('Y')); ?> NEM FASHION · Trang mô phỏng cổng thanh toán dành cho môi trường phát triển</footer>
<script src="<?php echo esc_url(includes_url('js/jquery/jquery.min.js')); ?>"></script>
<script src="<?php echo esc_url(plugins_url('woocommerce/assets/js/jquery-qrcode/jquery.qrcode.min.js')); ?>"></script>
<script src="<?php echo esc_url($npe_dir . 'payment-sim.js?v=' . $npe_ver('payment-sim.js')); ?>"></script>
</body></html>

