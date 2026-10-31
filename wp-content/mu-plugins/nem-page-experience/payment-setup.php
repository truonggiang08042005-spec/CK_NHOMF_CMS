<?php
/** Merchant setup and bank-transfer instructions; no credentials are exposed to the browser. */
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'Kết nối thanh toán', 'Kết nối thanh toán', 'manage_woocommerce', 'npe-payments', 'npe_render_payment_setup');
});

function npe_render_payment_setup() {
    if (!current_user_can('manage_woocommerce')) return;
    $all = WC()->payment_gateways()->payment_gateways();
    echo '<div class="wrap"><h1>Kết nối thanh toán</h1><p>Chuẩn bị chuyển khoản ngân hàng, ví MoMo, thẻ ATM nội địa và thẻ quốc tế. Chọn Cấu hình để nhập thông tin do ngân hàng hoặc MoMo cấp.</p>';
    echo '<table class="widefat striped"><thead><tr><th>Phương thức</th><th>Tình trạng</th><th>Thông tin cần bổ sung</th><th></th></tr></thead><tbody>';
    foreach (['bacs'=>'Chuyển khoản ngân hàng','momo'=>'Ví MoMo','momo_atm'=>'Thẻ ATM nội địa / NAPAS','momo_card'=>'Thẻ quốc tế','cod'=>'Thanh toán khi nhận hàng'] as $id=>$name) {
        $gateway = $all[$id] ?? null;
        $demo = $gateway && method_exists($gateway,'is_demo') && $gateway->is_demo();
        $ready = $id === 'bacs' ? npe_checkout_has_bank_account() : ($id === 'cod' || ($gateway && method_exists($gateway,'is_configured') && $gateway->is_configured()));
        $state = $demo ? 'Mô phỏng local — không nhận tiền thật' : (!$ready ? 'Chưa kết nối' : ($gateway && $gateway->enabled === 'yes' ? 'Đã cấu hình' : 'Đã cấu hình, đang tắt'));
        if (!$demo && $ready && in_array($id,['momo','momo_atm','momo_card'],true)) $state .= $gateway->testmode ? ' · Sandbox' : ' · Production';
        $need = $id === 'bacs' ? 'Tên ngân hàng, số tài khoản, chủ tài khoản; ảnh QR từ ngân hàng (tùy chọn).' : ($id === 'cod' ? 'Bật COD và hướng dẫn giao hàng.' : 'Tài khoản merchant MoMo, Partner Code, Access Key, Secret Key và URL HTTPS nhận kết quả.');
        if (in_array($id,['momo_atm','momo_card'],true)) $need .= ' MoMo phải cấp quyền nhận loại thẻ này; có thể dùng chung khóa Ví MoMo cùng môi trường.';
        $url = add_query_arg(['page'=>'wc-settings','tab'=>'checkout','section'=>$id],admin_url('admin.php'));
        echo '<tr><td><strong>'.esc_html($name).'</strong></td><td>'.esc_html($state).'</td><td>'.esc_html($need).'</td><td><a class="button" href="'.esc_url($url).'">Cấu hình</a></td></tr>';
    }
    echo '</tbody></table><h2>Đưa vào sử dụng</h2><ol><li>Nhập tài khoản ngân hàng nhận tiền thật và QR do ngân hàng cấp.</li><li>Đăng ký merchant MoMo; yêu cầu kích hoạt ví, thẻ ATM và thẻ quốc tế theo nhu cầu.</li><li>Đưa website lên tên miền HTTPS công khai. Sao chép URL IPN và URL quay lại trong từng cấu hình gửi cho MoMo.</li><li>Nhập khóa Sandbox, tắt Mô phỏng local và thử giao dịch bằng dữ liệu thử do MoMo cấp.</li><li>Sau nghiệm thu, nhập khóa Production tương ứng và tắt Sandbox. Chỉ xác nhận đã nhận tiền từ thông báo có chữ ký hợp lệ.</li></ol><p>Chuyển khoản ngân hàng chờ cửa hàng đối soát. Số thẻ và CVV được nhập trên trang bảo mật của nhà cung cấp, website không thu hoặc lưu các dữ liệu này.</p>';
    echo '<p><a class="button" href="'.esc_url(npe_visual_url('checkout')).'">Sửa giao diện thanh toán</a> <a class="button" href="'.esc_url(wc_get_checkout_url()).'">Xem trang thanh toán</a></p></div>';
}

