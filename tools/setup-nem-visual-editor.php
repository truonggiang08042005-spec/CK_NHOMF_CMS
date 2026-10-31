<?php
/** Run once (idempotent): php tools/setup-nem-visual-editor.php. Never served over HTTP. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('DOING_AJAX', true);
require dirname(__DIR__) . '/wp-includes/plugin.php';
// Marketing plugins may terminate a raw CLI bootstrap. This affects this process only.
add_filter('option_active_plugins', function () { return ['woocommerce/woocommerce.php']; });
add_filter('pre_http_request', function () { return new WP_Error('npe_setup_offline', 'External requests disabled for local setup.'); });
add_filter('pre_wp_mail', '__return_true');
require dirname(__DIR__) . '/wp-load.php';
if (!function_exists('npe_visual_pages') || !function_exists('wc_get_products')) throw new RuntimeException('NEM and WooCommerce are required.');
$home = (int) get_option('page_on_front');
$checkout = (int) get_option('woocommerce_checkout_page_id');
if (get_post_type($home) !== 'page' || get_post_type($checkout) !== 'page') throw new RuntimeException('Existing home and checkout pages must be configured first.');
if (!get_option('npe_visual_import_backup')) {
    $pages = [];
    foreach ([$home,$checkout] as $id) $pages[$id] = get_post($id, ARRAY_A);
    add_option('npe_visual_import_backup', ['at'=>current_time('mysql'),'pages'=>$pages,'home'=>get_option('nem_home_content'),'builder'=>get_option('npe_page_builder')], '', false);
}
$detail = (int) get_option('npe_product_layout_page');
if (!$detail || get_post_type($detail) !== 'page' || get_post_status($detail) === 'trash') {
    $detail = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Chi tiết sản phẩm','post_name'=>'nem-chi-tiet-san-pham','post_content'=>'<p>Bố cục chi tiết sản phẩm NEM. Bấm Edit Page để sửa trực quan; bố cục áp dụng cho tất cả sản phẩm.</p>'], true);
    if (is_wp_error($detail)) throw new RuntimeException($detail->get_error_message());
    update_option('npe_product_layout_page', $detail, false);
}
foreach (npe_visual_pages() as $type=>$id) update_post_meta($id, '_npe_visual_layout', $type);
if (get_the_title($checkout) === 'Checkout') wp_update_post(['ID'=>$checkout,'post_title'=>'Thanh toán']);
echo "Imported visual pages (existing content and commerce settings preserved):\n";
foreach (npe_visual_pages() as $type=>$id) echo $type . ' #' . $id . ' ' . npe_visual_url($type) . PHP_EOL;
