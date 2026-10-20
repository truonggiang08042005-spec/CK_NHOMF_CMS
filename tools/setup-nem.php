<?php
/** Run: php -d memory_limit=512M tools/setup-nem.php. Never exposed over HTTP. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/image.php';
if (!class_exists('WC_Product_Simple')) exit("WooCommerce required\n");
function nem_seed_post($slug,$type,$title,$body) {
    $existing=get_page_by_path($slug, OBJECT, $type);
    if ($existing) return $existing->ID;
    $id=wp_insert_post(['post_type'=>$type,'post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$body],true);
    if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
$images=[];
$asset_keys=array_keys(json_decode(ltrim(file_get_contents(__DIR__.'/assets.json'), "\xEF\xBB\xBF"),true));
$asset_keys=array_merge($asset_keys,['hero2','hero3','hero4','hero-sale70']);
foreach(glob(WP_CONTENT_DIR.'/uploads/nem-home/*') as $file) {
    $key=pathinfo($file,PATHINFO_FILENAME);
    if(!in_array($key,$asset_keys,true)) continue;
    $saved=get_posts(['post_type'=>'attachment','post_status'=>'inherit','meta_key'=>'_nem_asset','meta_value'=>$key,'fields'=>'ids','posts_per_page'=>1]);
    if($saved) { $images[$key]=$saved[0]; continue; }
    $type=wp_check_filetype($file);
    $id=wp_insert_attachment(['post_title'=>'NEM '.$key,'post_mime_type'=>$type['type'],'post_status'=>'inherit'], $file,0,true);
    if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    update_post_meta($id,'_nem_asset',$key); update_post_meta($id,'_wp_attachment_image_alt','NEM '. $key);
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$file)); $images[$key]=$id;
}
if(isset($images['hero-sale70'])) $images['hero']=$images['hero-sale70'];
$names=['ĐẦM SUÔNG PHỐI TÚI 2 BÊN D64282','ÁO LEN NGẮN TAY AL62372','QUẦN JEANS ỐNG SUÔNG Q67112','QUẦN JEANS XANH ĐẬM Q67102','ĐẦM SUÔNG CAM D64152','QUẦN SHORT ỐNG RỘNG Q01492','ÁO LEN CỔ BẺ AL62302','ĐẦM THIẾT KẾ D01572'];
$prices=[1399000,599000,799000,799000,1099000,699000,599000,1599000];
$sales=[null,null,null,null,329700,209700,299500,479700];
$groups=[];
foreach(['casual'=>'NEM CASUAL','plus'=>'NEM PLUS+'] as $key=>$name) {
    $term=term_exists('nem-'.$key,'product_cat'); if(!$term) $term=wp_insert_term($name,'product_cat',['slug'=>'nem-'.$key]);
    if(is_wp_error($term)) throw new RuntimeException($term->get_error_message());
    $groups[$key]=(int)$term['term_id'];
}
$ids=[];
foreach($names as $i=>$name) {
    $sku='NEM-'.['D64282','AL62372','Q67112','Q67102','D64152','Q01492','AL62302','D01572'][$i];
    $existing=wc_get_product_id_by_sku($sku);
    if($existing) { $ids[]=$existing; continue; }
    $p=new WC_Product_Simple(); $p->set_name($name); $p->set_sku($sku); $p->set_status('publish');
    $p->set_regular_price($prices[$i]); if($sales[$i]) $p->set_sale_price($sales[$i]);
    $key=$i<4?'casual':'plus'; $p->set_category_ids([$groups[$key]]); $p->set_image_id($images[$key.(($i%4)+1)]);
    $p->set_description('Thiết kế thuộc dòng '.$key.'. Phom dáng thanh lịch, dễ kết hợp cho công sở và dạo phố. Vui lòng liên hệ cửa hàng để được tư vấn kích cỡ và tình trạng sản phẩm.');
    // Sample products are visible but cannot accidentally be purchased as real inventory.
    $p->set_stock_status('outofstock'); $p->set_manage_stock(false);
    $p->update_meta_data('_nem_reference_product','yes'); $ids[]=$p->save();
}
$blog_titles=['NEM I 24 YEARS OF WOMEN','NEM CASUAL - FROM OFFICE TO EVERYWHERE','MẸ ĐÃ TỪNG LÀ MỘT CÔ GÁI - SỰ KIỆN TRI ÂN NGÀY CỦA MẸ'];
$bodies=['24 năm đồng hành cùng phụ nữ, NEM tôn vinh vẻ đẹp thanh lịch và phong cách riêng qua những thiết kế thời trang công sở. Cảm ơn những người phụ nữ đã cùng NEM viết nên hành trình này.','Từ văn phòng đến những cuộc hẹn sau giờ làm, NEM Casual mang đến những lựa chọn dễ phối: đầm suông, áo len ngắn tay và quần jeans. Khám phá các thiết kế trong bộ sưu tập NEM Casual.','Mỗi người mẹ đều có một thời tuổi trẻ rực rỡ. NEM dành lời tri ân tới những người mẹ, cùng lưu giữ khoảnh khắc yêu thương và chia sẻ những câu chuyện trong ngày đặc biệt.'];
$posts=[]; foreach($blog_titles as $i=>$title) { $id=nem_seed_post('nem-blog-'.($i+1),'post',$title,'<p>'.$bodies[$i].'</p>'); set_post_thumbnail($id,$images['blog'.($i+1)]); $posts[]=$id; }
$footer_titles=[['Giới thiệu','Triết lý kinh doanh tại NEM Fashion',"NEM’s Blog",'Hệ thống showroom'],['Chính sách giao nhận - Vận chuyển','Hướng dẫn thanh toán','Tra cứu đơn hàng','Hướng dẫn chọn Size','Quy định đổi hàng','Quy định bảo hành và sửa chữa','Khách hàng thân thiết','Hướng dẫn bảo quản sản phẩm']];
$page_texts=['Thời trang NEM đồng hành cùng phụ nữ với các thiết kế thanh lịch dành cho công sở và cuộc sống hằng ngày.','NEM hướng tới phong cách thanh lịch, tôn trọng cá tính và mang lại sự tự tin cho người mặc.','Khám phá câu chuyện thời trang và gợi ý phối đồ từ NEM.','Liên hệ 0966.612.569 để được tư vấn showroom phù hợp.','Liên hệ cửa hàng để xác nhận khu vực, thời gian và phí giao hàng trước khi đặt mua.','Vui lòng xác nhận phương thức thanh toán với cửa hàng trước khi hoàn tất đơn hàng.','Đăng nhập tài khoản để xem đơn hàng đã đặt. Khi cần hỗ trợ, vui lòng cung cấp mã đơn qua email nemcskh@stripe-vn.com.','Vui lòng cung cấp số đo ngực, eo, hông và chiều cao để cửa hàng tư vấn kích cỡ phù hợp.','Vui lòng giữ hóa đơn, tem nhãn và liên hệ cửa hàng để xác nhận điều kiện đổi hàng cho sản phẩm.','Liên hệ cửa hàng kèm mã sản phẩm, hóa đơn và hình ảnh để được tư vấn bảo hành hoặc sửa chữa.','Liên hệ cửa hàng để được cập nhật chương trình dành cho khách hàng thân thiết.','Đọc hướng dẫn trên nhãn sản phẩm. Giặt riêng sản phẩm khác màu, dùng chất giặt dịu nhẹ và phơi nơi thoáng mát.'];
$footer=[]; $n=0; foreach($footer_titles as $gi=>$titles) foreach($titles as $title) { $id=nem_seed_post('nem-info-'.($n+1),'page',$title,'<p>'.$page_texts[$n].'</p>'); $footer[$gi][]=['title'=>$title,'url'=>get_permalink($id)]; $n++; }
$casual=get_term_link($groups['casual'],'product_cat'); $plus=get_term_link($groups['plus'],'product_cat');
$home=nem_seed_post('nem-trang-chu','page','Trang chủ NEM','');
if(!get_option('nem_home_content')) update_option('nem_home_content',[
    'images'=>$images,'hero_url'=>$plus,'collection_url'=>$plus,
    'menu'=>[['title'=>'SẢN PHẨM⌄','url'=>wc_get_page_permalink('shop')],['title'=>'SẢN PHẨM MỚI','url'=>$casual],['title'=>'NEM PLUS+','url'=>$plus],['title'=>'SẢN PHẨM SALE⌄','url'=>$plus],['title'=>'BỘ SƯU TẬP⌄','url'=>$plus]],
    'features'=>[['title'=>'ĐẦM XÁM TÚI HỘP','url'=>$casual],['title'=>'ĐẦM BE RÚT EO','url'=>$casual]],
    'sections'=>[['title'=>'NEM CASUAL','products'=>array_slice($ids,0,4)],['title'=>'NEM PLUS+','products'=>array_slice($ids,4,4)]],
    'blog_title'=>'NEM’S BLOG','blog_subtitle'=>'ĐÓN ĐẦU XU HƯỚNG, ĐỊNH HÌNH PHONG CÁCH','posts'=>$posts,
    'newsletter_title'=>'ĐĂNG KÝ BẢN TIN','newsletter_text'=>'Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất',
    'socials'=>[['title'=>'Facebook','icon'=>'f','url'=>'https://www.facebook.com/nemfashion'],['title'=>'Instagram','icon'=>'◎','url'=>'https://www.instagram.com/nemfashion/'],['title'=>'YouTube','icon'=>'▶','url'=>'https://www.youtube.com/results?search_query=NEM+Fashion']],
    'company_title'=>'NEM FASHION - THỜI TRANG CÔNG SỞ','company_text'=>"Công ty TNHH Dịch vụ và Thương mại An Thành.\nSố ĐKKD 0107861393, Sở KHĐT Tp. Hà Nội cấp ngày 04/10/2017\nĐịa chỉ: Lô 1+2, Ô quy hoạch E.2/NO7 đường Lâm Hạ\nphường Bồ Đề, quận Long Biên, Hà Nội\nMua hàng online: 0966.612.569\nEmail: nemcskh@stripe-vn.com",'footer_groups'=>$footer,'copyright'=>'© 2020 - Bản quyền NEM'
]);
$config=get_option('nem_home_content'); $config['images']=array_merge($images,$config['images']); update_option('nem_home_content',$config);
switch_theme('storefront-child'); update_option('show_on_front','page'); update_option('page_on_front',$home);
update_option('woocommerce_currency','VND'); update_option('woocommerce_price_num_decimals',0);
// Reference items remain displayed on catalog pages despite having no real stock.
update_option('woocommerce_hide_out_of_stock_items','no');
flush_rewrite_rules();
echo 'NEM ready: '.count($ids).' products, '.count($posts).' posts, '.count($images)." images\n";