add_filter('woocommerce_settings_api_form_fields_bacs', function ($fields) {
    $fields['npe_bank_bin'] = ['title'=>'Ngân hàng (VietQR)','type'=>'select','description'=>'Dùng để tạo mã VietQR động có sẵn số tiền và nội dung cho từng đơn. Để "Tự nhận diện" nếu tên ngân hàng trong tài khoản đã rõ ràng (VD: Vietcombank, MB Bank).','default'=>'','options'=>['' => 'Tự nhận diện theo tên ngân hàng'] + npe_vn_banks()];
    $fields['npe_qr_image'] = ['title'=>'Ảnh QR ngân hàng (dự phòng)','type'=>'text','description'=>'Chỉ dùng khi không xác định được ngân hàng để tạo VietQR động.','default'=>'','custom_attributes'=>['readonly'=>'readonly']];
    $fields['npe_transfer_prefix'] = ['title'=>'Tiền tố nội dung chuyển khoản','type'=>'text','description'=>'Nội dung chuyển khoản gồm tiền tố này và mã đơn hàng (không dấu).','default'=>'NEM'];
    return $fields;
});

add_action('admin_enqueue_scripts', function () {
    if (($_GET['page'] ?? '') !== 'wc-settings' || ($_GET['section'] ?? '') !== 'bacs') return;
    wp_enqueue_media();
    wp_enqueue_script('npe-payment-ui',content_url('/mu-plugins/nem-page-experience/payment-ui.js'),['jquery','media-editor'],filemtime(__DIR__.'/payment-ui.js'),true);
});

add_action('woocommerce_checkout_order_created', function ($order) {
    if ($order->get_payment_method() !== 'bacs') return;
    $settings = (array) get_option('woocommerce_bacs_settings', []);
    $order->update_meta_data('_npe_bank_qr_image',absint($settings['npe_qr_image'] ?? 0));
    $order->update_meta_data('_npe_bank_reference',npe_bank_transfer_content(($settings['npe_transfer_prefix'] ?? 'NEM').' '.$order->get_order_number()));
    $order->save();
});

/** Customer clicks "Tôi đã chuyển khoản": record it for the shop to reconcile (never marks paid). */
add_action('woocommerce_api_npe_bank_notify', function () {
    $id = isset($_POST['order_id']) && is_scalar($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $key = isset($_POST['key']) && is_string($_POST['key']) ? wp_unslash($_POST['key']) : '';
    $order = $id ? wc_get_order($id) : false;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$order || $order->get_payment_method() !== 'bacs' || !hash_equals((string) $order->get_order_key(), $key)
        || !wp_verify_nonce(isset($_POST['npe_bank_nonce']) ? sanitize_text_field(wp_unslash($_POST['npe_bank_nonce'])) : '', 'npe_bank_notify_'.$id)) {
        wp_die('Liên kết không hợp lệ hoặc đã hết hạn.', 'Chuyển khoản', ['response' => 403]);
    }
    if (!$order->get_meta('_npe_bank_customer_confirmed') && !$order->is_paid()) {
        $order->update_meta_data('_npe_bank_customer_confirmed', time());
        $order->add_order_note('Khách hàng báo đã chuyển khoản lúc '.wp_date('H:i d/m/Y').'. Vui lòng đối soát sao kê trước khi xác nhận.', 1);
        $order->save();
    }
    wp_safe_redirect($order->get_checkout_order_received_url()); exit;
});

// Our template shows richer bank details; avoid WooCommerce printing them twice.
add_action('template_redirect', function () {
    if (!function_exists('WC') || !WC()->payment_gateways()) return;
    $gateway = WC()->payment_gateways()->payment_gateways()['bacs'] ?? null;
    if ($gateway) remove_action('woocommerce_thankyou_bacs', [$gateway, 'thankyou_page']);
});

function npe_bank_copy_row($label, $value, $display = null) {
    return '<div class="npe-bank-row"><span>'.esc_html($label).'</span><strong>'.($display ?? esc_html($value)).'</strong><button type="button" class="npe-copy-payment" data-copy="'.esc_attr($value).'" aria-label="'.esc_attr('Sao chép '.$label).'">Sao chép</button></div>';
}

