<?php
/** Prepare the explicit local simulation profile. Does not enable real payment processing. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('DOING_AJAX', true);
require dirname(__DIR__).'/wp-includes/plugin.php';
add_filter('option_active_plugins',function () { return ['woocommerce/woocommerce.php']; });
add_filter('pre_http_request',function () { return new WP_Error('npe_setup_offline','External HTTP disabled during setup.'); });
add_filter('pre_wp_mail','__return_true');
require dirname(__DIR__).'/wp-load.php';
if (!npe_payments_is_local_site()) throw new RuntimeException('Local setup only.');
$backup = [];
foreach (['momo','momo_atm','momo_card','payoo'] as $id) $backup[$id] = get_option('woocommerce_'.$id.'_settings',false);
add_option('npe_payment_methods_setup_backup',$backup,'',false);
foreach (['momo','momo_atm','momo_card'] as $id) {
    $settings = (array) get_option('woocommerce_'.$id.'_settings',[]);
    if (!empty($settings['partner_code']) || !empty($settings['access_key']) || !empty($settings['secret_key'])) { echo $id.": existing merchant configuration preserved\n"; continue; }
    update_option('woocommerce_'.$id.'_settings',array_merge($settings,['enabled'=>'yes','testmode'=>'yes','local_demo'=>'yes']),false);
    echo $id.": explicit local simulation enabled; real payment remains unconfigured\n";
}
$payoo = (array) get_option('woocommerce_payoo_settings',[]);
if (($payoo['local_demo'] ?? '') === 'yes' && empty($payoo['merchant_username']) && empty($payoo['checksum_key'])) {
    $payoo['enabled'] = 'no'; update_option('woocommerce_payoo_settings',$payoo,false);
    echo "payoo: redundant unconfigured simulation hidden; settings preserved\n";
}
echo 'Bank account: '.(npe_checkout_has_bank_account() ? 'configured' : 'awaiting real recipient information').PHP_EOL;
echo 'Setup: '.admin_url('admin.php?page=npe-payments').PHP_EOL;
