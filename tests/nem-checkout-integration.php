<?php
/** Local integration tests. Only disposable, clearly named test records are created. */
if (PHP_SAPI !== 'cli') exit;
define('DOING_AJAX', true);
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
require dirname(__DIR__).'/wp-includes/plugin.php';
add_filter('pre_http_request', function () { return new WP_Error('npe_test_offline', 'External HTTP is disabled during this test.'); }, 1);
add_filter('pre_wp_mail', '__return_true', 1);
require dirname(__DIR__).'/wp-load.php';
if (!npe_payments_is_local_site()) throw new RuntimeException('Run this test on a local site only.');
if (!WC()->session) WC()->initialize_session();
if (!WC()->cart) wc_load_cart();
$checks = 0;
$orders = [];
$test_product = null;
function npe_integration_assert($value, $message) {
    global $checks;
    if (!$value) throw new RuntimeException('FAIL: '.$message);
    $checks++;
}
$bank_fixture = function () { return [['account_name'=>'NPE DISPOSABLE TEST', 'account_number'=>'TEST-ONLY-0000', 'bank_name'=>'LOCAL TEST BANK', 'sort_code'=>'', 'iban'=>'', 'bic'=>'']]; };
try {
    $test_product = new WC_Product_Simple();
    $test_product->set_name('NPE disposable checkout integration test');
    $test_product->set_status('publish');
    $test_product->set_catalog_visibility('hidden');
    $test_product->set_regular_price('100000');
    $test_product->set_virtual(true);
    $test_product->set_manage_stock(true);
    $test_product->set_stock_quantity(10);
    $test_product->save();
    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($test_product->get_id(), 1);
    WC()->cart->calculate_totals();
    npe_integration_assert(WC()->cart->get_cart_contents_count() === 1, 'test cart contains the disposable product');
    add_filter('pre_option_woocommerce_bacs_accounts', '__return_empty_array');
    npe_integration_assert(!isset(WC()->payment_gateways()->get_available_payment_gateways()['bacs']), 'bank transfer without receiving account is unavailable');
    $html = npe_checkout_payments();
    npe_integration_assert(str_contains($html, 'name="npe_unavailable_payment"') && str_contains($html, 'Chưa có thông tin tài khoản nhận tiền'), 'checkout explains the missing receiving account');
    remove_filter('pre_option_woocommerce_bacs_accounts', '__return_empty_array');
    add_filter('pre_option_woocommerce_bacs_accounts', $bank_fixture);
    $gateways = WC()->payment_gateways()->payment_gateways();
    $gateways['bacs']->enabled = 'yes';
    $gateways['bacs']->account_details = $bank_fixture();
    $gateways['cod']->enabled = 'yes';
    $available = WC()->payment_gateways()->get_available_payment_gateways();
    npe_integration_assert(isset($available['bacs']), 'configured bank account enables bank transfer');
    $html = npe_checkout_payments();
    foreach (['momo','payoo','bacs','cod'] as $id) npe_integration_assert(str_contains($html, 'id="payment_method_'.$id.'"'), 'payment option rendered: '.$id);
    npe_integration_assert(str_contains(npe_checkout_totals(), '100,000'), 'summary uses WooCommerce cart totals');
    $_POST['post_data'] = 'npe_checkout=1';
    $fragments = apply_filters('woocommerce_update_order_review_fragments', []);
    npe_integration_assert(isset($fragments['.woocommerce-checkout-payment'], $fragments['#npe-order-totals'], $fragments['#npe-shipping-methods']), 'checkout AJAX updates all custom payment/shipping/total fragments');
    unset($_POST['post_data']);
    foreach (['bacs','cod','momo','payoo'] as $id) {
        $order = wc_create_order(['created_via'=>'npe-disposable-test', 'status'=>'pending']);
        $orders[] = $order;
        $order->set_currency('VND');
        $order->set_payment_method($id);
        $order->set_payment_method_title('NPE TEST '.$id);
        $order->add_product($test_product, 1);
        $order->calculate_totals();
        $order->save();
        if (in_array($id, ['momo','payoo'], true)) {
            $gateways[$id]->enabled = 'yes';
            $gateways[$id]->settings['local_demo'] = 'yes';
            $gateways[$id]->settings['testmode'] = 'yes';
            $result = $gateways[$id]->process_payment($order->get_id());
            npe_integration_assert($result['result'] === 'success', $id.' local simulator opens');
            parse_str((string)parse_url($result['redirect'], PHP_URL_QUERY), $request);
            $order = wc_get_order($order->get_id());
            npe_integration_assert(npe_payments_valid_demo_request($order, $request), $id.' simulator validates signed order URL');
            $forged = $request; $forged['key'] = 'invalid';
            npe_integration_assert(!npe_payments_valid_demo_request($order, $forged), $id.' simulator rejects wrong order key');
            npe_integration_assert(npe_payments_apply_demo_result($order, 'success'), $id.' simulated success accepted');
            $order = wc_get_order($order->get_id());
            npe_integration_assert($order->has_status('on-hold') && !$order->get_date_paid(), $id.' demo remains unpaid and on hold');
            npe_integration_assert(npe_payments_apply_demo_result($order, 'success') && !npe_payments_apply_demo_result($order, 'failure'), $id.' result is idempotent and immutable');
            npe_integration_assert(!apply_filters('woocommerce_can_reduce_order_stock', true, $order), $id.' demo cannot reduce actual stock');
        } else {
            $result = $gateways[$id]->process_payment($order->get_id());
            $order = wc_get_order($order->get_id());
            npe_integration_assert($result['result'] === 'success', $id.' order flow returns a receipt URL');
            npe_integration_assert(!$order->get_date_paid(), $id.' does not invent payment confirmation');
            if ($id === 'bacs') {
                npe_integration_assert($order->has_status('on-hold'), 'bank order waits for manual reconciliation');
                ob_start(); $gateways[$id]->thankyou_page($order->get_id()); $instructions = ob_get_clean();
                npe_integration_assert(str_contains($instructions, 'TEST-ONLY-0000'), 'bank instructions include configured receiving account');
            }
        }
    }
    echo "PASS: {$checks} WooCommerce integration checks; disposable records cleaned; external HTTP and email blocked.\n";
} finally {
    remove_filter('pre_option_woocommerce_bacs_accounts', $bank_fixture);
    remove_filter('pre_option_woocommerce_bacs_accounts', '__return_empty_array');
    WC()->cart->empty_cart();
    foreach ($orders as $order) { $fresh = wc_get_order($order->get_id()); if ($fresh) $fresh->delete(true); }
    if ($test_product) $test_product->delete(true);
}
