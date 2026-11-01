<?php
if(PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/wp-load.php';
if(in_array('--newsletter',$argv,true)) {
    $email='nem-test-'.uniqid().'@example.invalid';
    $key='nem_mail_'.hash('sha256',$email);
    register_shutdown_function(function() use($key) { $id=get_option($key); if($id) wp_delete_post((int)$id,true); delete_option($key); });
    add_filter('wp_redirect',function($url) use($key,$email) {
        $id=get_option($key);
        if(!$id || get_post_field('post_title',$id)!==$email || strpos($url,'newsletter=success')===false) { fwrite(STDERR,"FAIL newsletter persistence: ".wp_json_encode(['id'=>$id,'url'=>$url,'title'=>$id?get_post_field('post_title',$id):null])."\n"); exit(1); }
        echo "PASS: newsletter submission saved email to database; test data cleaned up\n";
        return false;
    });
    $_POST=['email'=>$email,'nem_nonce'=>wp_create_nonce('nem_subscribe')]; nem_subscribe();
}
$c=get_option('nem_home_content');
$errors=[];
foreach($c['images'] as $name=>$id) if(!is_file(get_attached_file($id))) $errors[]='Missing image '.$name;
$count=0; foreach($c['sections'] as $s) foreach($s['products'] as $id) { $p=wc_get_product($id); if(!$p || !$p->is_visible() || !$p->get_image_id()) $errors[]='Invalid product '.$id; $count++; }
foreach($c['posts'] as $id) if(get_post_status($id)!=='publish' || !get_post_thumbnail_id($id)) $errors[]='Invalid blog '.$id;
if($count!==8) $errors[]='Expected 8 products';
if(get_option('stylesheet')!=='storefront-child') $errors[]='Wrong theme';
if(get_option('show_on_front')!=='page') $errors[]='Wrong front page';
echo $errors?implode("\n",$errors):"PASS: 8 products, 3 posts, all images, active theme, front page\n";
exit($errors?1:0);
