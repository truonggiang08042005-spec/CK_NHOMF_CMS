<?php
/** Run with PHP CLI. All provider requests, orders and WP storage are test doubles. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', dirname(__DIR__) . '/');
$actions = $filters = $options = $orders = $gateway_settings = [];
$checks = $http_count = 0;
$http_mock = null;
$local_site_url = 'http://wordpress.local/CK_NHOMF_CMS/';
function check($condition, $message) { global $checks; $checks++; if (!$condition) throw new RuntimeException('FAIL: ' . $message); }
function add_action($name, $callback, $priority = 10, $args = 1) { global $actions; $actions[$name][$priority][] = $callback; }
function add_filter($name, $callback, $priority = 10, $args = 1) { global $filters; $filters[$name][$priority][] = $callback; }
function apply_filters($name, $value, ...$args) { global $filters; $callbacks = $filters[$name] ?? []; ksort($callbacks); foreach ($callbacks as $group) foreach ($group as $callback) $value = $callback($value, ...$args); return $value; }
function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
function add_option($key, $value, $deprecated = '', $autoload = false) { global $options; if (array_key_exists($key, $options)) return false; $options[$key] = $value; return true; }
function delete_option($key) { global $options; unset($options[$key]); return true; }
function wp_generate_uuid4() { static $counter = 0; return sprintf('%08x-0000-4000-8000-%012d', ++$counter, $counter); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function home_url($path = '/') { global $local_site_url; return $local_site_url; }
function wp_salt($scheme) { return 'LOCAL_TEST_SIGNATURE_SECRET'; }
function wp_json_encode($data) { return json_encode($data); }
function wp_remote_post($url, $args) { global $http_mock, $http_count; $http_count++; if (!$http_mock) throw new RuntimeException('Unexpected HTTP request'); return $http_mock($url, $args); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code($response) { return $response['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function wc_add_notice($message, $type) { }
function wc_get_order($id) { global $orders; return $orders[$id] ?? false; }
function get_woocommerce_currency() { return 'VND'; }
function sanitize_text_field($value) { return strip_tags($value); }
function add_query_arg($args, $url) { return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($args); }
function wp_date($format, $time, $zone) { return (new DateTimeImmutable('@' . $time))->setTimezone($zone)->format($format); }
function WC() { static $wc; if (!$wc) $wc = new class { public $cart; public function __construct() { $this->cart = new class { public $emptied = 0; public function empty_cart() { $this->emptied++; } }; } public function api_request_url($name) { return 'https://shop.example/?wc-api=' . $name; } }; return $wc; }
class WP_Error { }
#[AllowDynamicProperties]
class WC_Payment_Gateway {
    public function init_settings() { }
    public function get_option($key, $default = '') { global $gateway_settings; return $gateway_settings[$this->id][$key] ?? $this->form_fields[$key]['default'] ?? $default; }
    public function is_available() { return $this->enabled === 'yes'; }
    public function get_return_url($order) { return 'https://shop.example/order/' . $order->get_id(); }
}
class PaymentTestOrder {
    public $id, $method, $currency = 'VND', $total = '100000.00', $meta = [], $notes = [], $status = 'pending', $transaction = '', $completed = 0;
    public function __construct($id, $method = 'momo') { $this->id = $id; $this->method = $method; }
    public function get_id() { return $this->id; }
    public function get_order_key() { return 'test-order-key'; }
    public function get_total() { return $this->total; }
    public function get_currency() { return $this->currency; }
    public function get_payment_method() { return $this->method; }
    public function get_meta($key, $single = true) { return $this->meta[$key] ?? ''; }
    public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
    public function save() { }
    public function is_paid() { return $this->status === 'processing'; }
    public function has_status($states) { return in_array($this->status, $states, true); }
    public function get_transaction_id() { return $this->transaction; }
    public function add_order_note($note) { $this->notes[] = $note; }
    public function update_status($status, $note) { $this->status = $status; $this->notes[] = $note; }
    public function payment_complete($transaction) { $this->completed++; $this->transaction = $transaction; $this->status = 'processing'; }
}
require dirname(__DIR__) . '/wp-content/mu-plugins/nem-page-experience/payments.php';
ksort($actions['plugins_loaded']);
foreach ($actions['plugins_loaded'] as $group) foreach ($group as $callback) $callback();

$gateway = new NPE_Gateway_Momo();
check(!$gateway->is_available(), 'unconfigured MoMo is unavailable');
$orders[1] = new PaymentTestOrder(1);
check($gateway->process_payment(1)['result'] === 'failure' && $http_count === 0, 'missing credentials cannot send HTTP or create fake success');
$gateway_settings['momo'] = ['enabled' => 'yes', 'testmode' => 'yes', 'partner_code' => 'TEST_PARTNER', 'access_key' => 'TEST_ACCESS', 'secret_key' => 'TEST_SECRET'];
$gateway = new NPE_Gateway_Momo();
$orders[42] = new PaymentTestOrder(42);
$last_payload = null;
$http_mock = function ($url, $args) use (&$last_payload) {
    check($url === 'https://test-payment.momo.vn/v2/gateway/api/create', 'sandbox is the default endpoint');
    check($args['sslverify'] === true && $args['redirection'] === 0, 'provider HTTP verifies TLS and does not follow redirects');
    $last_payload = json_decode($args['body'], true);
    check(hash_equals(npe_payments_momo_signature($last_payload, 'TEST_ACCESS', 'TEST_SECRET'), $last_payload['signature']), 'create request signature');
    return ['code' => 200, 'body' => json_encode(['partnerCode' => 'TEST_PARTNER', 'orderId' => $last_payload['orderId'], 'requestId' => $last_payload['requestId'], 'amount' => $last_payload['amount'], 'resultCode' => 0, 'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?t=test'])];
};
$result = $gateway->process_payment(42);
check($result['result'] === 'success' && str_starts_with($result['redirect'], 'https://test-payment.momo.vn/'), 'checkout redirects to hosted provider');
check($orders[42]->completed === 0 && $orders[42]->status === 'pending', 'creating a session does not mark an order paid');
$first_order_id = $last_payload['orderId'];
$gateway->process_payment(42);
check($last_payload['orderId'] === $first_order_id, 'retry uses same payment attempt for provider idempotency');
$attempt = $orders[42]->get_meta('_npe_momo_attempt');
$data = ['partnerCode' => 'TEST_PARTNER', 'orderId' => $attempt['order_id'], 'requestId' => $attempt['request_id'], 'amount' => 100000, 'orderInfo' => 'Thanh toan don hang NEM 42', 'orderType' => 'momo_wallet', 'transId' => 12345678, 'resultCode' => 0, 'message' => 'Successful.', 'payType' => 'qr', 'responseTime' => 1793318400000, 'extraData' => ''];
$data['signature'] = npe_payments_momo_signature($data, 'TEST_ACCESS', 'TEST_SECRET', true);
check(npe_payments_verify_momo($data, 'TEST_ACCESS', 'TEST_SECRET'), 'valid notification signature');
$tampered = $data; $tampered['amount'] = 1;
check($gateway->accept_notification($tampered) === 400 && $orders[42]->completed === 0, 'unsigned amount tampering is rejected');
$tampered['signature'] = npe_payments_momo_signature($tampered, 'TEST_ACCESS', 'TEST_SECRET', true);
check($gateway->accept_notification($tampered) === 400, 'signed amount that mismatches the order is rejected');
$wrong_request = $data; $wrong_request['requestId'] = 'wrong'; $wrong_request['signature'] = npe_payments_momo_signature($wrong_request, 'TEST_ACCESS', 'TEST_SECRET', true);
check($gateway->accept_notification($wrong_request) === 400, 'callback request ID must match persisted payment attempt');
$wrong_partner = $data; $wrong_partner['partnerCode'] = 'OTHER'; $wrong_partner['signature'] = npe_payments_momo_signature($wrong_partner, 'TEST_ACCESS', 'TEST_SECRET', true);
check($gateway->accept_notification($wrong_partner) === 400, 'callback partner identity is checked');
$failure = $data; $failure['resultCode'] = 1006; $failure['signature'] = npe_payments_momo_signature($failure, 'TEST_ACCESS', 'TEST_SECRET', true);
check($gateway->accept_notification($failure) === 204 && $orders[42]->completed === 0, 'authenticated unsuccessful callback never marks payment complete');
$expired = $orders[42]->meta['_npe_momo_attempt'];
$expired['created'] = time() - 901;
$orders[42]->meta['_npe_momo_attempt'] = $expired;
$gateway->process_payment(42);
check($orders[42]->get_meta('_npe_momo_attempt')['order_id'] !== $data['orderId'] && npe_payments_momo_attempt($orders[42], $data['orderId'])['request_id'] === $data['requestId'], 'retries preserve older attempts for delayed authenticated notifications');
check($gateway->accept_notification($data) === 204 && $orders[42]->completed === 0 && $orders[42]->status === 'on-hold', 'successful sandbox callback stays on-hold without marking real payment complete');
check($gateway->accept_notification($data) === 204 && $orders[42]->completed === 0, 'sandbox IPN replay is idempotent');
check(apply_filters('woocommerce_can_reduce_order_stock', true, $orders[42]) === false, 'sandbox results never reduce real stock');
$orders[43] = new PaymentTestOrder(43); $gateway->process_payment(43);
$duplicate = $data; $duplicate['orderId'] = $orders[43]->get_meta('_npe_momo_attempt')['order_id']; $duplicate['requestId'] = $orders[43]->get_meta('_npe_momo_attempt')['request_id']; $duplicate['orderInfo'] = 'Thanh toan don hang NEM 43'; $duplicate['signature'] = npe_payments_momo_signature($duplicate, 'TEST_ACCESS', 'TEST_SECRET', true);
check($gateway->accept_notification($duplicate) === 409 && $orders[43]->completed === 0, 'a transaction ID cannot pay another order');
$gateway_settings['momo']['testmode'] = 'no';
$production = new NPE_Gateway_Momo();
$http_mock = function ($url, $args) {
    check($url === 'https://payment.momo.vn/v2/gateway/api/create', 'production requires explicit environment selection');
    $payload = json_decode($args['body'], true);
    return ['code'=>200, 'body'=>json_encode(['partnerCode'=>'TEST_PARTNER', 'orderId'=>$payload['orderId'], 'requestId'=>$payload['requestId'], 'amount'=>$payload['amount'], 'resultCode'=>0, 'payUrl'=>'https://payment.momo.vn/v2/gateway/pay?t=mock'])];
};
$orders[44] = new PaymentTestOrder(44);
$production->process_payment(44);
$paid = $data;
$paid['orderId'] = $orders[44]->get_meta('_npe_momo_attempt')['order_id'];
$paid['requestId'] = $orders[44]->get_meta('_npe_momo_attempt')['request_id'];
$paid['orderInfo'] = 'Thanh toan don hang NEM 44';
$paid['transId'] = 87654321;
$paid['signature'] = npe_payments_momo_signature($paid, 'TEST_ACCESS', 'TEST_SECRET', true);
check($production->accept_notification($paid) === 204 && $orders[44]->completed === 1, 'only verified production IPN completes real payment');
check($production->accept_notification($paid) === 204 && $orders[44]->completed === 1, 'production IPN replay does not complete twice');
check(!npe_payments_hosted_url('https://test-payment.momo.vn.attacker.example/pay', ['test-payment.momo.vn']), 'provider suffix lookalike host rejected');
check(!npe_payments_hosted_url('https://user:pass@test-payment.momo.vn/pay', ['test-payment.momo.vn']), 'credential-bearing redirect URL rejected');
check(!npe_payments_hosted_url('http://test-payment.momo.vn/pay', ['test-payment.momo.vn']), 'non-TLS redirect rejected');
check(npe_payments_amount('100000.0000') === '100000' && npe_payments_amount('100.1') === false && npe_payments_amount('-1') === false, 'amount comparison uses exact whole VND');
$order_raw = '{"order_no":"NEM_42_TEST","amount":"100000","payment_url":"https:\/\/newsandbox.payoo.com.vn\/v2\/paynow\/detail?token=test"}';
$payoo_raw = '{"result":"success","checksum":"' . npe_payments_payoo_checksum($order_raw, 'TEST_PAYOO_KEY') . '","order":' . $order_raw . '}';
$verified = npe_payments_payoo_verified_response($payoo_raw, 'TEST_PAYOO_KEY');
check($verified && $verified['amount'] === '100000', 'Payoo checksum verifies exact provider JSON bytes');
check(npe_payments_payoo_verified_response(str_replace('100000', '999999', $payoo_raw), 'TEST_PAYOO_KEY') === false, 'Payoo tampered amount rejected');
check(npe_payments_payoo_verified_response($payoo_raw, 'WRONG_KEY') === false, 'Payoo wrong checksum secret rejected');
$gateway_settings['payoo'] = ['enabled' => 'yes', 'testmode' => 'yes', 'merchant_username' => 'TEST_SHOP', 'shop_id' => '123', 'shop_title' => 'Test & Shop', 'shop_domain' => 'https://shop.example', 'checksum_key' => 'TEST_PAYOO_KEY'];
$payoo = new NPE_Gateway_Payoo();
check(!$payoo->is_available(), 'Payoo fails closed without a merchant-approved IPN adapter');
$orders[50] = new PaymentTestOrder(50, 'payoo');
$before_http = $http_count;
check($payoo->process_payment(50)['result'] === 'failure' && $http_count === $before_http, 'Payoo cannot start a payment with an unsupported callback contract');
$xml = $payoo->build_order_xml($orders[50], 'NEM_50_TEST');
check(str_contains($xml, 'Test &amp; Shop') && str_contains($xml, '<order_cash_amount>100000</order_cash_amount>'), 'Payoo XML escapes merchant data and sends whole VND');
$gateway_settings['payoo']['local_demo'] = 'yes';
$payoo_demo = new NPE_Gateway_Payoo();
check($payoo_demo->is_available() && $payoo_demo->is_demo(), 'explicit local simulator is available without merchant credentials');
$orders[60] = new PaymentTestOrder(60, 'payoo');
$demo_before_http = $http_count;
$demo_result = $payoo_demo->process_payment(60);
check($demo_result['result'] === 'success' && $http_count === $demo_before_http && $orders[60]->completed === 0, 'local simulator creates no provider call or paid order');
parse_str(parse_url($demo_result['redirect'], PHP_URL_QUERY), $demo_request);
check(npe_payments_valid_demo_request($orders[60], $demo_request), 'simulator URL has a valid order-key-bound signature');
$demo_tampered = $demo_request; $demo_tampered['key'] = 'wrong';
check(!npe_payments_valid_demo_request($orders[60], $demo_tampered), 'simulator rejects the wrong order key');
$demo_tampered = $demo_request; $demo_tampered['signature'] = str_repeat('0', 64);
check(!npe_payments_valid_demo_request($orders[60], $demo_tampered), 'simulator rejects a forged signature');
check(npe_payments_apply_demo_result($orders[60], 'success') && $orders[60]->status === 'on-hold' && $orders[60]->completed === 0, 'simulated success stays on-hold and never calls payment_complete');
check(npe_payments_apply_demo_result($orders[60], 'success') && !npe_payments_apply_demo_result($orders[60], 'failure'), 'simulated outcomes are idempotent and cannot change after submission');
check(apply_filters('woocommerce_can_reduce_order_stock', true, $orders[60]) === false, 'simulated orders never reduce real stock');
$orders[61] = new PaymentTestOrder(61, 'payoo'); $payoo_demo->process_payment(61);
check(npe_payments_apply_demo_result($orders[61], 'failure') && $orders[61]->status === 'failed' && $orders[61]->completed === 0, 'simulated failure is recorded');
$orders[62] = new PaymentTestOrder(62, 'payoo'); $payoo_demo->process_payment(62);
check(npe_payments_apply_demo_result($orders[62], 'cancel') && $orders[62]->status === 'cancelled' && $orders[62]->completed === 0, 'simulated cancellation is recorded');
$local_site_url = 'https://shop.example/';
check(!$payoo_demo->is_demo() && !$payoo_demo->is_available() && !npe_payments_valid_demo_request($orders[60], $demo_request), 'simulation is denied on public website domains');
$local_site_url = 'http://wordpress.local/CK_NHOMF_CMS/';
$lock = npe_payments_lock('test-contention');
check($lock && npe_payments_lock('test-contention') === false, 'parallel callbacks are serialized');
npe_payments_unlock($lock);
check((bool) npe_payments_lock('test-contention'), 'lock released after processing');
echo "PASS: {$checks} payment protocol checks; HTTP mocked; no database or payment provider contacted.\n";
