<?php
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('nem-style', get_stylesheet_directory_uri().'/nem.css', ['storefront-style'], '1.0.1');
    wp_enqueue_style('nem-overrides', get_stylesheet_directory_uri().'/overrides.css', ['nem-style'], '1.0.0');
    $nem_js = __DIR__.'/nem.js';
    wp_enqueue_script('nem-script', get_stylesheet_directory_uri().'/nem.js', [], filemtime($nem_js), true);
}, 99);
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_shop') || (!is_shop() && !is_product_taxonomy())) return;
    $shop_css = __DIR__.'/nem-shop.css';
    wp_enqueue_style('nem-shop', get_stylesheet_directory_uri().'/nem-shop.css', ['nem-overrides', 'nem-collections'], filemtime($shop_css));
}, 101);
require_once __DIR__.'/home-admin.php';

function nem_is_shop_archive() {
    return function_exists('is_shop') && (is_shop() || is_product_taxonomy());
}

function nem_shop_selected_category_filters() {
    if (isset($_GET['nem_category'])) {
        $raw = wp_unslash($_GET['nem_category']);
        $values = is_array($raw) ? $raw : [$raw];
        $selected = [];
        foreach ($values as $value) {
            if (is_scalar($value) && ctype_digit((string) $value)) {
                $term_id = absint($value);
                if ($term_id && term_exists($term_id, 'product_cat')) $selected[] = $term_id;
            }
        }
        return array_values(array_unique($selected));
    }
    $queried = get_queried_object();
    return $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy
        ? [(int) $queried->term_id]
        : [];
}

function nem_shop_custom_attribute_values($filter_key) {
    static $values_by_key = null;

    if ($values_by_key === null) {
        $values_by_key = ['size' => [], 'color' => []];
        $products = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => '_product_attributes',
                'compare' => 'EXISTS',
            ]],
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        foreach ($products as $product_id) {
            $attributes = get_post_meta($product_id, '_product_attributes', true);
            if (!is_array($attributes)) continue;

            foreach ($attributes as $attribute) {
                if (!is_array($attribute) || !empty($attribute['is_taxonomy'])) continue;
                $name = remove_accents(strtolower(sanitize_title($attribute['name'] ?? '')));
                $key = preg_match('/^(size|kich-co|kichco)$/', $name)
                    ? 'size'
                    : (preg_match('/^(color|colour|mau-sac|mausac)$/', $name) ? 'color' : null);
                if ($key === null || !isset($attribute['value']) || !is_scalar($attribute['value'])) continue;

                foreach (explode('|', (string) $attribute['value']) as $value) {
                    $value = trim($value);
                    $slug = sanitize_title($value);
                    if ($value !== '' && $slug !== '') $values_by_key[$key][$slug] = $value;
                }
            }
        }

        foreach ($values_by_key as &$values) {
            uasort($values, 'strnatcasecmp');
        }
        unset($values);
    }

    return $values_by_key[$filter_key] ?? [];
}

function nem_shop_filter_categories($query) {
    if (!nem_is_shop_archive()) return;
    $categories = nem_shop_selected_category_filters();
    if ($categories) {
        $tax_query = (array) $query->get('tax_query');
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $categories,
            'operator' => 'IN',
            'include_children' => true,
        ];
        $query->set('tax_query', $tax_query);
    }

    $custom_attribute_filters = [];
    foreach (['size', 'color'] as $filter_key) {
        $parameter = 'nem_attribute_'.$filter_key;
        if (!isset($_GET[$parameter]) || !is_scalar($_GET[$parameter])) continue;

        $values = nem_shop_custom_attribute_values($filter_key);
        $selected_slugs = array_filter(array_map('sanitize_title', explode(',', sanitize_text_field(wp_unslash($_GET[$parameter])))));
        $attribute_name = $filter_key;
        $value_queries = [];
        foreach ($selected_slugs as $slug) {
            if (!isset($values[$slug])) continue;
            $attribute_pattern = preg_quote($attribute_name, '/');
            $value_pattern = preg_quote($values[$slug], '/');
            $value_queries[] = [
                'key' => '_product_attributes',
                'value' => '"'.$attribute_pattern.'";a:[^}]*"value";s:[0-9]+:"([^"]*[|][[:space:]]*)?'.$value_pattern.'([[:space:]]*[|][^"]*)?"',
                'compare' => 'REGEXP',
            ];
        }

        if ($value_queries) {
            $custom_attribute_filters[] = [
                'relation' => 'OR',
                ...$value_queries,
            ];
        }
    }

    if ($custom_attribute_filters) {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'relation' => 'AND',
            ...$custom_attribute_filters,
        ];
        $query->set('meta_query', $meta_query);
    }
}
add_action('woocommerce_product_query', 'nem_shop_filter_categories');

function nem_shop_archive_setup() {
    if (!nem_is_shop_archive()) return;
    remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    remove_action('woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30);
    remove_action('woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31);
    remove_action('woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9);
    remove_action('woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10);
    remove_action('woocommerce_after_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31);
}
add_action('wp', 'nem_shop_archive_setup', 20);

function nem_shop_newsletter() {
    if (nem_is_shop_archive()) get_template_part('template-parts/collection-newsletter');
}
add_action('storefront_before_footer', 'nem_shop_newsletter', 10);

function nem_shop_breadcrumbs($crumbs) {
    if (!is_shop() || !$crumbs) return $crumbs;
    $crumbs[0][0] = 'TRANG CHỦ';
    $last = count($crumbs) - 1;
    $crumbs[$last][0] = 'TẤT CẢ SẢN PHẨM';
    return $crumbs;
}
add_filter('woocommerce_get_breadcrumb', 'nem_shop_breadcrumbs');

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
