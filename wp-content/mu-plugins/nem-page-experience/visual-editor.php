<?php
/** Visual editing uses the very same templates as the storefront. */
defined('ABSPATH') || exit;

function npe_visual_pages() {
    return ['home' => (int) get_option('page_on_front'), 'product' => (int) get_option('npe_product_layout_page'), 'checkout' => (int) get_option('woocommerce_checkout_page_id')];
}

function npe_visual_page_type($id) {
    return array_search((int) $id, array_filter(npe_visual_pages()), true) ?: '';
}

function npe_visual_url($type, $product_id = 0) {
    $id = npe_visual_pages()[$type] ?? 0;
    return $id ? add_query_arg(array_filter(['page' => 'npe-visual-editor', 'post' => $id, 'product' => absint($product_id)]), admin_url('admin.php')) : '';
}

function npe_visual_product() {
    $id = isset($_GET['product']) && is_scalar($_GET['product']) ? absint($_GET['product']) : 0;
    $product = $id ? wc_get_product($id) : null;
    if ($product && $product->get_status() === 'publish') return $product;
    $products = wc_get_products(['status' => 'publish', 'limit' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
    return $products[0] ?? null;
}

function npe_visual_preview_type() {
    $id = isset($_GET['npe_visual_preview']) && is_scalar($_GET['npe_visual_preview']) ? absint($_GET['npe_visual_preview']) : 0;
    $nonce = isset($_GET['_npe_nonce']) && is_string($_GET['_npe_nonce']) ? $_GET['_npe_nonce'] : '';
    return $id && current_user_can('manage_options') && current_user_can('edit_post', $id) && wp_verify_nonce($nonce, 'npe_visual_preview_' . $id) ? npe_visual_page_type($id) : '';
}

add_filter('get_edit_post_link', function ($link, $id) {
    $type = npe_visual_page_type($id);
    return $type && current_user_can('manage_options') ? npe_visual_url($type) : $link;
}, 20, 2);

add_action('load-post.php', function () {
    $id = isset($_GET['post']) && is_scalar($_GET['post']) ? absint($_GET['post']) : 0;
    $type = npe_visual_page_type($id);
    if ($type && ($_GET['action'] ?? '') === 'edit' && current_user_can('manage_options') && current_user_can('edit_post', $id)) {
        wp_safe_redirect(npe_visual_url($type));
        exit;
    }
});

add_filter('page_row_actions', function ($actions, $post) {
    if (npe_visual_page_type($post->ID) && current_user_can('manage_options')) {
        unset($actions['inline hide-if-no-js']);
        $actions['edit'] = '<a href="' . esc_url(npe_visual_url(npe_visual_page_type($post->ID))) . '">Edit Page · Sửa trực quan</a>';
    }
    return $actions;
}, 20, 2);

add_action('admin_bar_menu', function ($bar) {
    if (!current_user_can('manage_options')) return;
    $type = is_front_page() ? 'home' : (function_exists('is_product') && is_product() ? 'product' : (function_exists('is_checkout') && is_checkout() && !is_order_received_page() ? 'checkout' : ''));
    if (!$type || !npe_visual_url($type)) return;
    if ($type === 'product') $bar->add_node(['id' => 'npe-edit-product', 'title' => 'Sửa dữ liệu sản phẩm', 'href' => admin_url('post.php?post=' . get_queried_object_id() . '&action=edit')]);
    $bar->add_node(['id' => 'edit', 'title' => 'Edit Page · Sửa trực quan', 'href' => npe_visual_url($type, $type === 'product' ? get_queried_object_id() : 0)]);
}, 99);

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=page', 'Sửa trang trực quan', 'Sửa trang trực quan', 'manage_options', 'npe-visual-editor', 'npe_visual_editor_screen');
});

