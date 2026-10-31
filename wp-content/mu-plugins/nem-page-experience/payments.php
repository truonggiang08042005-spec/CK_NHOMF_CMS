<?php
/**
 * Hosted payment gateways. Credentials belong in WooCommerce settings, never templates.
 * Protocol references and activation checklist: tools/README-nem-payments.md.
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/payments-demo.php';

function npe_payments_live_site_ready() {
    return !npe_payments_is_local_site() && wp_parse_url(home_url('/'),PHP_URL_SCHEME) === 'https';
}

function npe_payments_string(array $data, $key) {
    return isset($data[$key]) && is_scalar($data[$key]) ? (string) $data[$key] : '';
}

function npe_payments_momo_signature(array $data, $access_key, $secret_key, $notification = false, $request_type = 'captureWallet') {
    $keys = $notification
        ? ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId']
        : ['amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo', 'partnerCode', 'redirectUrl', 'requestId', 'requestType'];
    if ($notification && $request_type === 'payWithCC') $keys = ['amount','callbackToken','extraData','message','orderId','orderInfo','orderType','partnerClientId','partnerCode','payType','requestId','responseTime','resultCode','transId'];
    $parts = ['accessKey=' . $access_key];
    foreach ($keys as $key) $parts[] = $key . '=' . npe_payments_string($data, $key);
    return hash_hmac('sha256', implode('&', $parts), $secret_key);
}

function npe_payments_verify_momo(array $data, $access_key, $secret_key, $request_type = 'captureWallet') {
    foreach (['partnerCode', 'orderId', 'requestId', 'amount', 'orderInfo', 'orderType', 'transId', 'resultCode', 'message', 'payType', 'responseTime', 'extraData', 'signature'] as $key) {
        if (!array_key_exists($key, $data) || !is_scalar($data[$key])) return false;
    }
    if ($request_type === 'payWithCC') foreach (['callbackToken','partnerClientId'] as $key) if (isset($data[$key]) && !is_scalar($data[$key])) return false;
    $signature = (string) $data['signature'];
    return $access_key !== '' && $secret_key !== '' && preg_match('/^[a-fA-F0-9]{64}$/D', $signature)
        && hash_equals(npe_payments_momo_signature($data, $access_key, $secret_key, true, $request_type), strtolower($signature));
}

function npe_payments_momo_card_ack(array $data, $access_key, $secret_key) {
    $ack = ['partnerCode'=>npe_payments_string($data,'partnerCode'),'requestId'=>npe_payments_string($data,'requestId'),'orderId'=>npe_payments_string($data,'orderId'),'resultCode'=>0,'message'=>'Success','responseTime'=>wp_date('Y-m-d H:i:s', time(), new DateTimeZone('Asia/Ho_Chi_Minh')),'extraData'=>''];
    $parts = ['accessKey='.$access_key];
    foreach (['extraData','message','orderId','partnerCode','requestId','responseTime','resultCode'] as $key) $parts[] = $key.'='.$ack[$key];
    $ack['signature'] = hash_hmac('sha256', implode('&',$parts), $secret_key);
    return $ack;
}

function npe_payments_amount($value) {
    $value = (string) $value;
    if (!preg_match('/^([0-9]+)(?:\.0+)?$/D', $value, $match)) return false;
    return ltrim($match[1], '0') ?: '0';
}

function npe_payments_momo_attempt($order, $provider_order_id) {
    $current = $order->get_meta('_npe_momo_attempt', true);
    if (is_array($current) && ($current['order_id'] ?? '') === $provider_order_id) return $current;
    $previous = $order->get_meta('_npe_momo_attempts', true);
    return is_array($previous) && isset($previous[$provider_order_id]) && is_array($previous[$provider_order_id]) ? $previous[$provider_order_id] : [];
}

function npe_payments_hosted_url($url, array $hosts) {
    $parts = wp_parse_url((string) $url);
    return is_array($parts) && ($parts['scheme'] ?? '') === 'https'
        && empty($parts['user']) && empty($parts['pass'])
        && (!isset($parts['port']) || (int) $parts['port'] === 443)
        && in_array(strtolower($parts['host'] ?? ''), $hosts, true);
}

/** Return an exact JSON member value, preserving bytes used in Payoo's checksum. */
function npe_payments_raw_json_member($json, $member) {
    $length = strlen($json);
    $depth = 0;
    for ($i = 0; $i < $length; $i++) {
        $char = $json[$i];
        if ($char === '{' || $char === '[') { $depth++; continue; }
        if ($char === '}' || $char === ']') { $depth--; continue; }
        if ($char !== '"') continue;
        $start = $i++;
        for (; $i < $length; $i++) {
            if ($json[$i] === '\\') { $i++; continue; }
            if ($json[$i] === '"') break;
        }
        $key = json_decode(substr($json, $start, $i - $start + 1), true);
        if ($depth !== 1 || $key !== $member) continue;
        $cursor = $i + 1;
        while ($cursor < $length && ctype_space($json[$cursor])) $cursor++;
        if (($json[$cursor] ?? '') !== ':') continue;
        $cursor++;
        while ($cursor < $length && ctype_space($json[$cursor])) $cursor++;
        $value_start = $cursor;
        $value_depth = 0;
        $quoted = false;
        for (; $cursor < $length; $cursor++) {
            $c = $json[$cursor];
            if ($quoted) {
                if ($c === '\\') { $cursor++; continue; }
                if ($c === '"') $quoted = false;
                continue;
            }
            if ($c === '"') { $quoted = true; continue; }
            if ($c === '{' || $c === '[') { $value_depth++; continue; }
            if ($c === '}' || $c === ']') {
                if ($value_depth === 0) break;
                $value_depth--;
                continue;
            }
            if ($c === ',' && $value_depth === 0) break;
        }
        return rtrim(substr($json, $value_start, $cursor - $value_start));
    }
    return null;
}

