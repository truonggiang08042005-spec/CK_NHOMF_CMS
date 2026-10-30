<?php
/** Run with PHP CLI. No WordPress bootstrap or database writes. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', dirname(__DIR__) . '/');
class WP_Error {
    private $message;
    public function __construct($code, $message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_action() {}
function get_option($key, $default = false) { return $GLOBALS['fixtures'][$key] ?? $default; }
function home_url($path = '') { return 'https://shop.example' . $path; }
function absint($value) { return abs((int) $value); }
function sanitize_text_field($value) { return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags((string) $value))); }
function sanitize_textarea_field($value) { return trim(strip_tags((string) $value)); }
function esc_url_raw($value, $protocols = null) { return preg_match('~^https?://[^\s]+$~', $value) ? $value : ''; }
function get_post_type($id) { return [10 => 'product', 11 => 'product', 20 => 'post', 30 => 'attachment', 31 => 'attachment'][$id] ?? false; }
function wp_attachment_is_image($id) { return (int) $id === 30; }
$GLOBALS['fixtures'] = [
    'nem_home_content' => [
        'images' => ['hero' => 30, 'custom_logo' => 101],
        'features' => [['title' => 'Look 1', 'url' => 'https://shop.example/look']],
        'sections' => [['title' => 'Mới', 'products' => [10, 11]], ['title' => 'Plus', 'products' => [11]]],
        'posts' => [20],
        'unrelated_value' => ['retain' => true],
    ],
    'npe_page_builder' => [
        'home' => ['order' => ['blog', 'hero', 'not-a-real-section', 'blog'], 'hidden' => ['features']],
        'product' => ['fields' => ['brand' => 'NEM CUSTOM'], 'order' => ['reviews', 'details'], 'hidden' => ['related']],
        'checkout' => ['order' => ['payment', 'contact'], 'hidden' => ['payment', 'summary', 'promo'], 'columns' => ['payment' => 'sidebar', 'contact' => 'invalid', 'promo' => 'full']],
    ],
];
require ABSPATH . 'wp-content/mu-plugins/nem-page-experience/admin.php';
$checks = 0;
function check($condition, $message) {
    global $checks;
    $checks++;
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL); exit(1); }
}

check(npe_builder_clean_field('<strong>NEM</strong>', []) === 'NEM', 'HTML removed from editable labels');
check(npe_builder_clean_field("Dòng 1\nDòng 2", ['type' => 'textarea']) === "Dòng 1\nDòng 2", 'Multiline content is retained');
check(is_wp_error(npe_builder_clean_field(['nested'], [])), 'Nested input rejected');
check(is_wp_error(npe_builder_clean_field('javascript:alert(1)', ['type' => 'url'])), 'Unsafe URL protocol rejected');
check(npe_builder_clean_field('https://shop.example/sale', ['type' => 'url']) === 'https://shop.example/sale', 'HTTPS link accepted');
check(npe_builder_clean_field('30', ['type' => 'image']) === 30, 'Image attachment accepted');
check(is_wp_error(npe_builder_clean_field('31', ['type' => 'image'])), 'Non-image attachment rejected');
check(is_wp_error(npe_builder_clean_field('-30', ['type' => 'image'])), 'Negative image ID rejected');
check(is_wp_error(npe_builder_clean_field('10, <script>', ['type' => 'products'])), 'Mixed invalid product IDs rejected atomically');
check(is_wp_error(npe_builder_clean_field('20', ['type' => 'products'])), 'Post ID cannot be used as a product ID');
check(npe_builder_clean_field('11,10,11', ['type' => 'products']) === [11, 10], 'Product selection order preserved without duplicates');
check(npe_builder_clean_field('', ['type' => 'posts']) === [], 'Editorial groups may be emptied');
check(is_wp_error(npe_builder_clean_field('other', ['type' => 'select', 'choices' => ['left' => 'Left', 'right' => 'Right']])), 'Unknown select choice rejected');
$home_order = npe_section_order('home');
check(array_slice($home_order, 0, 2) === ['blog', 'hero'], 'Custom homepage order used');
check(count(array_keys($home_order, 'blog', true)) === 1, 'Duplicate blocks removed');
check(!in_array('not-a-real-section', $home_order, true), 'Unknown block excluded');
check(!in_array('features', $home_order, true), 'Hidden block excluded');
check(in_array('features', npe_section_order('home', true), true), 'Hidden block remains editable');
check(in_array('products_0', $home_order, true) && in_array('products_1', $home_order, true), 'Unlisted existing product groups appended');
$checkout_order = npe_section_order('checkout');
check(in_array('payment', $checkout_order, true) && in_array('summary', $checkout_order, true), 'Essential checkout blocks cannot be hidden');
check(!in_array('promo', $checkout_order, true), 'Optional checkout block can be hidden');
check(npe_section_column('checkout', 'payment') === 'sidebar', 'Block can move to sidebar');
check(npe_section_column('checkout', 'promo') === 'full', 'Block can span full width');
check(npe_section_column('checkout', 'contact') === 'main', 'Invalid column falls back safely');
check(npe_section_column('checkout', 'summary') === 'sidebar', 'Summary defaults to sidebar');
check(npe_setting('product', 'brand', 'NEM') === 'NEM CUSTOM', 'Product content overrides used');
check(npe_setting('product', 'missing', 'Default') === 'Default', 'Default content retained');
check(npe_setting('home', 'image_hero', 0) === 30, 'Existing homepage image source retained');
$merged = npe_builder_update_home(['image_hero' => 0, 'section_0_title' => 'Hàng mới', 'section_0_products' => [11], 'feature_0_title' => 'Mẫu mới', 'newsletter_text' => 'Đăng ký']);
check($merged['images']['hero'] === 0 && $merged['images']['custom_logo'] === 101, 'Image edits preserve unseen image keys');
check($merged['sections'][0] === ['title' => 'Hàng mới', 'products' => [11]], 'Group title/products update together');
check($merged['sections'][1] === $GLOBALS['fixtures']['nem_home_content']['sections'][1], 'Other product group retained');
check($merged['unrelated_value'] === ['retain' => true], 'Unrelated home content preserved');
$rendered = [];
npe_render_sections('product', [
    'details' => function () use (&$rendered) { $rendered[] = 'details'; },
    'reviews' => function () use (&$rendered) { $rendered[] = 'reviews'; },
    'related' => function () use (&$rendered) { $rendered[] = 'related'; },
]);
check($rendered === ['reviews', 'details'], 'Renderer respects selected order and visibility');
echo 'PASS: ' . $checks . " page-builder validation, preservation and layout checks.\n";