function npe_visual_targets($type) {
    $targets = [];
    if ($type === 'home') {
        foreach (npe_builder_schema('home') as $key => $field) {
            if (preg_match('/^section_(\d+)_title$/', $key, $m)) $targets[$key] = ['selector' => '[data-npe-section="products_' . $m[1] . '"] > h2', 'field' => $key];
            if (preg_match('/^feature_(\d+)_title$/', $key, $m)) $targets[$key] = ['selector' => '.npe-features > a[data-npe-feature="' . $m[1] . '"] h2', 'field' => $key];
        }
        $targets += [
            'blog_title' => ['selector' => '.npe-blog > h2', 'field' => 'blog_title'],
            'blog_subtitle' => ['selector' => '.npe-blog > p', 'field' => 'blog_subtitle'],
            'company_title' => ['selector' => '.npe-footer-grid > div:first-child h3', 'field' => 'company_title'],
            'company_text' => ['selector' => '.npe-footer-grid > div:first-child p', 'field' => 'company_text'],
            'copyright' => ['selector' => '.npe-copyright', 'field' => 'copyright'],
        ];
    } elseif ($type === 'product') {
        foreach (['support_text' => '.npe-consult > span:last-child', 'size_guide_title' => '.npe-size-guide', 'reviews_title' => '.npe-reviews > h2', 'related_title' => '.npe-related > h2', 'recent_title' => '.npe-recent > h2', 'banner_text' => '.npe-promo p'] as $key => $selector) $targets[$key] = compact('selector') + ['field' => $key];
        $targets['product_name'] = ['selector' => '.npe-product-title h1', 'productField' => 'name'];
        $targets['product_description'] = ['selector' => '.npe-description', 'productField' => 'description'];
        $targets['product_price'] = ['selector' => '.npe-price'];
        $targets['product_brand'] = ['selector' => '.npe-brand'];
        $targets['purchase_buttons'] = ['selector' => '.npe-purchase button'];
    } elseif ($type === 'checkout') {
        foreach (['heading' => '.nem-checkout-heading', 'contact_title' => '[data-npe-section="contact"] > h2', 'address_title' => '[data-npe-section="address"] > h2', 'shipping_title' => '[data-npe-section="shipping"] > h2', 'payment_title' => '[data-npe-section="payment"] > h2', 'order_notes_title' => '[data-npe-section="notes"] > h2', 'summary_title' => '[data-npe-section="summary"] > h2', 'place_order_text' => '#place_order', 'banner_text' => '.npe-promo p', 'coupon_title' => '[data-npe-section="coupon"] > h2', 'name_label' => 'label[for="billing_first_name"]', 'phone_label' => 'label[for="billing_phone"]', 'email_label' => 'label[for="billing_email"]', 'street_label' => 'label[for="billing_address_1"]', 'district_label' => 'label[for="billing_address_2"]', 'city_label' => 'label[for="billing_city"]'] as $key => $selector) $targets[$key] = compact('selector') + ['field' => $key];
        foreach (['name_label','phone_label','email_label','street_label','district_label','city_label'] as $key) $targets[$key]['selector'] .= ' .npe-field-label';
        $targets['form_text'] = ['selector' => '.nem-input, .nem-payment-text, .nem-summary-row'];
    }
    if ($type !== 'checkout') {
        foreach (['newsletter_title' => '.npe-newsletter > h2', 'newsletter_text' => '.npe-newsletter > p:first-of-type'] as $key => $selector) $targets[$key] = compact('selector') + ['field' => $key, 'shared' => true];
        $targets['catalog_names'] = ['selector' => '.npe-card h3'];
        $targets['catalog_prices'] = ['selector' => '.npe-card-price'];
        $targets['discover_links'] = ['selector' => '.npe-discover'];
        $targets['footer_text'] = ['selector' => '.npe-footer, .npe-copyright'];
    }
    return $targets;
}

function npe_visual_styles($type, $styles) {
    $clean = [];
    if (!is_array($styles)) return $clean;
    foreach (array_keys(npe_visual_targets($type)) as $key) {
        $style = $styles[$key] ?? [];
        if (!is_array($style)) continue;
        foreach (['fontSize' => [8, 120], 'lineHeight' => [0.8, 3], 'letterSpacing' => [-3, 12]] as $prop => $range) {
            if (isset($style[$prop]) && is_numeric($style[$prop])) $clean[$key][$prop] = max($range[0], min($range[1], (float) $style[$prop]));
        }
        if (isset($style['color']) && is_string($style['color']) && preg_match('/^#[a-f0-9]{6}$/i', $style['color'])) $clean[$key]['color'] = $style['color'];
        if (isset($style['textAlign']) && in_array($style['textAlign'], ['left','center','right'], true)) $clean[$key]['textAlign'] = $style['textAlign'];
        if (isset($style['fontWeight']) && in_array((string) $style['fontWeight'], ['400','500','600','700'], true)) $clean[$key]['fontWeight'] = (string) $style['fontWeight'];
    }
    return $clean;
}

