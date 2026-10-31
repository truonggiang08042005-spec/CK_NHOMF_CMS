<?php
/** Local HTTP integration. Changes are restored in finally; no checkout is submitted. */
if (PHP_SAPI !== 'cli') exit;
define('DOING_AJAX', true);
require dirname(__DIR__) . '/wp-includes/plugin.php';
add_filter('option_active_plugins', function () { return ['woocommerce/woocommerce.php']; });
add_filter('pre_wp_mail', '__return_true');
require dirname(__DIR__) . '/wp-load.php';
if (!npe_payments_is_local_site()) throw new RuntimeException('Local sites only.');
$admin = get_users(['role'=>'administrator','number'=>1])[0] ?? null;
if (!$admin) throw new RuntimeException('Existing administrator required.');
wp_set_current_user($admin->ID);
$expiration = time() + 600;
$manager = WP_Session_Tokens::get_instance($admin->ID);
$token = $manager->create($expiration);
$cookie = AUTH_COOKIE . '=' . wp_generate_auth_cookie($admin->ID,$expiration,'auth',$token) . '; ' . LOGGED_IN_COOKIE . '=' . wp_generate_auth_cookie($admin->ID,$expiration,'logged_in',$token);
$_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($admin->ID,$expiration,'logged_in',$token);
$saved = ['npe_page_builder'=>get_option('npe_page_builder'),'nem_home_content'=>get_option('nem_home_content'),'npe_visual_styles'=>get_option('npe_visual_styles')];
$history = [];
foreach (npe_visual_pages() as $id) $history[$id] = get_post_meta($id,'_npe_visual_history',true);
$checks = 0;
function nve_check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException('FAIL: '.$message); $checks++; }
function nve_request($url, $body = null, $logged_in = true, $redirects = 3) {
    global $cookie;
    $response = wp_remote_request($url,['method'=>$body === null ? 'GET' : 'POST','body'=>$body,'headers'=>$logged_in ? ['Cookie'=>$cookie] : [],'timeout'=>25,'redirection'=>$redirects]);
    if (is_wp_error($response)) throw new RuntimeException($response->get_error_message());
    return ['status'=>wp_remote_retrieve_response_code($response),'body'=>wp_remote_retrieve_body($response),'headers'=>wp_remote_retrieve_headers($response)];
}
try {
    nve_check(npe_visual_pages()['home'] === 90 && npe_visual_pages()['checkout'] === 9, 'existing commerce pages retained');
    nve_check(get_post_type(npe_visual_pages()['product']) === 'page', 'product layout is present in All Pages');
    nve_check(npe_visual_page_type(0) === '' && npe_visual_page_type(78) === '', 'unrelated pages keep their editor');
    $style = npe_visual_styles('home',['catalog_names'=>['fontSize'=>999,'color'=>'red;}body{display:none','textAlign'=>'center'],'unknown'=>['fontSize'=>12]]);
    nve_check($style['catalog_names']['fontSize'] === 120 && !isset($style['catalog_names']['color']) && !isset($style['unknown']), 'typography only accepts bounded known properties');
    $features = array_keys((array) (get_option('nem_home_content')['features'] ?? []));
    $reversed_features = array_reverse($features);
    nve_check(npe_visual_feature_order(array_merge($reversed_features, [99999, [], 'bad'], $reversed_features)) === $reversed_features, 'feature drag order rejects unknown items and duplicate indexes');
    $all = nve_request(admin_url('edit.php?post_type=page'));
    nve_check($all['status'] === 200 && str_contains($all['body'],'Edit Page · Sửa trực quan'), 'All Pages exposes the visual editor');
    $product = npe_visual_product();
    foreach (npe_visual_pages() as $type=>$id) {
        nve_check(str_contains(get_edit_post_link($id,'raw'),'page=npe-visual-editor'), $type.' Edit Page routes to the right editor');
        $editor = nve_request(npe_visual_url($type));
        nve_check($editor['status'] === 200 && str_contains($editor['body'],'id="nve-preview"') && str_contains($editor['body'],'visual-editor.js') && str_contains($editor['body'],'visual-editor.css'), $type.' editor serves the canvas and assets');
        preg_match('/<iframe[^>]+src="([^"]+)"/', $editor['body'], $m);
        nve_check(!empty($m[1]), $type.' editor contains a preview URL');
        $preview = nve_request(html_entity_decode($m[1],ENT_QUOTES,'UTF-8'));
        nve_check($preview['status'] === 200 && str_contains($preview['body'],'data-npe-section='), $type.' live template renders selectable sections');
        nve_check(!preg_match('~<script[^>]+src=[^>]+(?:pages\.js|checkout\.js|wc-checkout)~',$preview['body']), $type.' preview does not execute commerce scripts');
        $public_url = $type === 'home' ? home_url('/') : ($type === 'checkout' ? wc_get_checkout_url() : $product->get_permalink());
        $public = nve_request($public_url);
        nve_check(str_contains($public['body'],'Edit Page · Sửa trực quan'), $type.' frontend admin bar has Edit Page');
        if ($type === 'checkout') {
            nve_check(str_contains($preview['body'],'Đơn hàng minh họa') && str_contains($preview['body'],'id="place_order"') && str_contains($preview['body'],'name="billing_phone"'), 'empty-cart checkout preview still shows a complete layout');
            nve_check(!str_contains($preview['body'],'name="woocommerce-process-checkout-nonce"'), 'preview cannot submit a checkout nonce');
            nve_check(!str_contains($public['body'],'Đơn hàng minh họa'), 'sample order is absent from public checkout');
        }
        echo 'Verified ' . $type . " editor and preview.\n";
    }
    $id = npe_visual_pages()['home'];
    $nonce = wp_create_nonce('npe_visual_save');
    $request = ['action'=>'npe_visual_save','nonce'=>$nonce,'post'=>$id,'data'=>'{}'];
    $invalid = $request; $invalid['nonce'] = 'invalid';
    nve_check(nve_request(admin_url('admin-ajax.php'),$invalid)['status'] === 403, 'save rejects an invalid nonce');
    nve_check(nve_request(admin_url('admin-ajax.php'),$request,false)['status'] >= 400, 'guest cannot save');
    $invalid = $request; $invalid['post'] = 78;
    nve_check(nve_request(admin_url('admin-ajax.php'),$invalid)['status'] === 403, 'save rejects an unrelated page');
    $state = npe_page_settings('home');
    $fields = [];
    foreach (npe_builder_schema('home') as $key=>$field) { $v = npe_setting('home',$key,$field['default']??''); $fields[$key] = is_array($v) ? implode(',',$v) : $v; }
    $fields['blog_title'] = 'NVE temporary integration heading';
    $request['data'] = wp_json_encode(['fields'=>$fields,'order'=>array_reverse(npe_section_order('home',true)),'hidden'=>$state['hidden'],'columns'=>[],'featureOrder'=>$reversed_features,'styles'=>['blog_title'=>['fontSize'=>37,'color'=>'#123456']]]);
    $result = nve_request(admin_url('admin-ajax.php'),$request);
    nve_check($result['status'] === 200 && !empty(json_decode($result['body'],true)['success']), 'full homepage payload saves through the authenticated AJAX endpoint');
    $public = nve_request(home_url('/'),null,false);
    nve_check(str_contains($public['body'],'NVE temporary integration heading') && str_contains($public['body'],'font-size:37px!important'), 'saved content and typography appear for visitors');
    nve_check(strpos($public['body'],'data-npe-section="newsletter"') < strpos($public['body'],'data-npe-section="hero"'), 'saved drag order appears in the public page');
    if (count($reversed_features) >= 2) nve_check(strpos($public['body'],'data-npe-feature="'.$reversed_features[0].'"') < strpos($public['body'],'data-npe-feature="'.$reversed_features[1].'"'), 'dragging homepage image tiles persists their order with their content');
    $product_state = npe_page_settings('product');
    $request['post'] = npe_visual_pages()['product'];
    foreach (['right','left'] as $side) {
        $request['data'] = wp_json_encode(['fields'=>['gallery_position'=>$side],'order'=>npe_section_order('product',true),'hidden'=>$product_state['hidden'],'columns'=>$product_state['columns'],'styles'=>($saved['npe_visual_styles']['product'] ?? [])]);
        $result = nve_request(admin_url('admin-ajax.php'),$request);
        nve_check($result['status'] === 200 && !empty(json_decode($result['body'],true)['success']), 'product column drag saves gallery on '.$side);
        $public_product = nve_request($product->get_permalink(),null,false);
        nve_check(str_contains($public_product['body'],'class="npe-wrap npe-detail npe-gallery-right"') === ($side === 'right'), 'public product reflects gallery on '.$side);
    }
    $request['data'] = wp_json_encode(['fields'=>['gallery_position'=>'outside']]);
    nve_check(nve_request(admin_url('admin-ajax.php'),$request)['status'] === 400, 'unknown gallery position is rejected');
    $request['post'] = npe_visual_pages()['checkout'];
    $request['data'] = wp_json_encode(['fields'=>['heading'=>'NVE temporary checkout heading'],'order'=>['summary','payment','contact'],'hidden'=>['contact','address','payment','summary'],'columns'=>['payment'=>'sidebar'],'styles'=>['heading'=>['fontSize'=>35]]]);
    $result = nve_request(admin_url('admin-ajax.php'),$request);
    nve_check($result['status'] === 200 && !empty(json_decode($result['body'],true)['success']), 'checkout layout saves');
    wp_cache_delete('alloptions','options');
    wp_cache_delete('npe_page_builder','options');
    nve_check(array_diff(['contact','address','payment','summary'],npe_section_order('checkout')) === [], 'required checkout blocks survive an attempted hide');
    nve_check(npe_section_column('checkout','payment') === 'sidebar', 'saved checkout column is applied');
    echo "PASS: {$checks} visual editor HTTP, routing, persistence, preview and permission checks.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    throw $error;
} finally {
    wp_cache_delete('alloptions','options');
    foreach ($saved as $key=>$value) { wp_cache_delete($key,'options'); if ($value === false) delete_option($key); else update_option($key,$value,false); }
    foreach ($history as $id=>$value) { if ($value === '') delete_post_meta($id,'_npe_visual_history'); else update_post_meta($id,'_npe_visual_history',$value); }
    $manager->destroy($token);
    echo "Restored original content, layout, typography and history; temporary authentication session removed.\n";
}
