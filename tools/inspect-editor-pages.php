<?php
if (PHP_SAPI !== 'cli') exit;
define('DOING_AJAX',true);
require dirname(__DIR__).'/wp-includes/plugin.php';
add_filter('option_active_plugins',function(){return ['woocommerce/woocommerce.php'];});
add_filter('pre_http_request',function(){return new WP_Error('offline','External HTTP disabled.');});
require dirname(__DIR__).'/wp-load.php';
echo json_encode(['home'=>home_url('/'),'dom'=>class_exists('DOMDocument'),'shop'=>wc_get_page_id('shop'),'cart'=>wc_get_page_id('cart'),'pages'=>array_map(function($p){return ['id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name];},get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>40]))],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
