<?php
/** NEM page content and layout controls. */
defined('ABSPATH') || exit;

function npe_builder_schema($page) {
    $config = (array) get_option('nem_home_content', []);
    $fields = [];
    if ($page === 'home') {
        $fields = [
            'hero_url' => ['label' => 'Liên kết banner đầu trang', 'type' => 'url', 'default' => $config['hero_url'] ?? ''],
            'collection_url' => ['label' => 'Liên kết banner bộ sưu tập', 'type' => 'url', 'default' => $config['collection_url'] ?? home_url('/bo-suu-tap/')],
            'blog_title' => ['label' => 'Tiêu đề bài viết', 'default' => $config['blog_title'] ?? 'NEM’S BLOG'],
            'blog_subtitle' => ['label' => 'Mô tả bài viết', 'type' => 'textarea', 'default' => $config['blog_subtitle'] ?? ''],
            'posts' => ['label' => 'ID bài viết (phân cách bằng dấu phẩy)', 'type' => 'posts', 'default' => $config['posts'] ?? []],
            'newsletter_title' => ['label' => 'Tiêu đề đăng ký bản tin', 'default' => $config['newsletter_title'] ?? 'ĐĂNG KÝ BẢN TIN'],
            'newsletter_text' => ['label' => 'Nội dung đăng ký bản tin', 'type' => 'textarea', 'default' => $config['newsletter_text'] ?? ''],
            'company_title' => ['label' => 'Tên công ty ở chân trang', 'default' => $config['company_title'] ?? ''],
            'company_text' => ['label' => 'Thông tin công ty', 'type' => 'textarea', 'default' => $config['company_text'] ?? ''],
            'copyright' => ['label' => 'Dòng bản quyền', 'default' => $config['copyright'] ?? ''],
        ];
        foreach (['logo' => 'Logo', 'hero' => 'Banner đầu trang 1', 'hero3' => 'Banner đầu trang 2', 'hero4' => 'Banner đầu trang 3', 'feature1' => 'Ảnh thiết kế nổi bật 1', 'feature2' => 'Ảnh thiết kế nổi bật 2', 'collection' => 'Banner bộ sưu tập', 'payment1' => 'Logo thanh toán 1', 'payment2' => 'Logo thanh toán 2', 'payment' => 'Logo thanh toán 3', 'payment4' => 'Logo thanh toán 4', 'bct' => 'Ảnh chứng nhận'] as $key => $label) {
            $fields['image_' . $key] = ['label' => $label, 'type' => 'image', 'default' => (int) ($config['images'][$key] ?? 0)];
        }
        foreach (array_values($config['features'] ?? []) as $index => $feature) {
            $fields['feature_' . $index . '_title'] = ['label' => 'Thiết kế nổi bật ' . ($index + 1) . ' — tiêu đề', 'default' => $feature['title'] ?? ''];
            $fields['feature_' . $index . '_url'] = ['label' => 'Thiết kế nổi bật ' . ($index + 1) . ' — liên kết', 'type' => 'url', 'default' => $feature['url'] ?? ''];
        }
        foreach (array_values($config['sections'] ?? []) as $index => $section) {
            $fields['section_' . $index . '_title'] = ['label' => 'Nhóm sản phẩm ' . ($index + 1) . ' — tiêu đề', 'default' => $section['title'] ?? ''];
            $fields['section_' . $index . '_products'] = ['label' => 'Nhóm sản phẩm ' . ($index + 1) . ' — ID sản phẩm', 'type' => 'products', 'default' => $section['products'] ?? []];
        }
    } elseif ($page === 'product') {
        $fields = [
            'gallery_position' => ['label' => 'Vị trí thư viện ảnh', 'type' => 'select', 'choices' => ['left' => 'Ảnh bên trái, thông tin bên phải', 'right' => 'Thông tin bên trái, ảnh bên phải'], 'default' => 'left'],
            'brand' => ['label' => 'Tên thương hiệu', 'default' => 'NEM'],
            'support_text' => ['label' => 'Lời mời tư vấn', 'default' => 'Nhấn để được tư vấn và nhận ưu đãi'],
            'support_url' => ['label' => 'Liên kết tư vấn (để trống dùng Zalo hiện tại)', 'type' => 'url', 'default' => ''],
            'size_guide_title' => ['label' => 'Nhãn hướng dẫn kích thước', 'default' => 'HƯỚNG DẪN CHỌN SIZE'],
            'reviews_title' => ['label' => 'Tiêu đề đánh giá', 'default' => 'Đánh giá sản phẩm'],
            'related_title' => ['label' => 'Tiêu đề sản phẩm tương tự', 'default' => 'SẢN PHẨM TƯƠNG TỰ'],
            'recent_title' => ['label' => 'Tiêu đề sản phẩm đã xem', 'default' => 'SẢN PHẨM ĐÃ XEM'],
            'banner_image' => ['label' => 'Ảnh thông báo / ưu đãi', 'type' => 'image', 'default' => 0],
            'banner_url' => ['label' => 'Liên kết ảnh thông báo', 'type' => 'url', 'default' => ''],
            'banner_text' => ['label' => 'Nội dung thông báo / ưu đãi', 'type' => 'textarea', 'default' => ''],
        ];
    } elseif ($page === 'checkout') {
        $fields = [
            'heading' => ['label' => 'Tiêu đề trang thanh toán', 'default' => 'Thanh toán'],
            'contact_title' => ['label' => 'Tiêu đề thông tin khách hàng', 'default' => 'Thông tin giao hàng'],
            'address_title' => ['label' => 'Tiêu đề địa chỉ', 'default' => 'Địa chỉ giao hàng'],
            'shipping_title' => ['label' => 'Tiêu đề vận chuyển', 'default' => 'Phương thức vận chuyển'],
            'payment_title' => ['label' => 'Tiêu đề phương thức thanh toán', 'default' => 'Phương thức thanh toán'],
            'summary_title' => ['label' => 'Tiêu đề đơn hàng', 'default' => 'Đơn hàng của bạn'],
            'place_order_text' => ['label' => 'Nhãn nút đặt hàng', 'default' => 'ĐẶT HÀNG'],
            'order_notes_title' => ['label' => 'Nhãn ghi chú', 'default' => 'Ghi chú đơn hàng'],
            'coupon_title' => ['label' => 'Tiêu đề mã khuyến mãi', 'default' => 'Mã khuyến mãi'],
            'name_label' => ['label' => 'Nhãn họ và tên', 'default' => 'Họ và tên'],
            'phone_label' => ['label' => 'Nhãn số điện thoại', 'default' => 'Số điện thoại'],
            'email_label' => ['label' => 'Nhãn email', 'default' => 'Email'],
            'street_label' => ['label' => 'Nhãn số nhà, tên đường', 'default' => 'Số nhà, tên đường'],
            'district_label' => ['label' => 'Nhãn phường, quận', 'default' => 'Phường/Xã, Quận/Huyện'],
            'city_label' => ['label' => 'Nhãn tỉnh, thành phố', 'default' => 'Tỉnh/Thành phố'],
            'banner_image' => ['label' => 'Ảnh thông báo / ưu đãi', 'type' => 'image', 'default' => 0],
            'banner_url' => ['label' => 'Liên kết ảnh thông báo', 'type' => 'url', 'default' => ''],
            'banner_text' => ['label' => 'Nội dung thông báo / ưu đãi', 'type' => 'textarea', 'default' => ''],
        ];
    }
    return $fields;
}