function npe_payments_payoo_checksum($payload, $key) {
    return hash('sha512', $key . $payload);
}

function npe_payments_payoo_verified_response($raw, $key) {
    $data = json_decode($raw, true);
    if (!is_array($data) || ($data['result'] ?? '') !== 'success' || empty($data['checksum'])) return false;
    $order_member = npe_payments_raw_json_member($raw, 'order');
    if (!is_string($order_member)) return false;
    $order_value = json_decode($order_member, true);
    $order_raw = is_string($order_value) ? $order_value : $order_member;
    if (!is_string($order_raw) || !preg_match('/^[a-fA-F0-9]{128}$/D', (string) $data['checksum'])
        || !hash_equals(npe_payments_payoo_checksum($order_raw, $key), strtolower((string) $data['checksum']))) return false;
    $order = is_array($order_value) ? $order_value : json_decode($order_raw, true);
    return is_array($order) ? $order : false;
}

/** Atomic lock shared by PHP workers, including sites without a persistent object cache. */
function npe_payments_lock($name) {
    $key = 'npe_payment_lock_' . hash('sha256', $name);
    $existing = get_option($key);
    if (is_array($existing) && ($existing['expires'] ?? 0) < time()) delete_option($key);
    $value = ['token' => wp_generate_uuid4(), 'expires' => time() + 90];
    return add_option($key, $value, '', false) ? [$key, $value['token']] : false;
}

function npe_payments_unlock($lock) {
    if (!$lock) return;
    $value = get_option($lock[0]);
    if (is_array($value) && hash_equals((string) ($value['token'] ?? ''), $lock[1])) delete_option($lock[0]);
}

function npe_payments_gateway_state($id) {
    if (!function_exists('WC') || !WC()->payment_gateways()) return ['configured' => false, 'enabled' => false, 'testmode' => true, 'demo' => false];
    $all = WC()->payment_gateways()->payment_gateways();
    $gateway = $all[$id] ?? null;
    $demo = $gateway && method_exists($gateway, 'is_demo') && $gateway->is_demo();
    return ['configured' => $demo || ($gateway && method_exists($gateway, 'is_configured') && $gateway->is_configured()),
        'enabled' => $gateway && $gateway->enabled === 'yes',
        'testmode' => !$gateway || $gateway->get_option('testmode', 'yes') === 'yes', 'demo' => (bool) $demo];
}

