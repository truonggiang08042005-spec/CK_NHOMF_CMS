<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_theme_page('Nội dung trang chủ NEM', 'Trang chủ NEM', 'manage_options', 'nem-home', 'nem_render_home_admin');
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'appearance_page_nem-home') return;
    wp_enqueue_media();
    wp_enqueue_script('nem-home-admin', get_stylesheet_directory_uri().'/home-admin.js', ['jquery'], filemtime(__DIR__.'/home-admin.js'), true);
});

function nem_render_home_admin() {
    if (!current_user_can('manage_options')) return;
    $fields = [
        'hero'=>'Banner đầu trang — ảnh 1',
        'hero3'=>'Banner đầu trang — ảnh 2',
        'hero4'=>'Banner đầu trang — ảnh 3',
        'feature1'=>'Ảnh Đầm xám túi hộp',
        'feature2'=>'Ảnh Đầm be rút eo',
        'collection'=>'Banner bộ sưu tập',
    ];
    $config = (array) get_option('nem_home_content', []);
    if (isset($_POST['nem_save_images'])) {
        check_admin_referer('nem_home_images');
        $posted = isset($_POST['nem_images']) && is_array($_POST['nem_images']) ? wp_unslash($_POST['nem_images']) : [];
        $images = (array) ($config['images'] ?? []);
        $valid = true;
        foreach ($fields as $key=>$label) {
            if (!array_key_exists($key, $posted)) continue;
            if (!is_scalar($posted[$key])) { $valid = false; break; }
            $id = absint($posted[$key]);
            if ($id && (get_post_type($id) !== 'attachment' || !wp_attachment_is_image($id))) { $valid = false; break; }
            $images[$key] = $id;
        }
        if ($valid) {
            $config['images'] = $images;
            update_option('nem_home_content', $config);
            echo '<div class="notice notice-success"><p>Đã lưu ảnh trang chủ.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Ảnh không hợp lệ. Chưa lưu thay đổi.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Trang chủ NEM</h1>
        <p>Bấm <strong>Chọn / Thay ảnh</strong>, tải ảnh lên hoặc chọn từ Thư viện, sau đó bấm <strong>Lưu ảnh trang chủ</strong>.</p>
        <p>Banner ngang nên dùng ảnh có tỷ lệ khoảng 2,52:1 (ví dụ 1920 × 762 px).</p>
        <form method="post">
            <?php wp_nonce_field('nem_home_images'); ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;max-width:1100px">
                <?php foreach ($fields as $key=>$label): $id = absint($config['images'][$key] ?? 0); ?>
                    <section class="nem-admin-image" style="padding:16px;background:white;border:1px solid #ddd">
                        <h2 style="margin-top:0"><?php echo esc_html($label); ?></h2>
                        <div class="nem-admin-preview" style="height:180px;display:flex;align-items:center;margin-bottom:12px;overflow:hidden">
                            <?php if ($id): ?><img src="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" alt="<?php echo esc_attr($label); ?>" style="max-width:100%;max-height:180px;object-fit:contain"><?php else: ?><span>Chưa chọn ảnh</span><?php endif; ?>
                        </div>
                        <input type="hidden" name="nem_images[<?php echo esc_attr($key); ?>]" value="<?php echo $id; ?>">
                        <button type="button" class="button nem-admin-select">Chọn / Thay ảnh</button>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php submit_button('Lưu ảnh trang chủ', 'primary', 'nem_save_images'); ?>
        </form>
        <p><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">Xem trang chủ ↗</a></p>
        <p>Ảnh sản phẩm được thay tại <strong>Products → All Products</strong>; ảnh bài viết được thay tại <strong>Posts</strong>.</p>
    </div>
    <?php
}