function npe_builder_sections($page) {
    if ($page === 'home') {
        $sections = ['hero' => 'Banner đầu trang', 'features' => 'Thiết kế nổi bật'];
        $config = (array) get_option('nem_home_content', []);
        foreach (array_values($config['sections'] ?? []) as $index => $section) {
            $sections['products_' . $index] = 'Sản phẩm: ' . ($section['title'] ?? ($index + 1));
        }
        return $sections + ['collection' => 'Banner bộ sưu tập', 'blog' => 'Bài viết', 'recent' => 'Sản phẩm đã xem', 'newsletter' => 'Đăng ký bản tin'];
    }
    if ($page === 'product') {
        return ['details' => 'Thư viện ảnh & thông tin sản phẩm', 'reviews' => 'Đánh giá sản phẩm', 'related' => 'Sản phẩm tương tự', 'recent' => 'Sản phẩm đã xem', 'promo' => 'Ảnh / thông báo ưu đãi', 'newsletter' => 'Đăng ký bản tin'];
    }
    if ($page === 'checkout') {
        return ['contact' => 'Thông tin khách hàng', 'address' => 'Địa chỉ giao hàng', 'shipping' => 'Vận chuyển', 'payment' => 'Phương thức thanh toán', 'notes' => 'Ghi chú đơn hàng', 'promo' => 'Ảnh / thông báo ưu đãi', 'summary' => 'Tóm tắt đơn hàng', 'coupon' => 'Mã giảm giá'];
    }
    return [];
}

