<?php
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('nem-style', get_stylesheet_directory_uri().'/nem.css', ['storefront-style'], filemtime(__DIR__.'/nem.css'));
    wp_enqueue_style('nem-overrides', get_stylesheet_directory_uri().'/overrides.css', ['nem-style'], filemtime(__DIR__.'/overrides.css'));
    $nem_js = __DIR__.'/nem.js';
    wp_enqueue_script('nem-script', get_stylesheet_directory_uri().'/nem.js', [], filemtime($nem_js), true);
}, 99);
require_once __DIR__.'/home-admin.php';

add_action('init', function () {
    register_post_type('nem_subscriber', ['label'=>'Đăng ký bản tin', 'public'=>false, 'show_ui'=>true, 'supports'=>['title'], 'capability_type'=>'post', 'capabilities'=>['create_posts'=>'do_not_allow'], 'map_meta_cap'=>true]);
});
function nem_subscribe() {
    if (!isset($_POST['nem_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nem_nonce'])), 'nem_subscribe')) wp_die('Phiên đăng ký đã hết hạn.', '', ['response'=>403]);
    $email=sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $status='invalid';
    if (is_email($email) && empty($_POST['website'])) {
        $key='nem_mail_'.hash('sha256', strtolower($email));
        if (!get_option($key)) {
            $id=wp_insert_post(['post_type'=>'nem_subscriber','post_status'=>'private','post_title'=>$email], true);
            if (!is_wp_error($id) && $id) { add_option($key, $id, '', false); $status='success'; }
            else $status='error';
        } else $status='success';
    }
    wp_safe_redirect(add_query_arg('newsletter', $status, home_url('/')).'#newsletter'); exit;
}
add_action('admin_post_nopriv_nem_subscribe','nem_subscribe');
add_action('admin_post_nem_subscribe','nem_subscribe');
function nem_image($id, $class='', $eager=false) {
    if ($class==='payment') {
        $c=get_option('nem_home_content');
        echo '<div class="payment-logos">';
        foreach(['payment1','payment2','payment','payment4'] as $key) if(!empty($c['images'][$key])) echo wp_get_attachment_image((int)$c['images'][$key], 'full', false, ['class'=>'payment-card','loading'=>'lazy']);
        echo '</div>'; return;
    }
    echo wp_get_attachment_image((int)$id, 'full', false, ['class'=>$class,'loading'=>$eager?'eager':'lazy']);
}
