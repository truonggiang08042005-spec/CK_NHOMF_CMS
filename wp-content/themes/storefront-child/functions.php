<?php
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('nem-style', get_stylesheet_directory_uri().'/nem.css', ['storefront-style'], '1.0.1');
    wp_enqueue_style('nem-overrides', get_stylesheet_directory_uri().'/overrides.css', ['nem-style'], '1.0.0');
    wp_enqueue_script('nem-script', get_stylesheet_directory_uri().'/nem.js', [], '1.0.0', true);
}, 99);
add_action('admin_menu', function () {
    add_theme_page('Nội dung trang chủ NEM', 'Trang chủ NEM', 'manage_options', 'nem-home', function () {
        if (!current_user_can('manage_options')) return;
        if (isset($_POST['nem_config'])) {
            check_admin_referer('nem_settings');
            $data=json_decode(wp_unslash($_POST['nem_config']), true);
            if (is_array($data)) { update_option('nem_home_content', map_deep($data, 'sanitize_textarea_field')); echo '<div class="notice notice-success"><p>Đã lưu nội dung.</p></div>'; }
            else echo '<div class="notice notice-error"><p>JSON không hợp lệ. Chưa lưu thay đổi.</p></div>';
        }
        echo '<div class="wrap"><h1>Nội dung trang chủ NEM</h1><p>Chỉnh banner, tiêu đề, thông tin liên hệ và liên kết bằng JSON. Sản phẩm quản lý tại Sản phẩm; blog quản lý tại Bài viết. Ảnh dùng ID trong Thư viện.</p><form method="post">';
        wp_nonce_field('nem_settings');
        echo '<textarea name="nem_config" style="width:100%;min-height:600px;font-family:monospace">'.esc_textarea(wp_json_encode(get_option('nem_home_content'), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</textarea>';
        submit_button(); echo '</form></div>';
    });
});
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