function npe_visual_section_html($html, $key) {
    if (!$html) return '';
    $tags = new WP_HTML_Tag_Processor($html);
    if ($tags->next_tag()) $tags->set_attribute('data-npe-section', $key);
    return $tags->get_updated_html();
}

function npe_visual_feature_order($proposed = null) {
    $config = (array) get_option('nem_home_content', []);
    $allowed = array_keys((array) ($config['features'] ?? []));
    if ($proposed === null) $proposed = npe_page_settings('home')['feature_order'];
    $proposed = is_array($proposed) ? array_filter($proposed, function ($key) { return is_int($key) || (is_string($key) && ctype_digit($key)); }) : [];
    return array_values(array_unique(array_merge(array_intersect(array_map('intval', $proposed), $allowed), $allowed)));
}

function npe_visual_home_header() {
    ob_start();
    get_template_part('template-parts/header', 'nem');
    $html = ob_get_clean();
    $config = (array) get_option('nem_home_content', []);
    $url = !empty($config['images']['logo']) ? wp_get_attachment_image_url((int) $config['images']['logo'], 'full') : '';
    if (!$url) { echo $html; return; }
    $tags = new WP_HTML_Tag_Processor($html);
    while ($tags->next_tag(['tag_name'=>'IMG'])) {
        $classes = ' ' . (string) $tags->get_attribute('class') . ' ';
        if (strpos($classes, ' nem-logo-img ') === false && strpos($classes, ' nem-drawer-logo ') === false) continue;
        $tags->set_attribute('src', $url);
        $tags->remove_attribute('srcset');
        $tags->remove_attribute('sizes');
    }
    echo $tags->get_updated_html();
}

add_action('wp_head', function () {
    $type = is_front_page() ? 'home' : (function_exists('is_product') && is_product() ? 'product' : (function_exists('npe_is_checkout') && npe_is_checkout() ? 'checkout' : ''));
    if (!$type) return;
    $all = get_option('npe_visual_styles', []);
    $styles = npe_visual_styles($type, $all[$type] ?? []);
    $props = ['fontSize' => 'font-size','lineHeight' => 'line-height','letterSpacing' => 'letter-spacing','color' => 'color','textAlign' => 'text-align','fontWeight' => 'font-weight'];
    echo '<style id="npe-visual-typography">';
    foreach ($styles as $key => $values) {
        echo npe_visual_targets($type)[$key]['selector'] . '{';
        foreach ($values as $prop => $value) echo $props[$prop] . ':' . $value . (in_array($prop, ['fontSize','letterSpacing'], true) ? 'px' : '') . '!important;';
        echo '}';
    }
    echo '</style>';
}, 1100);

add_action('template_redirect', function () {
    $type = npe_visual_preview_type();
    if ($type) {
        nocache_headers();
        show_admin_bar(false);
        // Preview never processes a form, consumes notices, or runs checkout AJAX.
        remove_action('template_redirect', 'wc_template_redirect');
        add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false');
        add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false', 1000);
    } elseif (is_page((int) get_option('npe_product_layout_page')) && get_option('npe_product_layout_page')) {
        $product = npe_visual_product();
        if ($product) { wp_safe_redirect($product->get_permalink()); exit; }
    }
}, 0);

add_action('wp_enqueue_scripts', function () {
    if (!npe_visual_preview_type()) return;
    foreach (['npe-pages','npe-checkout','wc-checkout','wc-cart-fragments','nem-script','nem-header-footer','nem-header-footer-script'] as $handle) wp_dequeue_script($handle);
}, PHP_INT_MAX);

add_filter('script_loader_tag', function ($tag) { return npe_visual_preview_type() ? '' : $tag; }, PHP_INT_MAX);

// Keep Edit Page reachable for administrators even with an empty personal cart.
add_filter('woocommerce_checkout_redirect_empty_cart', function ($redirect) { return current_user_can('manage_options') ? false : $redirect; }, 100);

