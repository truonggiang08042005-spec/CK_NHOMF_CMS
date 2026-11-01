<?php
/** Isolated navigation contract checks; no WordPress database or live orders. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', dirname(__DIR__) . '/');
$hooks = [];
function add_action($name, $callback, $priority = 10) { global $hooks; $hooks[$name][] = $callback; }
function add_query_arg($key, $value = null, $url = null) {
    if (is_array($key)) { $args = $key; $url = $value; } else $args = [$key => $value];
    return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($args);
}
function home_url($path = '') { return 'https://shop.test' . $path; }
function wc_get_page_permalink($name) { return home_url('/shop/'); }
function get_page_by_path($slug) { return null; }
function get_terms($args) {
    if (isset($args['parent'])) return [];
    return [(object) ['name' => 'Đầm', 'slug' => 'dam', 'term_id' => 10]];
}
function is_wp_error($value) { return false; }
function sanitize_title($value) {
    $value = str_replace(['Đ', 'đ', 'ầ', 'Á', 'á', 'ạ', 'ự', 'Ớ', 'ớ'], ['D', 'd', 'a', 'A', 'a', 'a', 'u', 'O', 'o'], $value);
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/u', '-', $value), '-'));
}
function get_term_link($term) { return home_url('/product-category/' . $term->slug . '/'); }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function get_nav_menu_locations() { return []; }
function wp_get_nav_menu_items($id) { return []; }
function wc_get_product_ids_on_sale() { return [11, 12, 13]; }
function absint($value) { return abs((int) $value); }
function wp_unslash($value) { return stripslashes($value); }
function sanitize_text_field($value) { return strip_tags($value); }
require dirname(__DIR__) . '/wp-content/mu-plugins/nem-page-experience/navigation.php';
function nav_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL); exit(1); }
}
$defaults = npe_nav_defaults();
nav_check(count($defaults) === 5, 'five top-level reference links');
nav_check(array_column($defaults[0]['children'], 'title') === ['Đầm', 'Áo', 'Áo khoác', 'Áo dài', 'Chân váy', 'Quần', 'Set bộ'], 'reference category order');
nav_check($defaults[0]['children'][0]['url'] === 'https://shop.test/product-category/dam/', 'existing taxonomy gets its real URL');
nav_check(str_contains($defaults[0]['children'][1]['url'], 'post_type=product'), 'missing taxonomy gets product search');
nav_check(count($defaults[4]['children']) === 5, 'five requested collections');

$custom = [
    (object) ['ID' => 2, 'menu_item_parent' => 1, 'title' => 'Child', 'url' => home_url('/child/'), 'target' => '', 'current' => false],
    (object) ['ID' => 1, 'menu_item_parent' => 0, 'title' => 'Parent <script>', 'url' => home_url('/parent/'), 'target' => '_blank', 'current' => true],
    (object) ['ID' => 3, 'menu_item_parent' => 99, 'title' => 'Orphan', 'url' => home_url('/orphan/'), 'target' => '', 'current' => false],
];
$tree = npe_nav_menu_tree($custom);
nav_check(count($tree) === 1 && $tree[0]['children'][0]['title'] === 'Child', 'custom menu nesting and orphan handling');
ob_start(); npe_nav_render_items($tree); $html = ob_get_clean();
nav_check(str_contains($html, 'Parent &lt;script&gt;') && !str_contains($html, '<script>'), 'admin menu title escaped');
nav_check(str_contains($html, 'rel="noopener noreferrer"'), 'external target protected');
nav_check(str_contains($html, 'aria-controls="npe-submenu-') && str_contains($html, 'aria-expanded="false"'), 'submenu button semantics');
nav_check(str_contains($html, 'aria-current="page"'), 'current item semantics');

$legacy = '<header><a class="logo">Logo</a><nav class="nem-desktop-nav" role="navigation"><ul><li>OLD DESKTOP</li></ul></nav><div class="actions">Search and cart</div><div class="nem-drawer-body"><ul class="nem-mobile-nav-list"><li>OLD MOBILE<ul><li>Nested</li></ul></li></ul><div class="nem-drawer-footer">Account</div></div></header>';
$shared_header = npe_nav_legacy_header_markup($legacy);
nav_check(!str_contains($shared_header, 'OLD DESKTOP') && !str_contains($shared_header, 'OLD MOBILE'), 'desktop and nested mobile markup replaced');
nav_check(str_contains($shared_header, 'class="logo">Logo') && str_contains($shared_header, 'Search and cart') && str_contains($shared_header, 'nem-drawer-footer">Account'), 'existing header layout and actions preserved');
nav_check(str_contains($shared_header, 'id="npe-navigation"') && str_contains($shared_header, 'id="npe-mobile-navigation"'), 'desktop and drawer have distinct IDs');

$query = new class {
    public $ids = [12, 14];
    public function get($key) { return $this->ids; }
    public function set($key, $value) { $this->ids = $value; }
};
$_GET['npe_sale'] = '1';
$hooks['woocommerce_product_query'][0]($query);
nav_check(array_values($query->ids) === [12], 'sale filter preserves other product restrictions');
$_GET['npe_sale'] = ['invalid'];
$query->ids = [14];
$hooks['woocommerce_product_query'][0]($query);
nav_check($query->ids === [14], 'malformed sale query ignored');
echo 'PASS: reference navigation, catalogue URLs, editable menu nesting, escaping, accessibility markup, shared storefront header and sale filters.' . PHP_EOL;