function npe_page_settings($page) {
    $settings = get_option('npe_page_builder', []);
    $settings = is_array($settings) && isset($settings[$page]) && is_array($settings[$page]) ? $settings[$page] : [];
    return [
        'fields' => isset($settings['fields']) && is_array($settings['fields']) ? $settings['fields'] : [],
        'order' => isset($settings['order']) && is_array($settings['order']) ? $settings['order'] : [],
        'hidden' => isset($settings['hidden']) && is_array($settings['hidden']) ? $settings['hidden'] : [],
        'columns' => isset($settings['columns']) && is_array($settings['columns']) ? $settings['columns'] : [],
        'feature_order' => isset($settings['feature_order']) && is_array($settings['feature_order']) ? $settings['feature_order'] : [],
    ];
}

function npe_section_column($page, $section) {
    $settings = npe_page_settings($page);
    $default = in_array($section, ['summary', 'coupon'], true) ? 'sidebar' : 'main';
    $column = $settings['columns'][$section] ?? $default;
    return in_array($column, ['main', 'sidebar', 'full'], true) ? $column : $default;
}

function npe_setting($page, $key, $default = '') {
    // Home values stay in the existing option so the older image editor remains compatible.
    if ($page === 'home') {
        $schema = npe_builder_schema('home');
        return isset($schema[$key]) ? $schema[$key]['default'] : $default;
    }
    $settings = npe_page_settings($page);
    return array_key_exists($key, $settings['fields']) ? $settings['fields'][$key] : $default;
}

function npe_section_order($page, $include_hidden = false) {
    $settings = npe_page_settings($page);
    $allowed = array_keys(npe_builder_sections($page));
    $order = array_values(array_unique(array_merge(array_intersect($settings['order'], $allowed), $allowed)));
    if ($include_hidden) return $order;
    // A working checkout always keeps the fields/payment/summary needed to place an order.
    $required = $page === 'checkout' ? ['contact', 'address', 'payment', 'summary'] : [];
    return array_values(array_filter($order, function ($section) use ($settings, $required) {
        return in_array($section, $required, true) || !in_array($section, $settings['hidden'], true);
    }));
}

function npe_render_sections($page, $callbacks) {
    foreach (npe_section_order($page) as $section) {
        if (isset($callbacks[$section]) && is_callable($callbacks[$section])) call_user_func($callbacks[$section]);
    }
}

function npe_render_promo($page) {
    $image = absint(npe_setting($page, 'banner_image', 0));
    $text = npe_setting($page, 'banner_text', '');
    $url = npe_setting($page, 'banner_url', '');
    if (!$image && !$text && !(function_exists('npe_visual_preview_type') && npe_visual_preview_type())) return;
    echo '<section class="npe-wrap npe-promo" aria-label="Thông báo cửa hàng">';
    if ($url) echo '<a href="' . esc_url($url) . '">';
    if ($image) echo wp_get_attachment_image($image, 'large', false, ['loading' => 'lazy']);
    if ($text || (function_exists('npe_visual_preview_type') && npe_visual_preview_type())) echo '<p>' . nl2br(esc_html($text)) . '</p>';
    if ($url) echo '</a>';
    echo '</section>';
}

