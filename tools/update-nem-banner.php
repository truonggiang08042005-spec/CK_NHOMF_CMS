<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$file=WP_CONTENT_DIR.'/uploads/nem-home/hero-sale70.png';
if (!is_file($file)) exit("Missing hero-sale70.png\n");
$ids=get_posts(['post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_nem_asset','meta_value'=>'hero-sale70','fields'=>'ids','posts_per_page'=>1]);
$id=$ids ? $ids[0] : wp_insert_attachment(['post_title'=>'NEM - Giảm 70% hàng ngàn sản phẩm hè','post_mime_type'=>'image/png','post_status'=>'inherit'], $file, 0, true);
if(is_wp_error($id)) exit($id->get_error_message());
if(!$ids) wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$file));
update_post_meta($id,'_nem_asset','hero-sale70');
update_post_meta($id,'_wp_attachment_image_alt','NEM giảm 70% hàng ngàn sản phẩm hè, 25.9 - 4.10');
$c=get_option('nem_home_content');
if(!is_array($c)) exit("Missing home content\n");
$c['images']['hero-sale70']=$id;
$c['images']['hero']=$id;
$c['hero_campaign']=['title'=>'GIẢM 70%','subtitle'=>'HÀNG NGÀN SẢN PHẨM HÈ','note'=>'KHÔNG ÁP DỤNG CHO MỘT SỐ SẢN PHẨM','dates'=>'25.9 - 4.10','source'=>'Recreated from user reference with built-in image_gen'];
update_option('nem_home_content',$c);
echo "Updated database hero attachment: $id\n";
if((int)get_option('nem_home_content')['images']['hero']!==(int)$id) exit(1);
