<?php
/** Idempotent import of the shop, cart and search layout pages. CLI only. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('DOING_AJAX',true);
require dirname(__DIR__).'/wp-includes/plugin.php';
add_filter('option_active_plugins',function(){return ['woocommerce/woocommerce.php'];});
add_filter('pre_http_request',function(){return new WP_Error('npe_setup_offline','External HTTP disabled during local setup.');});
add_filter('pre_wp_mail','__return_true');
require dirname(__DIR__).'/wp-load.php';
$shop=wc_get_page_id('shop');$cart=wc_get_page_id('cart');
if(get_post_type($shop)!=='page' || get_post_type($cart)!=='page')throw new RuntimeException('Existing WooCommerce Shop and Cart pages are required.');
add_option('npe_catalog_editor_import_backup',['at'=>current_time('mysql'),'pages'=>[$shop=>get_post($shop,ARRAY_A),$cart=>get_post($cart,ARRAY_A)],'builder'=>get_option('npe_page_builder',[]),'styles'=>get_option('npe_visual_styles',[])],'',false);
$search=(int)get_option('npe_search_layout_page');
if(!$search || get_post_type($search)!=='page' || get_post_status($search)==='trash'){
    $search=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Tìm kiếm','post_name'=>'nem-tim-kiem','post_content'=>'<p>Bố cục trang tìm kiếm sản phẩm. Bấm Edit Page để chỉnh sửa trực quan.</p>'],true);
    if(is_wp_error($search))throw new RuntimeException($search->get_error_message());
    update_option('npe_search_layout_page',$search,false);
}
if(get_the_title($shop)==='Shop')wp_update_post(['ID'=>$shop,'post_title'=>'Sản phẩm']);
if(get_the_title($cart)==='Cart')wp_update_post(['ID'=>$cart,'post_title'=>'Giỏ hàng']);
foreach(['shop'=>$shop,'cart'=>$cart,'search'=>$search] as $type=>$id){
    update_post_meta($id,'_npe_visual_layout',$type);
    echo $type.' #'.$id.' '.npe_visual_url($type).PHP_EOL;
}
echo "Existing page content, cart form, product queries and WooCommerce settings preserved.\n";