add_action('admin_menu', function () {
    add_menu_page('Bố cục & nội dung NEM', 'Giao diện NEM', 'manage_options', 'npe-builder', 'npe_render_builder_admin', 'dashicons-layout', 59);
    add_submenu_page('npe-builder', 'Bố cục & nội dung', 'Bố cục & nội dung', 'manage_options', 'npe-builder', 'npe_render_builder_admin');
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_npe-builder') return;
    wp_enqueue_media();
    wp_enqueue_script('npe-builder-admin', content_url('/mu-plugins/nem-page-experience/admin.js'), ['jquery', 'jquery-ui-sortable'], filemtime(__DIR__ . '/admin.js'), true);
    wp_enqueue_style('npe-builder-admin', content_url('/mu-plugins/nem-page-experience/admin.css'), [], filemtime(__DIR__ . '/admin.css'));
});

add_action('wp_enqueue_scripts', function () {
    if ((function_exists('npe_is_page') && npe_is_page()) || (function_exists('npe_is_checkout') && npe_is_checkout())) {
        wp_enqueue_style('npe-builder-public', content_url('/mu-plugins/nem-page-experience/admin-public.css'), [], filemtime(__DIR__ . '/admin-public.css'));
    }
}, 1001);

function npe_builder_clean_field($value, $field) {
    $type = $field['type'] ?? 'text';
    if (is_array($value) || is_object($value)) return new WP_Error('npe_invalid_field', 'Giá trị không hợp lệ.');
    if ($type === 'image') {
        if (!preg_match('/^\d*$/', (string) $value)) return new WP_Error('npe_invalid_image', 'ID ảnh không hợp lệ.');
        $id = absint($value);
        return $id && (!wp_attachment_is_image($id) || get_post_type($id) !== 'attachment') ? new WP_Error('npe_invalid_image', 'Vui lòng chọn ảnh từ Thư viện.') : $id;
    }
    if ($type === 'products' || $type === 'posts') {
        $ids = [];
        $tokens = preg_split('/[\s,]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
        foreach (array_slice($tokens, 0, 100) as $token) {
            if (!ctype_digit($token)) return new WP_Error('npe_invalid_id', 'Danh sách ID chỉ gồm số, phân cách bằng dấu phẩy.');
            $id = absint($token);
            if (!$id || get_post_type($id) !== ($type === 'products' ? 'product' : 'post')) return new WP_Error('npe_invalid_id', 'Có ID sản phẩm hoặc bài viết không hợp lệ.');
            $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }
    if ($type === 'select') return array_key_exists((string) $value, $field['choices']) ? (string) $value : new WP_Error('npe_invalid_choice', 'Lựa chọn không hợp lệ.');
    if ($type === 'url') {
        $url = esc_url_raw(trim((string) $value), ['http', 'https']);
        return $value !== '' && !$url ? new WP_Error('npe_invalid_url', 'Liên kết không hợp lệ.') : $url;
    }
    return $type === 'textarea' ? sanitize_textarea_field($value) : sanitize_text_field($value);
}

function npe_builder_update_home($fields) {
    $config = (array) get_option('nem_home_content', []);
    foreach ($fields as $key => $value) {
        if (strpos($key, 'image_') === 0) $config['images'][substr($key, 6)] = $value;
        elseif (preg_match('/^feature_(\d+)_(title|url)$/', $key, $match)) $config['features'][(int) $match[1]][$match[2]] = $value;
        elseif (preg_match('/^section_(\d+)_(title|products)$/', $key, $match)) $config['sections'][(int) $match[1]][$match[2]] = $value;
        else $config[$key] = $value;
    }
    return $config;
}

add_action('admin_post_npe_save_builder', function () {
    if (!current_user_can('manage_options')) wp_die('Bạn không có quyền sửa giao diện.', '', ['response' => 403]);
    check_admin_referer('npe_save_builder');
    $page = isset($_POST['npe_page']) && is_string($_POST['npe_page']) ? sanitize_key(wp_unslash($_POST['npe_page'])) : '';
    if (!in_array($page, ['home', 'product', 'checkout'], true)) wp_die('Trang không hợp lệ.', '', ['response' => 400]);
    $posted = isset($_POST['npe_fields']) && is_array($_POST['npe_fields']) ? wp_unslash($_POST['npe_fields']) : [];
    $clean = [];
    foreach (npe_builder_schema($page) as $key => $field) {
        if (!array_key_exists($key, $posted)) continue;
        $value = npe_builder_clean_field($posted[$key], $field);
        if (is_wp_error($value)) wp_die(esc_html($field['label'] . ': ' . $value->get_error_message()), 'Chưa lưu thay đổi', ['back_link' => true, 'response' => 400]);
        $clean[$key] = $value;
    }
    $allowed = array_keys(npe_builder_sections($page));
    $posted_order = isset($_POST['npe_order']) && is_array($_POST['npe_order']) ? wp_unslash($_POST['npe_order']) : [];
    $posted_enabled = isset($_POST['npe_enabled']) && is_array($_POST['npe_enabled']) ? wp_unslash($_POST['npe_enabled']) : [];
    $posted_order = array_filter($posted_order, 'is_string');
    $posted_enabled = array_filter($posted_enabled, 'is_string');
    $order = array_values(array_unique(array_merge(array_intersect($posted_order, $allowed), $allowed)));
    $hidden = array_values(array_diff($allowed, $posted_enabled));
    if ($page === 'checkout') $hidden = array_values(array_diff($hidden, ['contact', 'address', 'payment', 'summary']));
    $all = get_option('npe_page_builder', []);
    if (!is_array($all)) $all = [];
    $old = isset($all[$page]) && is_array($all[$page]) ? $all[$page] : [];
    $old_fields = isset($old['fields']) && is_array($old['fields']) ? $old['fields'] : [];
    $columns = isset($old['columns']) && is_array($old['columns']) ? $old['columns'] : [];
    if ($page === 'checkout') {
        $posted_columns = isset($_POST['npe_columns']) && is_array($_POST['npe_columns']) ? wp_unslash($_POST['npe_columns']) : [];
        foreach ($allowed as $section) {
            if (isset($posted_columns[$section]) && is_string($posted_columns[$section]) && in_array($posted_columns[$section], ['main', 'sidebar', 'full'], true)) $columns[$section] = $posted_columns[$section];
        }
    }
    $all[$page] = array_merge($old, ['fields' => array_merge($old_fields, $clean), 'order' => $order, 'hidden' => $hidden, 'columns' => $columns]);
    if ($page === 'home') update_option('nem_home_content', npe_builder_update_home($clean));
    update_option('npe_page_builder', $all, false);
    wp_safe_redirect(add_query_arg(['page' => 'npe-builder', 'tab' => $page, 'npe_saved' => '1'], admin_url('admin.php')));
    exit;
});

function npe_render_builder_admin() {
    if (!current_user_can('manage_options')) return;
    $pages = ['home' => 'Trang chủ', 'product' => 'Chi tiết sản phẩm', 'checkout' => 'Thanh toán'];
    $page = isset($_GET['tab']) && is_string($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'home';
    if (!isset($pages[$page])) $page = 'home';
    $settings = npe_page_settings($page);
    $sections = npe_builder_sections($page);
    ?>
    <div class="wrap npe-builder">
        <h1>Bố cục & nội dung NEM</h1>
        <p>Sửa nội dung, thay ảnh và sắp xếp các khối hiển thị. Ảnh, tên, giá, biến thể và mô tả riêng của sản phẩm được quản lý tại <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>">Sản phẩm</a>.</p>
        <?php if (isset($_GET['npe_saved']) && $_GET['npe_saved'] === '1'): ?><div class="notice notice-success is-dismissible"><p>Đã lưu bố cục và nội dung.</p></div><?php endif; ?>
        <nav class="nav-tab-wrapper" aria-label="Trang cần chỉnh sửa"><?php foreach ($pages as $key => $label): ?><a class="nav-tab<?php echo $page === $key ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'npe-builder', 'tab' => $key], admin_url('admin.php'))); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav>
        <?php if ($page === 'checkout'): ?>
        <section class="npe-builder-panel npe-payment-setup">
            <h2>Thiết lập thanh toán</h2>
            <p class="npe-setup-links"><?php foreach (['bacs' => 'Tài khoản ngân hàng', 'momo' => 'Ví MoMo', 'momo_atm' => 'Thẻ ATM / NAPAS', 'momo_card' => 'Thẻ quốc tế', 'payoo' => 'Payoo · ATM / thẻ / QR / ví điện tử', 'cod' => 'Thanh toán khi nhận hàng'] as $method => $label): ?><a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'wc-settings', 'tab' => 'checkout', 'section' => $method], admin_url('admin.php'))); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></p>
            <p><strong>Chưa có tài khoản merchant:</strong> mở thiết lập MoMo hoặc Payoo, bật phương thức và chọn <strong>Mô phỏng local</strong>. Bạn có thể thử kết quả thành công, thất bại hoặc hủy; không trừ tiền và đơn luôn được ghi rõ là mô phỏng.</p>
            <p><strong>Khi đã có tài khoản thử nghiệm:</strong> nhập thông tin do đối tác cấp trong thiết lập tương ứng, tắt mô phỏng local và giữ chế độ Sandbox. Đối tác cần truy cập được URL HTTPS nhận kết quả thanh toán. Chỉ chuyển sang môi trường thật sau khi hoàn tất kiểm tra tích hợp với đối tác.</p>
            <p>Với chuyển khoản, thêm tên ngân hàng, chủ tài khoản và số tài khoản tại <strong>Tài khoản ngân hàng</strong>. Cửa hàng xác nhận đơn sau khi đối soát tiền nhận được.</p>
            <p>Menu rê chuột được chỉnh tại <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">Giao diện → Menu</a>.</p>
        </section>
        <?php endif; ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="npe_save_builder"><input type="hidden" name="npe_page" value="<?php echo esc_attr($page); ?>"><?php wp_nonce_field('npe_save_builder'); ?>
            <div class="npe-builder-columns">
                <section class="npe-builder-panel npe-builder-layout"><h2>Thứ tự các khối</h2><p>Kéo tay nắm hoặc dùng nút ↑ / ↓. Bỏ chọn để ẩn khối.</p>
                    <?php if ($page === 'checkout'): ?><p class="description">Các khối thông tin, địa chỉ, thanh toán và đơn hàng luôn được giữ để khách có thể đặt hàng. Chọn cột cho mỗi khối; thứ tự được áp dụng trong từng cột.</p><?php endif; ?>
                    <ol class="npe-builder-order" aria-label="Thứ tự khối <?php echo esc_attr($pages[$page]); ?>">
                    <?php foreach (npe_section_order($page, true) as $key): $required = $page === 'checkout' && in_array($key, ['contact', 'address', 'payment', 'summary'], true); ?>
                        <li data-section="<?php echo esc_attr($key); ?>"><span class="npe-drag-handle dashicons dashicons-menu" aria-hidden="true"></span><input type="hidden" name="npe_order[]" value="<?php echo esc_attr($key); ?>"><div class="npe-block-control"><label><input type="checkbox" name="npe_enabled[]" value="<?php echo esc_attr($key); ?>" <?php checked($required || !in_array($key, $settings['hidden'], true)); ?> <?php disabled($required); ?>><?php echo esc_html($sections[$key]); ?><?php if ($required): ?><span class="npe-required">Bắt buộc</span><?php endif; ?></label><?php if ($page === 'checkout'): ?><select name="npe_columns[<?php echo esc_attr($key); ?>]" aria-label="Vị trí khối <?php echo esc_attr($sections[$key]); ?>"><?php foreach (['main' => 'Cột chính', 'sidebar' => 'Cột bên phải', 'full' => 'Toàn chiều ngang'] as $column => $label): ?><option value="<?php echo esc_attr($column); ?>" <?php selected(npe_section_column($page, $key), $column); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><?php endif; ?></div><div class="npe-order-buttons"><button type="button" class="button npe-move-up" aria-label="Đưa <?php echo esc_attr($sections[$key]); ?> lên">↑</button><button type="button" class="button npe-move-down" aria-label="Đưa <?php echo esc_attr($sections[$key]); ?> xuống">↓</button></div></li>
                    <?php endforeach; ?></ol><p class="npe-order-status screen-reader-text" aria-live="polite"></p>
                    <?php if ($page === 'product'): ?><p class="description">Đổi ảnh sản phẩm tại <strong>Sản phẩm → Sửa sản phẩm → Ảnh sản phẩm / Album hình ảnh sản phẩm</strong>. Đổi vị trí ảnh và thông tin ở phần nội dung bên cạnh.</p><?php endif; ?>
                </section>
                <section class="npe-builder-panel"><h2>Nội dung & hình ảnh</h2><div class="npe-builder-fields">
                <?php foreach (npe_builder_schema($page) as $key => $field): $type = $field['type'] ?? 'text'; $value = npe_setting($page, $key, $field['default'] ?? ''); $id = 'npe-field-' . $key; ?>
                    <div class="npe-builder-field<?php echo $type === 'image' ? ' npe-image-field' : ''; ?>"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?></label>
                    <?php if ($type === 'image'): ?><div class="npe-image-preview"><?php if ($value) echo wp_get_attachment_image(absint($value), 'medium'); else echo '<span>Chưa chọn ảnh</span>'; ?></div><input type="hidden" class="npe-image-id" id="<?php echo esc_attr($id); ?>" name="npe_fields[<?php echo esc_attr($key); ?>]" value="<?php echo absint($value); ?>"><div><button type="button" class="button npe-select-image">Chọn / thay ảnh</button> <button type="button" class="button-link-delete npe-remove-image">Bỏ ảnh</button></div>
                    <?php elseif ($type === 'textarea'): ?><textarea rows="4" id="<?php echo esc_attr($id); ?>" name="npe_fields[<?php echo esc_attr($key); ?>]" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                    <?php elseif ($type === 'select'): ?><select id="<?php echo esc_attr($id); ?>" name="npe_fields[<?php echo esc_attr($key); ?>]" class="widefat"><?php foreach ($field['choices'] as $choice => $label): ?><option value="<?php echo esc_attr($choice); ?>" <?php selected($value, $choice); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                    <?php else: ?><input type="<?php echo $type === 'url' ? 'url' : 'text'; ?>" id="<?php echo esc_attr($id); ?>" name="npe_fields[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(is_array($value) ? implode(', ', $value) : $value); ?>" class="large-text"><?php if ($type === 'products' || $type === 'posts'): ?><p class="description">Để trống nếu không hiển thị mục nào. Tối đa 100 ID; thứ tự nhập là thứ tự hiển thị.</p><?php endif; ?>
                    <?php endif; ?></div>
                <?php endforeach; ?></div></section>
            </div>
            <?php submit_button('Lưu bố cục & nội dung'); ?>
        </form>
        <p><a href="<?php echo esc_url($page === 'checkout' && function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/')); ?>" target="_blank" rel="noopener">Xem website ↗</a> · <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">Sửa menu</a> · <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>">Cấu hình phương thức thanh toán</a></p>
    </div>
    <?php
}