add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) return;

    abstract class NPE_Hosted_Gateway extends WC_Payment_Gateway {
        public $testmode = true;

        protected function setup_gateway($id, $title, $description) {
            $this->id = $id;
            $this->method_title = $title;
            $this->method_description = $description;
            $this->has_fields = false;
            $this->supports = ['products'];
            $this->init_form_fields();
            $this->init_settings();
            $this->enabled = $this->get_option('enabled', 'no');
            $this->title = $this->get_option('title', $title);
            $this->description = $this->get_option('description', $description);
            $this->testmode = $this->get_option('testmode', 'yes') === 'yes';
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        protected function common_fields($title, $description) {
            return [
                'enabled' => ['title' => 'Bật / Tắt', 'type' => 'checkbox', 'label' => 'Bật phương thức thanh toán', 'default' => 'no'],
                'title' => ['title' => 'Tên hiển thị', 'type' => 'text', 'default' => $title],
                'description' => ['title' => 'Hướng dẫn cho khách', 'type' => 'textarea', 'default' => $description],
                'local_demo' => ['title' => 'Mô phỏng local', 'type' => 'checkbox', 'label' => 'Mô phỏng thanh toán — không trừ tiền, không cần tài khoản merchant', 'default' => 'no', 'description' => 'Chỉ chạy trên localhost, IP loopback hoặc domain .local/.test. Khi bật, không gọi MoMo/Payoo; đơn luôn ghi rõ kết quả mô phỏng và không được xác nhận đã nhận tiền.'],
                'testmode' => ['title' => 'Môi trường thử nghiệm', 'type' => 'checkbox', 'label' => 'Dùng Sandbox (không dùng khóa Production)', 'default' => 'yes', 'description' => 'Sandbox vẫn cần tài khoản thử nghiệm do nhà cung cấp cấp. Website local phải có URL HTTPS công khai để nhận IPN.'],
            ];
        }

        public function is_available() {
            return ($this->is_demo() || ($this->is_configured() && ($this->testmode || npe_payments_live_site_ready()))) && get_woocommerce_currency() === 'VND' && parent::is_available();
        }

        public function is_demo() {
            return $this->get_option('local_demo', 'no') === 'yes' && npe_payments_is_local_site();
        }

        public function payment_fields() {
            if (!$this->is_demo() && $this->description) echo wpautop(wp_kses_post($this->description));
            if ($this->is_demo()) echo '<p class="npe-payment-test"><strong>Mô phỏng local — không trừ tiền.</strong> Bạn có thể thử kết quả thành công, thất bại hoặc hủy.</p>';
            elseif ($this->testmode) echo '<p class="npe-payment-test">Môi trường thử nghiệm — không dùng tiền thật.</p>';
        }

        public function admin_options() {
            parent::admin_options();
            echo '<p><strong>URL IPN:</strong> <code>' . esc_html(WC()->api_request_url('npe_' . $this->id . '_ipn')) . '</code></p>';
            echo '<p><strong>URL quay lại:</strong> <code>' . esc_html(WC()->api_request_url('npe_' . $this->id . '_return')) . '</code></p>';
            if ($this->is_demo()) echo '<p><strong>Đang mô phỏng local: không gửi yêu cầu tới cổng thanh toán và không ghi nhận đã nhận tiền.</strong></p>';
            elseif (!$this->is_configured()) echo '<p style="color:#b32d2e">Chưa sẵn sàng: bổ sung cấu hình bắt buộc bên dưới. Có thể bật Mô phỏng local để kiểm tra giao diện mà không cần tài khoản merchant.</p>';
        }

        protected function order_for_payment($order_id) {
            $order = wc_get_order($order_id);
            if (!$order || $order->get_payment_method() !== $this->id || $order->get_currency() !== 'VND'
                || npe_payments_amount($order->get_total()) === false || (float) $order->get_total() <= 0) {
                throw new RuntimeException('Đơn hàng hoặc số tiền thanh toán không hợp lệ.');
            }
            if ($order->has_status(['cancelled', 'refunded'])) throw new RuntimeException('Đơn đã hủy hoặc hoàn tiền không thể thanh toán lại. Vui lòng tạo đơn hàng mới.');
            if ((!$this->is_demo() && !$this->is_configured()) || $this->enabled !== 'yes') throw new RuntimeException('Phương thức thanh toán chưa được cấu hình. Vui lòng chọn chuyển khoản ngân hàng hoặc COD.');
            if (!$this->is_demo() && !$this->testmode && !npe_payments_live_site_ready()) throw new RuntimeException('Thanh toán Production cần website HTTPS công khai. Hãy dùng Sandbox hoặc mô phỏng local khi kiểm thử.');
            if (!$this->is_demo() && $order->get_meta('_npe_payment_demo', true)) throw new RuntimeException('Đơn mô phỏng không nhận thanh toán thật. Vui lòng tạo đơn hàng mới.');
            return $order;
        }

        protected function error_result($message) {
            wc_add_notice($message, 'error');
            return ['result' => 'failure'];
        }

        protected function request_json($url, array $data) {
            $response = wp_remote_post($url, ['timeout' => 25, 'redirection' => 0, 'headers' => ['Content-Type' => 'application/json; charset=UTF-8'], 'body' => wp_json_encode($data), 'sslverify' => true, 'limit_response_size' => 131072]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) throw new RuntimeException('Không thể kết nối cổng thanh toán. Vui lòng thử lại hoặc chọn phương thức khác.');
            return wp_remote_retrieve_body($response);
        }
    }

    class NPE_Gateway_Momo extends NPE_Hosted_Gateway {
        protected $momo_gateway_id = 'momo';
        protected $momo_request_type = 'captureWallet';
        protected $momo_title = 'Thanh toán online qua ví MoMo';
        public function __construct() {
            $this->setup_gateway($this->momo_gateway_id, $this->momo_title, 'Bạn sẽ được chuyển đến trang thanh toán bảo mật của MoMo. Đơn hàng được xác nhận sau khi nhận kết quả hợp lệ.');
            add_action('woocommerce_api_npe_'.$this->id.'_ipn', [$this, 'handle_ipn']);
            add_action('woocommerce_api_npe_'.$this->id.'_return', [$this, 'handle_return']);
        }

        public function init_form_fields() {
            $this->form_fields = $this->common_fields($this->momo_title, 'Bạn sẽ được chuyển đến trang bảo mật của MoMo để thanh toán.');
            if ($this->momo_gateway_id !== 'momo') $this->form_fields['channel_approved'] = ['title'=>'Quyền nhận thanh toán thẻ','type'=>'checkbox','label'=>'MoMo đã cấp quyền sử dụng phương thức thẻ này cho tài khoản merchant','default'=>'no','description'=>'Chỉ bật sau khi MoMo xác nhận dịch vụ. Mô phỏng local không cần quyền này.'];
            foreach (['partner_code' => 'Partner Code', 'access_key' => 'Access Key', 'secret_key' => 'Secret Key'] as $key => $label) {
                $this->form_fields[$key] = ['title' => $label, 'type' => $key === 'partner_code' ? 'text' : 'password', 'default' => '', 'description'=>$this->momo_gateway_id !== 'momo' ? 'Để trống để dùng bộ khóa trong cấu hình Ví MoMo (cùng môi trường).' : '', 'custom_attributes' => ['autocomplete' => 'new-password']];
            }
        }

        public function credential($key) {
            $value = $this->get_option($key, '');
            if ($this->id !== 'momo' && trim((string)$value) === '') {
                $shared = get_option('woocommerce_momo_settings', []);
                if (!is_array($shared) || (($shared['testmode'] ?? 'yes') === 'yes') !== $this->testmode) return '';
                $value = $shared[$key] ?? '';
            }
            return is_string($value) ? trim($value) : '';
        }

        public function is_configured() {
            return ($this->id === 'momo' || $this->get_option('channel_approved','no') === 'yes') && $this->credential('partner_code') !== '' && $this->credential('access_key') !== '' && $this->credential('secret_key') !== '';
        }

        public function process_payment($order_id) {
            $lock = npe_payments_lock('momo-create-' . $order_id);
            if (!$lock) return $this->error_result('Yêu cầu thanh toán đang được xử lý. Vui lòng chờ vài giây.');
            try {
                $order = $this->order_for_payment($order_id);
                if ($order->is_paid()) return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
                if ($this->is_demo()) return npe_payments_start_demo($order, $this->id);
                $minimum = $this->momo_request_type === 'payWithATM' ? 10000 : 1000;
                $maximum = $this->momo_request_type === 'payWithCC' ? 10000000 : 50000000;
                if ((float)$order->get_total() < $minimum || (float)$order->get_total() > $maximum) throw new RuntimeException('Phương thức này hỗ trợ số tiền từ '.number_format($minimum).' đến '.number_format($maximum).' đồng.');
                if ($this->momo_request_type === 'payWithCC' && !is_email($order->get_billing_email())) throw new RuntimeException('Vui lòng nhập email hợp lệ để thanh toán bằng thẻ quốc tế.');
                $mode = $this->testmode ? 'sandbox' : 'production';
                $attempt = $order->get_meta('_npe_momo_attempt', true);
                if (!is_array($attempt) || ($attempt['mode'] ?? '') !== $mode || ($attempt['request_type'] ?? 'captureWallet') !== $this->momo_request_type || ($attempt['gateway'] ?? 'momo') !== $this->id || ($attempt['amount'] ?? '') !== npe_payments_amount($order->get_total()) || ($attempt['created'] ?? 0) < time() - 900) {
                    if (is_array($attempt) && !empty($attempt['order_id'])) {
                        $previous = $order->get_meta('_npe_momo_attempts', true);
                        $previous = is_array($previous) ? $previous : [];
                        $previous[$attempt['order_id']] = $attempt;
                        $order->update_meta_data('_npe_momo_attempts', $previous);
                    }
                    $unique = str_replace('-', '', wp_generate_uuid4());
                    $attempt = ['order_id' => 'NEM_' . $order_id . '_' . substr($unique, 0, 16), 'request_id' => 'NEM_' . substr($unique, 0, 28), 'amount' => npe_payments_amount($order->get_total()), 'mode' => $mode, 'created' => time(), 'request_type'=>$this->momo_request_type, 'gateway'=>$this->id];
                    $order->update_meta_data('_npe_momo_attempt', $attempt);
                    $order->save();
                }
                $payload = ['partnerCode' => trim($this->credential('partner_code')), 'requestType' => $this->momo_request_type, 'ipnUrl' => WC()->api_request_url('npe_'.$this->id.'_ipn'), 'redirectUrl' => WC()->api_request_url('npe_'.$this->id.'_return'), 'orderId' => $attempt['order_id'], 'amount' => (int) $attempt['amount'], 'orderInfo' => 'Thanh toan don hang NEM ' . $order->get_id(), 'requestId' => $attempt['request_id'], 'extraData' => '', 'autoCapture' => true, 'lang' => 'vi'];
                if ($this->momo_request_type === 'payWithCC') $payload['userInfo'] = ['email'=>$order->get_billing_email()];
                $payload['signature'] = npe_payments_momo_signature($payload, $this->credential('access_key'), $this->credential('secret_key'));
                $endpoint = $this->testmode ? 'https://test-payment.momo.vn/v2/gateway/api/create' : 'https://payment.momo.vn/v2/gateway/api/create';
                $response = json_decode($this->request_json($endpoint, $payload), true);
                if (!is_array($response) || !isset($response['resultCode']) || (string) $response['resultCode'] !== '0'
                    || npe_payments_string($response, 'partnerCode') !== $payload['partnerCode'] || npe_payments_string($response, 'orderId') !== $attempt['order_id']
                    || npe_payments_string($response, 'requestId') !== $attempt['request_id'] || npe_payments_amount($response['amount'] ?? '') !== $attempt['amount']
                    || !npe_payments_hosted_url($response['payUrl'] ?? '', [$this->testmode ? 'test-payment.momo.vn' : 'payment.momo.vn'])) {
                    throw new RuntimeException('MoMo chưa tạo được phiên thanh toán hợp lệ. Vui lòng thử lại hoặc chọn phương thức khác.');
                }
                $order->add_order_note('Đã tạo phiên MoMo ' . ($this->testmode ? 'Sandbox' : 'Production') . '; đang chờ IPN xác thực.');
                if (WC()->cart) WC()->cart->empty_cart();
                return ['result' => 'success', 'redirect' => $response['payUrl']];
            } catch (Throwable $e) {
                return $this->error_result($e->getMessage());
            } finally {
                npe_payments_unlock($lock);
            }
        }

        /** Returns a status code, and mutates orders only after all signed fields match. */
        public function accept_notification(array $data) {
            if (!$this->is_configured() || !npe_payments_verify_momo($data, $this->credential('access_key'), $this->credential('secret_key'), $this->momo_request_type)
                || npe_payments_string($data, 'partnerCode') !== trim($this->credential('partner_code'))
                || !preg_match('/^NEM_([1-9][0-9]*)_[a-f0-9]{16}$/D', npe_payments_string($data, 'orderId'), $match)) return 400;
            $lock = npe_payments_lock('momo-ipn-' . $match[1]);
            if (!$lock) return 503;
            try {
                $order = wc_get_order((int) $match[1]);
                if (!$order || $order->get_payment_method() !== $this->id || $order->get_currency() !== 'VND' || $order->get_meta('_npe_payment_demo', true)) return 400;
                $attempt = npe_payments_momo_attempt($order, $data['orderId']);
                if (!is_array($attempt) || ($attempt['order_id'] ?? '') !== $data['orderId'] || ($attempt['request_id'] ?? '') !== $data['requestId']
                    || ($attempt['gateway'] ?? 'momo') !== $this->id || ($attempt['request_type'] ?? 'captureWallet') !== $this->momo_request_type
                    || ($attempt['mode'] ?? '') !== ($this->testmode ? 'sandbox' : 'production')
                    || ($attempt['amount'] ?? '') !== npe_payments_amount($data['amount']) || npe_payments_amount($order->get_total()) !== npe_payments_amount($data['amount'])
                    || $data['orderInfo'] !== 'Thanh toan don hang NEM ' . $order->get_id() || $data['extraData'] !== '' || $data['orderType'] !== 'momo_wallet') return 400;
                if ((string) $data['resultCode'] !== '0') {
                    $code = (string) $data['resultCode'];
                    if ($order->get_meta('_npe_momo_last_result', true) !== $code) {
                        $order->update_meta_data('_npe_momo_last_result', $code);
                        $order->add_order_note('MoMo thông báo mã kết quả ' . sanitize_text_field($code) . '. Chưa xác nhận thanh toán.');
                        $order->save();
                    }
                    return 204;
                }
                $transaction = npe_payments_string($data, 'transId');
                if (($this->momo_request_type === 'payWithATM' && $data['payType'] !== 'napas') || ($this->momo_request_type === 'payWithCC' && $data['payType'] !== 'credit')) return 400;
                if (!preg_match('/^[1-9][0-9]*$/D', $transaction)) return 400;
                if ($order->is_paid()) return (string) $order->get_transaction_id() === $transaction ? 204 : 409;
                if ($order->has_status(['cancelled', 'refunded'])) return 409;
                $key = 'npe_momo_txn_' . hash('sha256', $attempt['mode'] . ':' . $transaction);
                if (!add_option($key, $order->get_id(), '', false) && (int) get_option($key) !== $order->get_id()) return 409;
                if ($attempt['mode'] === 'sandbox') {
                    $recorded = $order->get_meta('_npe_momo_sandbox_transaction', true);
                    if ($recorded !== '') return (string)$recorded === $transaction ? 204 : 409;
                    $order->update_meta_data('_npe_momo_sandbox_transaction', $transaction);
                    $order->save();
                    $order->update_status('on-hold', 'MOMO SANDBOX: đã xác thực kết quả thử nghiệm. Không nhận tiền thật; không giao hàng từ kết quả sandbox.');
                    return 204;
                }
                $order->payment_complete($transaction);
                $order->add_order_note('Đã xác thực IPN MoMo; giao dịch ' . $transaction . '.');
                return 204;
            } finally {
                npe_payments_unlock($lock);
            }
        }

        public function handle_ipn() {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { status_header(405); exit; }
            $raw = file_get_contents('php://input', false, null, 0, 131073);
            if (strlen($raw) > 131072) { status_header(413); exit; }
            $data = json_decode($raw, true);
            $status = is_array($data) ? $this->accept_notification($data) : 400;
            if ($status === 204 && $this->momo_request_type === 'payWithCC') {
                status_header(200);
                header('Content-Type: application/json; charset=UTF-8');
                echo wp_json_encode(npe_payments_momo_card_ack($data, $this->credential('access_key'), $this->credential('secret_key')));
            } else status_header($status);
            exit;
        }

        public function handle_return() {
            $data = wp_unslash($_GET);
            // The browser return is used only for navigation, never payment_complete().
            if (!is_array($data) || !npe_payments_verify_momo($data, $this->credential('access_key'), $this->credential('secret_key'), $this->momo_request_type)
                || !preg_match('/^NEM_([1-9][0-9]*)_[a-f0-9]{16}$/D', npe_payments_string($data, 'orderId'), $match)) {
                wp_safe_redirect(wc_get_checkout_url()); exit;
            }
            $order = wc_get_order((int) $match[1]);
            $attempt = $order ? npe_payments_momo_attempt($order, $data['orderId']) : [];
            if (!$order || $order->get_payment_method() !== $this->id || !is_array($attempt) || ($attempt['order_id'] ?? '') !== $data['orderId'] || ($attempt['request_id'] ?? '') !== $data['requestId'] || ($attempt['amount'] ?? '') !== npe_payments_amount($data['amount'])) {
                wp_safe_redirect(wc_get_checkout_url()); exit;
            }
            wp_safe_redirect($this->get_return_url($order)); exit;
        }
    }

    class NPE_Gateway_Momo_Atm extends NPE_Gateway_Momo {
        protected $momo_gateway_id = 'momo_atm';
        protected $momo_request_type = 'payWithATM';
        protected $momo_title = 'Thẻ ATM nội địa / NAPAS qua MoMo';
    }

    class NPE_Gateway_Momo_Card extends NPE_Gateway_Momo {
        protected $momo_gateway_id = 'momo_card';
        protected $momo_request_type = 'payWithCC';
        protected $momo_title = 'Thẻ quốc tế qua MoMo';
    }

    class NPE_Gateway_Payoo extends NPE_Hosted_Gateway {
        public function __construct() {
            $this->setup_gateway('payoo', 'Thẻ ATM / Visa / Master / JCB / QR Pay / Ví điện tử khác qua Payoo', 'Thanh toán trên trang bảo mật của Payoo bằng các phương thức được cấp cho tài khoản cửa hàng.');
            add_action('woocommerce_api_npe_payoo_ipn', [$this, 'handle_ipn']);
            add_action('woocommerce_api_npe_payoo_return', [$this, 'handle_return']);
        }

        public function init_form_fields() {
            $this->form_fields = $this->common_fields('Thẻ ATM / Visa / Master / JCB / QR Pay / Ví điện tử khác qua Payoo', 'Bạn sẽ được chuyển đến trang thanh toán bảo mật Payoo.');
            $this->form_fields['integration_note'] = ['title' => 'Kích hoạt tích hợp', 'type' => 'title', 'description' => 'Cần tài khoản merchant, domain/IP được Payoo duyệt và adapter IPN theo tài liệu merchant của Payoo. Khi chưa có adapter xác thực, phương thức này luôn khóa để tránh ghi nhận sai thanh toán. Xem tools/README-nem-payments.md.'];
            foreach (['merchant_username' => 'Tài khoản doanh nghiệp', 'shop_id' => 'Shop ID', 'shop_title' => 'Shop Title', 'shop_domain' => 'Domain đã đăng ký', 'checksum_key' => 'Checksum Key'] as $key => $label) {
                $this->form_fields[$key] = ['title' => $label, 'type' => $key === 'checksum_key' ? 'password' : 'text', 'default' => '', 'custom_attributes' => ['autocomplete' => 'new-password']];
            }
            $this->form_fields['payment_group'] = ['title' => 'Nhóm phương thức', 'type' => 'text', 'default' => 'cc,bank-account,qrcode,payoo-account', 'description' => 'Các phương thức thực tế phụ thuộc hợp đồng với Payoo.'];
        }

        public function is_configured() {
            foreach (['merchant_username', 'shop_id', 'shop_title', 'shop_domain', 'checksum_key'] as $key) if (trim($this->get_option($key, '')) === '') return false;
            return preg_match('/^[1-9][0-9]*$/D', $this->get_option('shop_id')) && (bool) apply_filters('npe_payoo_notification_adapter_ready', false, $this);
        }

        public function build_order_xml($order, $order_no) {
            $values = ['username' => $this->get_option('merchant_username'), 'shop_id' => $this->get_option('shop_id'), 'shop_title' => $this->get_option('shop_title'), 'shop_domain' => $this->get_option('shop_domain'),
                'shop_back_url' => rawurlencode(add_query_arg(['order_id' => $order->get_id(), 'key' => $order->get_order_key()], WC()->api_request_url('npe_payoo_return'))), 'order_no' => $order_no,
                'order_cash_amount' => npe_payments_amount($order->get_total()), 'order_description' => rawurlencode('Thanh toan don hang NEM ' . $order->get_id()),
                'notify_url' => rawurlencode(WC()->api_request_url('npe_payoo_ipn')), 'validity_time' => wp_date('YmdHis', time() + 1800, new DateTimeZone('Asia/Ho_Chi_Minh'))];
            $xml = '<shops><shop>';
            foreach ($values as $key => $value) $xml .= '<' . $key . '>' . htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $key . '>';
            return $xml . '</shop></shops>';
        }

        public function process_payment($order_id) {
            $lock = npe_payments_lock('payoo-create-' . $order_id);
            if (!$lock) return $this->error_result('Yêu cầu thanh toán đang được xử lý. Vui lòng chờ vài giây.');
            try {
                $order = $this->order_for_payment($order_id);
                if ($order->is_paid()) return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
                if ($this->is_demo()) return npe_payments_start_demo($order, $this->id);
                $order_no = 'NEM_' . $order_id . '_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
                $xml = $this->build_order_xml($order, $order_no);
                $order->update_meta_data('_npe_payoo_attempt', ['order_no' => $order_no, 'amount' => npe_payments_amount($order->get_total()), 'mode' => $this->testmode ? 'sandbox' : 'production']);
                $order->save();
                $payload = ['data' => $xml, 'checksum' => npe_payments_payoo_checksum($xml, $this->get_option('checksum_key')), 'refer' => $this->get_option('shop_domain'), 'payment_group' => $this->get_option('payment_group')];
                $endpoint = $this->testmode ? 'https://newsandbox.payoo.com.vn/v2/checkout' : 'https://payoo.vn/v2/checkout';
                $data = npe_payments_payoo_verified_response($this->request_json($endpoint, $payload), $this->get_option('checksum_key'));
                $hosts = $this->testmode ? ['newsandbox.payoo.com.vn'] : ['payoo.vn', 'www.payoo.vn'];
                if (!$data || npe_payments_string($data, 'order_no') !== $order_no || npe_payments_amount($data['amount'] ?? '') !== npe_payments_amount($order->get_total()) || !npe_payments_hosted_url($data['payment_url'] ?? '', $hosts)) throw new RuntimeException('Payoo chưa trả về phiên thanh toán đã xác thực. Vui lòng chọn phương thức khác.');
                $order->add_order_note('Đã tạo phiên Payoo; chờ xác thực IPN.');
                if (WC()->cart) WC()->cart->empty_cart();
                return ['result' => 'success', 'redirect' => $data['payment_url']];
            } catch (Throwable $e) {
                return $this->error_result($e->getMessage());
            } finally {
                npe_payments_unlock($lock);
            }
        }

        public function handle_ipn() {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { status_header(405); exit; }
            // Merchant-specific Payoo IPN profiles must be verified against its supplied
            // integration contract. Never guess a signature algorithm or trust $_POST.
            // The adapter must validate identity, amount, currency, transaction reuse,
            // order state and signature before it calls payment_complete().
            if (!apply_filters('npe_payoo_notification_adapter_ready', false, $this)) { status_header(503); exit; }
            do_action('npe_payoo_handle_verified_ipn', $this);
            status_header(501);
            exit;
        }

        public function handle_return() {
            $id = isset($_GET['order_id']) && is_scalar($_GET['order_id']) ? absint($_GET['order_id']) : 0;
            $key = isset($_GET['key']) && is_string($_GET['key']) ? wp_unslash($_GET['key']) : '';
            $order = $id ? wc_get_order($id) : false;
            // Navigating with an order key never changes payment state.
            if (!$order || $order->get_payment_method() !== 'payoo' || !hash_equals((string) $order->get_order_key(), $key)) { wp_safe_redirect(wc_get_checkout_url()); exit; }
            wp_safe_redirect($this->get_return_url($order)); exit;
        }
    }

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'NPE_Gateway_Momo';
        $gateways[] = 'NPE_Gateway_Momo_Atm';
        $gateways[] = 'NPE_Gateway_Momo_Card';
        $gateways[] = 'NPE_Gateway_Payoo';
        return $gateways;
    });
}, 20);