function npe_bank_order_instructions($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== 'bacs' || $order->is_paid() || $order->has_status(['cancelled','refunded','failed'])) return;
    $settings = (array) get_option('woocommerce_bacs_settings', []);
    $reference = $order->get_meta('_npe_bank_reference') ?: npe_bank_transfer_content(($settings['npe_transfer_prefix'] ?? 'NEM').' '.$order->get_order_number());
    $amount = (int) npe_payments_amount($order->get_total());
    $account = npe_bank_primary_account();
    $payload = $account ? npe_vietqr_payload($account['bin'], $account['account_number'], $amount, $reference) : '';
    $image = absint($order->get_meta('_npe_bank_qr_image') ?: ($settings['npe_qr_image'] ?? 0));
    echo '<section class="npe-bank-instructions"><h2>Chuyển khoản để hoàn tất đơn hàng</h2><div class="npe-bank-grid">';
    echo '<div class="npe-bank-qr-col">';
    if ($payload) echo '<div class="npe-vietqr" data-qr="'.esc_attr($payload).'" role="img" aria-label="Mã VietQR chuyển khoản"></div><p class="npe-vietqr-brand"><b>VIET</b>QR · <span>napas 247</span></p><p class="npe-bank-hint">Mở ứng dụng ngân hàng bất kỳ → Quét QR. Số tiền và nội dung đã được điền sẵn.</p>';
    elseif ($image && wp_attachment_is_image($image)) echo wp_get_attachment_image($image,'medium',false,['class'=>'npe-bank-qr','alt'=>'Mã QR tài khoản nhận tiền của cửa hàng']).'<p class="npe-bank-hint">Quét QR và nhập đúng số tiền, nội dung bên cạnh.</p>';
    echo '</div><div class="npe-bank-info">';
    if ($account) {
        echo npe_bank_copy_row('Ngân hàng', $account['bank_name']);
        echo npe_bank_copy_row('Số tài khoản', $account['account_number']);
        echo npe_bank_copy_row('Chủ tài khoản', $account['account_name']);
    }
    echo npe_bank_copy_row('Số tiền', (string) $amount, wp_kses_post($order->get_formatted_order_total()));
    echo npe_bank_copy_row('Nội dung', $reference, '<code>'.esc_html($reference).'</code>');
    echo '<p class="npe-bank-warn">Vui lòng ghi <b>đúng nội dung</b> để cửa hàng xác nhận nhanh. Đơn được giữ trong 24 giờ.</p>';
    if ($order->get_meta('_npe_bank_customer_confirmed')) {
        echo '<p class="npe-bank-done">✓ Bạn đã báo chuyển khoản. Cửa hàng sẽ xác nhận sau khi kiểm tra tài khoản.</p>';
    } else {
        echo '<form method="post" action="'.esc_url(WC()->api_request_url('npe_bank_notify')).'"><input type="hidden" name="order_id" value="'.esc_attr($order->get_id()).'"><input type="hidden" name="key" value="'.esc_attr($order->get_order_key()).'">'.wp_nonce_field('npe_bank_notify_'.$order->get_id(), 'npe_bank_nonce', false, false).'<button type="submit" class="npe-bank-confirm">Tôi đã chuyển khoản</button></form>';
    }
    echo '</div></div><span class="npe-copy-status" role="status" aria-live="polite"></span></section>';
}
add_action('woocommerce_thankyou_bacs','npe_bank_order_instructions',25);

add_action('woocommerce_after_checkout_validation', function ($data,$errors) {
    if (($data['payment_method'] ?? '') === 'momo_card' && !is_email($data['billing_email'] ?? '')) $errors->add('npe_card_email','Vui lòng nhập email hợp lệ để thanh toán bằng thẻ quốc tế.');
},10,2);

add_action('wp_enqueue_scripts',function () {
    if (!(npe_is_checkout() || npe_is_thankyou())) return;
    $deps = ['jquery'];
    if (npe_is_thankyou()) {
        wp_register_script('npe-jquery-qrcode', plugins_url('woocommerce/assets/js/jquery-qrcode/jquery.qrcode.min.js'), ['jquery'], '1.0', true);
        $deps[] = 'npe-jquery-qrcode';
    }
    wp_enqueue_script('npe-payment-ui',content_url('/mu-plugins/nem-page-experience/payment-ui.js'),$deps,filemtime(__DIR__.'/payment-ui.js'),true);
    wp_enqueue_style('npe-payment-ui',content_url('/mu-plugins/nem-page-experience/payment-ui.css'),[],filemtime(__DIR__.'/payment-ui.css'));
},1002);