function npe_visual_sample_payments() {
    echo '<div class="woocommerce-checkout-payment"><ul class="nem-payment-options payment_methods methods">';
    foreach (npe_checkout_payment_labels() as $id=>$title) {
        echo '<li class="wc_payment_method"><label class="nem-payment-item"><input type="radio" disabled><span class="nem-payment-icon">'.npe_payment_icon($id).'</span><span class="nem-payment-text">'.esc_html($title).'</span></label></li>';
    }
    echo '</ul></div>';
}

add_action('wp_ajax_npe_visual_save', function () {
    check_ajax_referer('npe_visual_save', 'nonce');
    $id = isset($_POST['post']) && is_scalar($_POST['post']) ? absint($_POST['post']) : 0;
    $type = npe_visual_page_type($id);
    if (!$type || !current_user_can('manage_options') || !current_user_can('edit_post', $id)) wp_send_json_error(['message' => 'Bạn không có quyền sửa trang này.'], 403);
    $raw = isset($_POST['data']) && is_string($_POST['data']) ? wp_unslash($_POST['data']) : '';
    if (strlen($raw) > 200000) wp_send_json_error(['message' => 'Nội dung quá dài.'], 400);
    $data = json_decode($raw, true);
    if (!is_array($data)) wp_send_json_error(['message' => 'Dữ liệu không hợp lệ.'], 400);
    $schema = npe_builder_schema($type);
    if ($type === 'product') foreach (['newsletter_title','newsletter_text'] as $key) $schema[$key] = npe_builder_schema('home')[$key];
    $fields = [];
    foreach ($schema as $key => $field) {
        if (!isset($data['fields']) || !is_array($data['fields']) || !array_key_exists($key, $data['fields'])) continue;
        $value = npe_builder_clean_field($data['fields'][$key], $field);
        if (is_wp_error($value)) wp_send_json_error(['message' => $field['label'] . ': ' . $value->get_error_message()], 400);
        $fields[$key] = $value;
    }
    $product = null;
    if ($type === 'product' && !empty($data['productFields'])) {
        $pid = isset($data['product']) && is_scalar($data['product']) ? absint($data['product']) : 0;
        if (!current_user_can('edit_post', $pid) || !($product = wc_get_product($pid)) || $product->get_status() !== 'publish' || !is_array($data['productFields'])) wp_send_json_error(['message' => 'Không thể sửa sản phẩm này.'], 403);
        foreach (['name','description'] as $key) if (isset($data['productFields'][$key]) && !is_string($data['productFields'][$key])) wp_send_json_error(['message' => 'Nội dung sản phẩm không hợp lệ.'], 400);
        if (isset($data['productFields']['name']) && trim(sanitize_text_field($data['productFields']['name'])) === '') wp_send_json_error(['message' => 'Tên sản phẩm không được để trống.'], 400);
    }
    $allowed = array_keys(npe_builder_sections($type));
    $order = array_values(array_unique(array_merge(array_intersect(array_filter(is_array($data['order'] ?? null) ? $data['order'] : [], 'is_string'), $allowed), $allowed)));
    $hidden = array_values(array_intersect(array_filter(is_array($data['hidden'] ?? null) ? $data['hidden'] : [], 'is_string'), $allowed));
    if ($type === 'checkout') $hidden = array_values(array_diff($hidden, ['contact','address','payment','summary']));
    $all = get_option('npe_page_builder', []);
    if (!is_array($all)) $all = [];
    $old = $all[$type] ?? [];
    $columns = is_array($old['columns'] ?? null) ? $old['columns'] : [];
    foreach ($allowed as $key) if (in_array($data['columns'][$key] ?? '', ['main','sidebar','full'], true)) $columns[$key] = $data['columns'][$key];
    // Keep a bounded restore history before changing the current configuration.
    $history = (array) get_post_meta($id, '_npe_visual_history', true);
    $history[] = ['at' => current_time('mysql'), 'builder' => $old, 'home' => get_option('nem_home_content', []), 'styles' => get_option('npe_visual_styles', [])];
    update_post_meta($id, '_npe_visual_history', array_slice($history, -10));
    $all[$type] = array_merge($old, ['fields' => array_merge($old['fields'] ?? [], $fields), 'order' => $order, 'hidden' => $hidden, 'columns' => $columns]);
    if ($type === 'home') $all[$type]['feature_order'] = npe_visual_feature_order($data['featureOrder'] ?? ($old['feature_order'] ?? []));
    if ($type === 'home') update_option('nem_home_content', npe_builder_update_home($fields));
    if ($type === 'product') {
        $shared = array_intersect_key($fields, array_flip(['newsletter_title','newsletter_text']));
        if ($shared) update_option('nem_home_content', npe_builder_update_home($shared));
    }
    update_option('npe_page_builder', $all, false);
    $styles = get_option('npe_visual_styles', []);
    if (!is_array($styles)) $styles = [];
    $styles[$type] = npe_visual_styles($type, $data['styles'] ?? []);
    update_option('npe_visual_styles', $styles, false);
    if ($product) {
        if (isset($data['productFields']['name'])) $product->set_name(sanitize_text_field($data['productFields']['name']));
        if (isset($data['productFields']['description'])) $product->set_short_description(wp_kses_post($data['productFields']['description']));
        $product->save();
    }
    clean_post_cache($id);
    wp_send_json_success(['message' => 'Đã lưu. Nội dung mới đang hiển thị trên website.']);
});

