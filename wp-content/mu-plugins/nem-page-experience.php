<?php
/**
 * Plugin Name: NEM — Home & Product Experience
 * Description: Independent presentation for the front page and single products only.
 * Version: 1.0.0
 */
defined('ABSPATH') || exit;
require_once __DIR__.'/nem-page-experience/admin.php';
require_once __DIR__.'/nem-page-experience/navigation.php';
require_once __DIR__.'/nem-page-experience/checkout-functions.php';
require_once __DIR__.'/nem-page-experience/payments.php';
function npe_is_page() { return did_action('wp') && function_exists('is_product') && (is_front_page() || is_product()); }
function npe_is_checkout() { return did_action('wp') && function_exists('is_checkout') && is_checkout() && !is_order_received_page(); }
function npe_is_thankyou() { return did_action('wp') && function_exists('is_order_received_page') && is_order_received_page(); }

add_filter('template_include', function ($template) {
    if (npe_is_page()) return __DIR__.'/nem-page-experience/page.php';
    if (npe_is_checkout()) return __DIR__.'/nem-page-experience/checkout.php';
    if (npe_is_thankyou()) return __DIR__.'/nem-page-experience/thankyou.php';
    return $template;
}, 99);

add_filter('woocommerce_checkout_fields', function ($fields) {
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['required'] = false;
    }
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['required'] = false;
    }
    return $fields;
});

