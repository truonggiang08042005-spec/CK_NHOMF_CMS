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
function is_email($value) { return filter_var($value,FILTER_VALIDATE_EMAIL) !== false; }
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
    public $billing_email = 'customer@example.test';
    public function get_billing_email() { return $this->billing_email; }
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
$local_site_url = 'https://shop.example/';
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
$local_site_url = 'http://wordpress.local/CK_NHOMF_CMS/';
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
$channel_credentials = ['enabled'=>'yes','testmode'=>'yes','channel_approved'=>'yes','partner_code'=>'TEST_PARTNER','access_key'=>'TEST_ACCESS','secret_key'=>'TEST_SECRET'];
$gateway_settings['momo_card'] = $channel_credentials;
$gateway_settings['momo_atm'] = $channel_credentials;
$credit = new NPE_Gateway_Momo_Card();
$atm = new NPE_Gateway_Momo_Atm();
check($credit->is_available() && $atm->is_available(),'configured and merchant-approved card channels are available');
$gateway_settings['momo_card']['channel_approved'] = 'no';
check(!(new NPE_Gateway_Momo_Card())->is_available(),'card channel requires explicit provider entitlement');
$gateway_settings['momo_card']['channel_approved'] = 'yes';
$channel_payload = null;
$http_mock = function ($url,$args) use (&$channel_payload) {
    $channel_payload = json_decode($args['body'],true);
    check(in_array($channel_payload['requestType'],['payWithATM','payWithCC'],true),'card channels send their own documented request type');
    check(str_contains($channel_payload['ipnUrl'],'npe_'.($channel_payload['requestType']==='payWithCC'?'momo_card':'momo_atm').'_ipn'),'each channel has its own notification route');
    check(!isset($channel_payload['cardNumber'],$channel_payload['cvv']),'card data is never collected or forwarded by the store');
    $host = str_contains($url,'test-payment') ? 'test-payment.momo.vn' : 'payment.momo.vn';
    return ['code'=>200,'body'=>json_encode(['partnerCode'=>'TEST_PARTNER','orderId'=>$channel_payload['orderId'],'requestId'=>$channel_payload['requestId'],'amount'=>$channel_payload['amount'],'resultCode'=>0,'payUrl'=>'https://'.$host.'/v2/gateway/pay?t=fixture'])];
};
$orders[80] = new PaymentTestOrder(80,'momo_card');
check($credit->process_payment(80)['result']==='success' && $channel_payload['userInfo']['email']==='customer@example.test','international card redirects to provider with required email');
$attempt = $orders[80]->get_meta('_npe_momo_attempt');
$credit_data = ['partnerCode'=>'TEST_PARTNER','orderId'=>$attempt['order_id'],'requestId'=>$attempt['request_id'],'amount'=>100000,'orderInfo'=>'Thanh toan don hang NEM 80','orderType'=>'momo_wallet','transId'=>88880001,'resultCode'=>0,'message'=>'Successful.','payType'=>'credit','responseTime'=>1793318400000,'extraData'=>'','callbackToken'=>'','partnerClientId'=>''];
$credit_raw = 'accessKey=TEST_ACCESS&amount=100000&callbackToken=&extraData=&message=Successful.&orderId='.$attempt['order_id'].'&orderInfo=Thanh toan don hang NEM 80&orderType=momo_wallet&partnerClientId=&partnerCode=TEST_PARTNER&payType=credit&requestId='.$attempt['request_id'].'&responseTime=1793318400000&resultCode=0&transId=88880001';
$credit_data['signature'] = hash_hmac('sha256',$credit_raw,'TEST_SECRET');
check(npe_payments_verify_momo($credit_data,'TEST_ACCESS','TEST_SECRET','payWithCC'),'credit IPN verifies against an independent canonical signature');
$tampered_credit = $credit_data; $tampered_credit['callbackToken']='tampered';
check($credit->accept_notification($tampered_credit)===400,'credit token fields are covered by the signature');
check($credit->accept_notification($credit_data)===204 && $orders[80]->completed===0 && $orders[80]->status==='on-hold','signed sandbox card result never records money received');
check($atm->accept_notification($credit_data)===400,'card notification cannot be delivered to ATM channel');
$ack = npe_payments_momo_card_ack($credit_data,'TEST_ACCESS','TEST_SECRET');
$ack_raw = 'accessKey=TEST_ACCESS&extraData=&message=Success&orderId='.$attempt['order_id'].'&partnerCode=TEST_PARTNER&requestId='.$attempt['request_id'].'&responseTime='.$ack['responseTime'].'&resultCode=0';
check(hash_equals(hash_hmac('sha256',$ack_raw,'TEST_SECRET'),$ack['signature']),'credit notification acknowledgement has the documented signature');
$orders[81] = new PaymentTestOrder(81,'momo_atm'); $orders[81]->total='9999'; $calls=$http_count;
check($atm->process_payment(81)['result']==='failure' && $http_count===$calls,'ATM rejects amounts below 10000 before provider call');
$orders[81]->total='10000';
check($atm->process_payment(81)['result']==='success' && $channel_payload['requestType']==='payWithATM','ATM starts using the correct channel');
$atm_attempt=$orders[81]->get_meta('_npe_momo_attempt');
$atm_data=$data; $atm_data['orderId']=$atm_attempt['order_id']; $atm_data['requestId']=$atm_attempt['request_id']; $atm_data['orderInfo']='Thanh toan don hang NEM 81'; $atm_data['amount']=10000; $atm_data['payType']='napas'; $atm_data['transId']=88880002;
$atm_data['signature']=npe_payments_momo_signature($atm_data,'TEST_ACCESS','TEST_SECRET',true);
check($atm->accept_notification($atm_data)===204 && $orders[81]->completed===0,'ATM sandbox callback verifies and remains unpaid');
$orders[82]=new PaymentTestOrder(82,'momo_card'); $orders[82]->billing_email=''; $calls=$http_count;
check($credit->process_payment(82)['result']==='failure' && $http_count===$calls,'credit email is validated before calling provider');
$orders[82]->billing_email='customer@example.test'; $orders[82]->total='10000001';
check($credit->process_payment(82)['result']==='failure' && $http_count===$calls,'credit enforces documented maximum amount');
$gateway_settings['momo_card']['testmode']='no'; $live_credit=new NPE_Gateway_Momo_Card();
$local_site_url='https://shop.example/';
$orders[83]=new PaymentTestOrder(83,'momo_card'); $live_credit->process_payment(83);
$live_attempt=$orders[83]->get_meta('_npe_momo_attempt');
$live_data=$credit_data; $live_data['orderId']=$live_attempt['order_id']; $live_data['requestId']=$live_attempt['request_id']; $live_data['orderInfo']='Thanh toan don hang NEM 83'; $live_data['transId']=88880003;
$live_data['signature']=npe_payments_momo_signature($live_data,'TEST_ACCESS','TEST_SECRET',true,'payWithCC');
check($live_credit->accept_notification($live_data)===204 && $orders[83]->completed===1,'only authenticated production card IPN completes payment');
check($live_credit->accept_notification($live_data)===204 && $orders[83]->completed===1,'production card callback is idempotent');
$local_site_url='http://wordpress.local/CK_NHOMF_CMS/';
check(!$live_credit->is_available(),'production is unavailable on localhost');
$gateway_settings['momo_atm']['local_demo']='yes'; $demo_atm=new NPE_Gateway_Momo_Atm(); $orders[84]=new PaymentTestOrder(84,'momo_atm'); $calls=$http_count;
$demo=$demo_atm->process_payment(84); parse_str(parse_url($demo['redirect'],PHP_URL_QUERY),$demo_request);
check(npe_payments_valid_demo_request($orders[84],$demo_request) && $http_count===$calls,'new ATM channel supports explicit offline simulation');
$options['woocommerce_momo_settings']=array_merge($channel_credentials,['testmode'=>'yes']);
$gateway_settings['momo_card']=['enabled'=>'yes','testmode'=>'yes','channel_approved'=>'yes'];
check((new NPE_Gateway_Momo_Card())->is_configured(),'card channel can share wallet credentials in the same environment');
$gateway_settings['momo_card']['testmode']='no';
check(!(new NPE_Gateway_Momo_Card())->is_configured(),'sandbox wallet credentials cannot be inherited in production');
echo "PASS: {$checks} payment protocol checks; HTTP mocked; no database or payment provider contacted.\n";