function npe_visual_editor_screen() {
    if (!current_user_can('manage_options')) return;
    $type = npe_visual_page_type(isset($_GET['post']) && is_scalar($_GET['post']) ? absint($_GET['post']) : 0) ?: 'home';
    $id = npe_visual_pages()[$type];
    if (!$id || !current_user_can('edit_post', $id)) { echo '<div class="wrap"><h1>Chưa nhập trang</h1><p>Cần nhập các trang NEM trước khi chỉnh sửa.</p></div>'; return; }
    $product = npe_visual_product();
    $view = $type === 'home' ? home_url('/') : ($type === 'checkout' ? wc_get_checkout_url() : ($product ? $product->get_permalink() : home_url('/')));
    $preview = add_query_arg(['npe_visual_preview' => $id, '_npe_nonce' => wp_create_nonce('npe_visual_preview_' . $id)], $view);
    $schema = npe_builder_schema($type);
    if ($type === 'product') foreach (['newsletter_title','newsletter_text'] as $key) $schema[$key] = npe_builder_schema('home')[$key];
    $fields = [];
    foreach ($schema as $key => $field) $fields[$key] = $type === 'product' && in_array($key, ['newsletter_title','newsletter_text'], true) ? npe_setting('home', $key, $field['default']) : npe_setting($type, $key, $field['default'] ?? '');
    $styles = get_option('npe_visual_styles', []);
    $settings = npe_page_settings($type);
    wp_enqueue_media();
    wp_enqueue_style('npe-visual-editor', content_url('/mu-plugins/nem-page-experience/visual-editor.css'), [], filemtime(__DIR__.'/visual-editor.css'));
    wp_enqueue_script('npe-visual-editor', content_url('/mu-plugins/nem-page-experience/visual-editor.js'), ['jquery','jquery-ui-sortable','media-editor'], filemtime(__DIR__.'/visual-editor.js'), true);
    wp_localize_script('npe-visual-editor', 'npeVisual', ['type' => $type, 'post' => $id, 'ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('npe_visual_save'), 'targets' => npe_visual_targets($type), 'schema' => $schema, 'fields' => $fields, 'order' => npe_section_order($type, true), 'hidden' => $settings['hidden'], 'columns' => $settings['columns'], 'featureOrder' => npe_visual_feature_order(), 'canvasStyle' => add_query_arg('ver', filemtime(__DIR__.'/visual-canvas.css'), content_url('/mu-plugins/nem-page-experience/visual-canvas.css')), 'styles' => npe_visual_styles($type, $styles[$type] ?? []), 'sections' => npe_builder_sections($type), 'product' => $type === 'product' && $product ? $product->get_id() : 0]);
    ?>
    <div class="nve-shell">
        <header class="nve-toolbar"><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">← All Pages</a><strong>Sửa trang trực quan</strong><nav aria-label="Chọn trang"><?php foreach (['home'=>'Trang chủ','product'=>'Chi tiết sản phẩm','checkout'=>'Thanh toán'] as $key=>$label): ?><a <?php if ($key === $type) echo 'aria-current="page"'; ?> href="<?php echo esc_url(npe_visual_url($key)); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav><select id="nve-device" aria-label="Kích thước xem trước"><option value="100%">Máy tính</option><option value="820px">Máy tính bảng</option><option value="390px">Điện thoại</option></select><a href="<?php echo esc_url($view); ?>" target="_blank" rel="noopener">Xem trang ↗</a><button id="nve-save" class="button button-primary">Lưu thay đổi</button></header>
        <aside class="nve-sidebar"><p class="nve-help">Bấm vào chữ trên trang để sửa. Chọn một dòng chữ để đổi cỡ chữ bên dưới. Kéo tay nắm ngay trên trang để đổi vị trí; ảnh và nội dung có thể kéo đổi bên.</p><p id="nve-status" role="status" aria-live="polite">Đang tải trang…</p>
        <?php if ($type === 'product' && $product): ?><label for="nve-product">Sản phẩm xem trước</label><select id="nve-product"><?php foreach (wc_get_products(['status'=>'publish','limit'=>100,'orderby'=>'title','order'=>'ASC']) as $p): ?><option value="<?php echo (int)$p->get_id(); ?>" <?php selected($p->get_id(),$product->get_id()); ?>><?php echo esc_html($p->get_name()); ?></option><?php endforeach; ?></select><p class="nve-help">Bố cục áp dụng cho mọi sản phẩm. Sửa tên/mô tả chỉ tác động sản phẩm đang xem.</p><a href="<?php echo esc_url(admin_url('post.php?post='.$product->get_id().'&action=edit')); ?>" target="_blank" rel="noopener">Sửa ảnh, giá, màu và size sản phẩm ↗</a><?php endif; ?>
        <details open><summary>Chữ đang chọn</summary><p id="nve-selection">Bấm chọn chữ trên trang.</p><label>Cỡ chữ (px)<input id="nve-font-size" type="number" min="8" max="120" placeholder="Mặc định"></label><label>Độ đậm<select id="nve-font-weight"><option value="">Mặc định</option><option value="400">Thường</option><option value="500">Vừa</option><option value="600">Đậm vừa</option><option value="700">Đậm</option></select></label><label>Màu chữ<input id="nve-color" type="color" value="#222222"></label><label>Căn chữ<select id="nve-align"><option value="">Mặc định</option><option value="left">Trái</option><option value="center">Giữa</option><option value="right">Phải</option></select></label><label>Chiều cao dòng<input id="nve-line-height" type="number" min="0.8" max="3" step="0.1" placeholder="Mặc định"></label><button type="button" id="nve-reset-style" class="button">Khôi phục kiểu chữ mặc định</button></details>
        <details open><summary>Bố cục · kéo để sắp xếp</summary><ol id="nve-sections"></ol></details>
        <details><summary>Nội dung, ảnh và liên kết</summary><div id="nve-fields"></div></details>
        <details><summary>Hướng dẫn</summary><p>Chỉnh chữ ngay trên khung trang hoặc trong mục Nội dung. Chọn / thay ảnh từ Thư viện. Mỗi thay đổi hiện ngay trong bản xem trước. Bấm Lưu thay đổi để áp dụng.</p><p>Trang chủ: kéo các ô ảnh, sản phẩm và bài viết. Chi tiết: kéo ảnh hoặc nội dung để đổi bên. Thanh toán: kéo khối giữa cột trái, cột phải và vùng toàn chiều ngang.</p><p>Chữ có viền xanh có thể sửa nội dung. Giá và dữ liệu danh sách sản phẩm lấy từ WooCommerce; bạn có thể đổi kiểu chữ của chúng.</p><p><a href="<?php echo esc_url(admin_url('admin.php?page=npe-payments')); ?>" target="_blank" rel="noopener">Kết nối ngân hàng, MoMo và thẻ ↗</a></p><p>Trang thanh toán dùng đơn hàng minh họa, không đặt đơn thật. Các khối bắt buộc luôn được giữ.</p><a href="<?php echo esc_url(admin_url('admin.php?page=npe-builder&tab='.$type)); ?>">Mở bảng thiết lập đầy đủ ↗</a></details>
        </aside><main class="nve-workspace"><iframe id="nve-preview" title="Xem và sửa trang <?php echo esc_attr($type); ?>" src="<?php echo esc_url($preview); ?>" sandbox="allow-same-origin allow-scripts"></iframe></main>
    </div>
    <?php
}