add_action('wp_enqueue_scripts', function () {
    if (!npe_is_page()) return;
    wp_dequeue_style('nem-style'); wp_dequeue_style('nem-overrides'); wp_dequeue_script('nem-script');
    wp_dequeue_style('nem-header-footer-style'); wp_dequeue_style('nem-collections');
    $url = content_url('/mu-plugins/nem-page-experience/');
    wp_enqueue_style('npe-pages', $url.'pages.css', [], filemtime(__DIR__.'/nem-page-experience/pages.css'));
    wp_enqueue_script('npe-pages', $url.'pages.js', ['jquery', 'wc-add-to-cart-variation'], filemtime(__DIR__.'/nem-page-experience/pages.js'), true);
}, 999);
add_action('wp', function () {
    if (!npe_is_page()) return;
    add_filter('body_class', function ($classes) { $classes[] = 'npe-experience'; return $classes; });
});
add_action('woocommerce_after_add_to_cart_button', function () {
    global $product;
    if ($product && $product->is_type(['simple', 'variable'])) {
        if ($product->is_type('simple')) echo '<input type="hidden" name="add-to-cart" value="'.esc_attr($product->get_id()).'">';
        echo '<button type="submit" name="npe_buy_now" value="1" class="npe-buy-now button">MUA NGAY</button>';
    }
}, 30);
add_filter('woocommerce_product_single_add_to_cart_text', function ($text) { return 'THÊM VÀO GIỎ'; }, 99);
add_filter('woocommerce_product_is_in_stock', '__return_true', 99);
add_filter('woocommerce_attribute_label', function ($label, $name) {
    if (!npe_is_page()) return $label;
    return in_array($name,['size','pa_size'],true) ? 'Kích thước' : (in_array($name,['color','pa_color'],true) ? 'Màu sắc' : $label);
}, 99, 2);
// Missing reference photos are resolved only while rendering these two pages.
// Attachment records and the original uploads remain unchanged.
add_filter('wp_get_attachment_url', function ($url, $id) {
    if (!npe_is_page()) return $url;
    $path = get_attached_file($id);
    if ($path && !is_file($path)) {
        $name = basename($path);
        if (is_file(__DIR__.'/nem-page-experience/assets/'.$name)) return content_url('/mu-plugins/nem-page-experience/assets/'.rawurlencode($name));
    }
    return $url;
}, 99, 2);
add_filter('wp_calculate_image_srcset', function ($sources, $size, $src) {
    return npe_is_page() && strpos($src, '/nem-page-experience/assets/')!==false ? false : $sources;
}, 99, 3);
add_filter('wp_get_attachment_image_src', function ($image, $id) {
    if (!npe_is_page()) return $image;
    $path = get_attached_file($id); $name = basename((string)$path);
    if ($path && !is_file($path) && is_file(__DIR__.'/nem-page-experience/assets/'.$name)) {
        $meta = wp_get_attachment_metadata($id);
        return [content_url('/mu-plugins/nem-page-experience/assets/'.rawurlencode($name)), $meta['width']??1024, $meta['height']??1536, false];
    }
    return $image;
}, 99, 2);
add_filter('gettext', function ($translated, $text, $domain) {
    if ($domain!=='woocommerce' || !npe_is_page()) return $translated;
    return ['There are no reviews yet.'=>'Chưa có đánh giá nào.', 'Your review'=>'Nội dung đánh giá', 'Your rating'=>'Đánh giá của bạn', 'Name'=>'Họ tên', 'Email'=>'Email', 'Submit'=>'Gửi đánh giá'][$text] ?? $translated;
}, 99, 3);
add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    return isset($_POST['npe_buy_now']) && $_POST['npe_buy_now'] === '1' ? wc_get_checkout_url() : $url;
});
function npe_image($id, $class = '', $eager = false) {
    if ($id) echo wp_get_attachment_image((int) $id, 'full', false, ['class'=>$class, 'loading'=>$eager?'eager':'lazy', 'decoding'=>'async']);
}
function npe_icon($name) {
    $paths = ['search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>', 'bag'=>'<path d="M5 9h14l1 13H4L5 9Z" fill="currentColor" stroke="none"/><path d="M8 10V6a4 4 0 0 1 8 0v4" stroke-width="1.5"/>', 'user'=>'<circle cx="12" cy="12" r="10" stroke-width="1.7"/><circle cx="12" cy="8" r="3.2" fill="currentColor" stroke="none"/><path d="M5.5 19a6.5 6.5 0 0 1 13 0 9 9 0 0 1-13 0Z" fill="currentColor" stroke="none"/>', 'heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>'];
    return '<svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">'.($paths[$name] ?? '').'</svg>';
}
function npe_reference($product) {
    static $data;
    if ($data === null) $data = json_decode((string)@file_get_contents(__DIR__.'/nem-page-experience/reference.json'), true) ?: [];
    $sku = preg_replace('/^NEM-/', '', strtoupper($product->get_sku()));
    return $data[$sku] ?? [];
}
// Simple seed products have no native variants: keep selected reference options
// as item metadata while retaining the shop's own price and stock validation.
function npe_simple_options($product) {
    if (!$product || !$product->is_type('simple')) return;
    $options = npe_reference($product)['options'] ?? [];
    if (!$options) return;
    echo '<table class="variations npe-simple-options"><tbody>';
    wp_nonce_field('npe_options_'.$product->get_id(), 'npe_options_nonce');
    foreach ($options as $index=>$option) {
        echo '<tr><th><label for="npe-option-'.$index.'">'.esc_html($option['name']).'</label></th><td><select id="npe-option-'.$index.'" name="npe_options['.$index.']" required data-npe-color="'.($option['name']==='Màu sắc'?'1':'0').'">';
        foreach ($option['values'] as $value) echo '<option value="'.esc_attr($value).'">'.esc_html($value).'</option>';
        echo '</select></td></tr>';
    }
    echo '</tbody></table>';
}
add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if (npe_is_page()) npe_simple_options($product);
});
function npe_purchase($product) {
    woocommerce_template_single_add_to_cart();
}
function npe_selected_options($id) {
    $p=wc_get_product($id); $reference=$p ? npe_reference($p) : [];
    if (!$p || !$p->is_type('simple') || empty($reference['options'])) return [];
    $posted=isset($_POST['npe_options']) && is_array($_POST['npe_options']) ? wp_unslash($_POST['npe_options']) : [];
    $selected=[];
    foreach ($reference['options'] as $i=>$option) {
        if (isset($posted[$i]) && is_string($posted[$i])) {
            $selected[$option['name']]=$posted[$i];
        } else {
            $selected[$option['name']]=$option['values'][0] ?? '';
        }
    }
    return $selected;
}
add_filter('woocommerce_add_to_cart_validation', '__return_true', 99);
add_filter('woocommerce_add_cart_item_data', function ($data,$id) {
    if ($selected=npe_selected_options($id)) $data['npe_options']=$selected;
    return $data;
},10,2);
add_filter('woocommerce_get_item_data', function ($data,$item) {
    foreach ($item['npe_options'] ?? [] as $key=>$value) $data[]=['key'=>$key,'value'=>$value];
    return $data;
},10,2);
add_action('woocommerce_checkout_create_order_line_item', function ($item,$key,$values) {
    foreach ($values['npe_options'] ?? [] as $label=>$value) $item->add_meta_data($label,$value,true);
},10,3);
function npe_product_photos($product) {
    $photos = [];
    foreach (array_unique(array_merge([$product->get_image_id()], $product->get_gallery_image_ids())) as $id) {
        if ($id && ($url = wp_get_attachment_image_url($id, 'full'))) $photos[] = $url;
    }
    if (count($photos) < 2) {
        $reference = npe_reference($product)['images'] ?? [];
        if ($photos) $reference = array_slice($reference, 1);
        foreach ($reference as $file) {
            if (is_file(__DIR__.'/nem-page-experience/assets/'.basename($file))) $photos[] = content_url('/mu-plugins/nem-page-experience/assets/'.rawurlencode(basename($file)));
        }
    }
    return array_values(array_unique($photos));
}
function npe_secondary_photo($product) {
    $photos = npe_product_photos($product);
    // Reference image 1 is the original catalog pose; image 2 is the alternate pose.
    return $photos[1] ?? '';
}
function npe_description($product) {
    $text = $product->get_short_description() ?: $product->get_description();
    // Only the old seeded placeholder uses the matching SKU's reference presentation.
    if (!$product->get_short_description() && str_contains($text, 'Vui lòng liên hệ cửa hàng để được tư vấn kích cỡ')) $text = (npe_reference($product)['description'] ?? '') ?: $text;
    $html = wp_kses_post(wpautop($text));
    return preg_replace('/(<strong[^>]*>)\s*(Chất liệu|Kiểu dáng|Sản phẩm thuộc dòng(?: sản phẩm)?|Sản phẩm kết hợp)\s*:?\s*(<\/strong>)/u', '$1$2:$3', $html);
}
function npe_card($product, $class = '') {
    if (!$product || !$product->is_visible()) return;
    $gallery = $product->get_gallery_image_ids();
    echo '<article class="npe-card '.esc_attr($class).'" data-product-id="'.esc_attr($product->get_id()).'"><a href="'.esc_url($product->get_permalink()).'"><div class="npe-card-photo">';
    echo $product->get_image('woocommerce_single', ['class'=>'npe-primary']);
    $secondary = npe_secondary_photo($product);
    if ($secondary) echo '<img class="npe-secondary" src="'.esc_url($secondary).'" alt="'.esc_attr($product->get_name()).' — góc chụp khác" loading="lazy" decoding="async">';
    echo '<span class="npe-card-hover" aria-hidden="true"><span>XEM THÊM</span></span>';
    if ($product->is_on_sale() && (float)$product->get_regular_price() > 0) echo '<span class="npe-sale">-'.esc_html(round(100*(1-(float)$product->get_price()/(float)$product->get_regular_price()))).'%</span>';
    echo '</div><h3>'.esc_html($product->get_name()).'</h3></a><div class="npe-card-price">'.wp_kses_post($product->get_price_html()).'</div></article>';
}

