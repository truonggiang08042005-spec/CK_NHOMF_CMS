<?php
/**
 * Plugin Name: NEM — Home & Product Experience
 * Description: Independent presentation for the front page and single products only.
 * Version: 1.0.0
 */
defined('ABSPATH') || exit;
function npe_is_page() { return did_action('wp') && function_exists('is_product') && (is_front_page() || is_product()); }
add_filter('template_include', function ($template) {
    return npe_is_page() ? __DIR__.'/nem-page-experience/page.php' : $template;
}, 99);
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
    if (is_product()) add_action('woocommerce_after_add_to_cart_button', function () {
        global $product;
        if ($product && $product->is_type(['simple', 'variable'])) {
            if ($product->is_type('simple')) echo '<input type="hidden" name="add-to-cart" value="'.esc_attr($product->get_id()).'">';
            echo '<button type="submit" name="npe_buy_now" value="1" class="npe-buy-now button">MUA NGAY</button>';
        }
    });
});
add_filter('woocommerce_product_single_add_to_cart_text', function ($text) { return npe_is_page() ? 'THÊM VÀO GIỎ' : $text; });
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
    wp_nonce_field('npe_options_'.$product->get_id(), 'npe_options_nonce');
    echo '<table class="variations npe-simple-options"><tbody>';
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
    if (!$product->is_type('simple') || $product->is_in_stock()) {woocommerce_template_single_add_to_cart();return;}
    echo '<form class="cart npe-unavailable" onsubmit="return false">';
    npe_simple_options($product);
    echo '<div class="quantity"><input class="qty" id="npe-unavailable-quantity" type="number" min="1" value="1" disabled aria-label="Số lượng"></div><button type="button" class="single_add_to_cart_button button" disabled>THÊM VÀO GIỎ</button><button type="button" class="npe-buy-now button" disabled>MUA NGAY</button><p class="stock out-of-stock" role="status">Sản phẩm hiện hết hàng.</p></form>';
}
function npe_selected_options($id) {
    $p=wc_get_product($id); $reference=$p ? npe_reference($p) : [];
    if (!$p || !$p->is_type('simple') || empty($reference['options'])) return [];
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['npe_options_nonce'] ?? '')), 'npe_options_'.$id)) return false;
    $posted=isset($_POST['npe_options']) && is_array($_POST['npe_options']) ? wp_unslash($_POST['npe_options']) : [];
    $selected=[];
    foreach ($reference['options'] as $i=>$option) {
        if (!isset($posted[$i]) || !is_string($posted[$i]) || !in_array($posted[$i], $option['values'], true)) return false;
        $selected[$option['name']]=$posted[$i];
    }
    return $selected;
}
add_filter('woocommerce_add_to_cart_validation', function ($valid,$id) {
    if (isset($_POST['npe_options_nonce']) && npe_selected_options($id)===false) {wc_add_notice('Vui lòng chọn lại kích thước và màu sắc hợp lệ.', 'error'); return false;}
    return $valid;
},10,2);
add_filter('woocommerce_add_cart_item_data', function ($data,$id) {
    if (isset($_POST['npe_options_nonce']) && ($selected=npe_selected_options($id))) $data['npe_options']=$selected;
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