// WooCommerce includes spoken price labels. Keep them visually hidden even
// when the theme's accessibility stylesheet is absent or cached.
function nem_accessible_price_labels($html) {
    return str_replace('class="screen-reader-text">', 'class="screen-reader-text" style="position:absolute!important;clip-path:inset(50%)!important;width:1px!important;height:1px!important;overflow:hidden!important;white-space:nowrap!important;padding:0!important;border:0!important">', $html);
}
add_filter('woocommerce_get_price_html', 'nem_accessible_price_labels', 100);
add_filter('woocommerce_format_sale_price', 'nem_accessible_price_labels', 100);

// Cover prices inserted by variations, cart updates, and other product widgets.
add_action('wp_head', function () {
    echo '<style id="nem-price-accessibility">.price .screen-reader-text,.npe-card-price .screen-reader-text,.npe-price .screen-reader-text,.nem-price .screen-reader-text,.woocommerce-Price-amount~.screen-reader-text{position:absolute!important;clip-path:inset(50%)!important;width:1px!important;height:1px!important;overflow:hidden!important;white-space:nowrap!important;padding:0!important;border:0!important}</style>';
}, 100);

// Checkout AJAX Endpoints
add_action('wp_ajax_npe_checkout_update_qty', 'npe_ajax_checkout_update_qty');
add_action('wp_ajax_nopriv_npe_checkout_update_qty', 'npe_ajax_checkout_update_qty');
function npe_ajax_checkout_update_qty() {
    check_ajax_referer('nem_checkout_nonce', 'nonce');
    $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
    $quantity = max(1, absint($_POST['quantity'] ?? 1));

    if ($cart_item_key && WC()->cart) {
        WC()->cart->set_quantity($cart_item_key, $quantity, true);
        WC()->cart->calculate_totals();

        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        $product = $cart_item ? $cart_item['data'] : null;
        $item_subtotal = $product ? WC()->cart->get_product_subtotal($product, $cart_item['quantity']) : '';

        wp_send_json_success([
            'item_subtotal' => $item_subtotal,
            'subtotal_html' => WC()->cart->get_cart_subtotal(),
            'total_html' => WC()->cart->get_total(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ]);
    }
    wp_send_json_error();
}

add_action('wp_ajax_npe_checkout_remove_item', 'npe_ajax_checkout_remove_item');
add_action('wp_ajax_nopriv_npe_checkout_remove_item', 'npe_ajax_checkout_remove_item');
function npe_ajax_checkout_remove_item() {
    check_ajax_referer('nem_checkout_nonce', 'nonce');
    $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');

    if ($cart_item_key && WC()->cart) {
        WC()->cart->remove_cart_item($cart_item_key);
        WC()->cart->calculate_totals();

        wp_send_json_success([
            'subtotal_html' => WC()->cart->get_cart_subtotal(),
            'total_html' => WC()->cart->get_total(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ]);
    }
    wp_send_json_error();
}

add_action('wp_ajax_npe_checkout_apply_coupon', 'npe_ajax_checkout_apply_coupon');
add_action('wp_ajax_nopriv_npe_checkout_apply_coupon', 'npe_ajax_checkout_apply_coupon');
function npe_ajax_checkout_apply_coupon() {
    check_ajax_referer('nem_checkout_nonce', 'nonce');
    $code = sanitize_text_field($_POST['coupon_code'] ?? '');

    if ($code && WC()->cart) {
        if (!WC()->cart->has_discount($code)) {
            $applied = WC()->cart->apply_coupon($code);
            if ($applied) {
                WC()->cart->calculate_totals();
                wp_send_json_success([
                    'message' => 'Áp dụng mã giảm giá thành công!',
                    'subtotal_html' => WC()->cart->get_cart_subtotal(),
                    'total_html' => WC()->cart->get_total(),
                    'discount_html' => '-' . wc_price(WC()->cart->get_discount_total()),
                ]);
            } else {
                wp_send_json_error(['message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.']);
            }
        } else {
            wp_send_json_error(['message' => 'Mã giảm giá này đã được áp dụng.']);
        }
    }
    wp_send_json_error(['message' => 'Vui lòng nhập mã giảm giá.']);
}

