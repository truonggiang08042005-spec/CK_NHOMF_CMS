<?php
defined('ABSPATH') || exit;

get_header('shop');

$shop_filter_attributes = ['size' => null, 'color' => null];
if (function_exists('wc_get_attribute_taxonomies')) {
    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $attribute_name = sanitize_title($attribute->attribute_name);
        $normalized = remove_accents(strtolower($attribute_name));
        $filter_key = null;

        if (preg_match('/^(size|kich-co|kichco)$/', $normalized)) {
            $filter_key = 'size';
        } elseif (preg_match('/^(color|colour|mau-sac|mausac)$/', $normalized)) {
            $filter_key = 'color';
        }

        $prefer_color = $filter_key === 'color' && $normalized === 'color';
        if ($filter_key === null || ($shop_filter_attributes[$filter_key] !== null && !$prefer_color)) continue;

        $taxonomy = wc_attribute_taxonomy_name($attribute_name);
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'term_id',
            'order' => 'ASC',
        ]);
        if (!is_wp_error($terms)) {
            $shop_filter_attributes[$filter_key] = [
                'name' => $attribute_name,
                'parameter' => 'filter_' . $attribute_name,
                'terms' => $terms,
            ];
        }
    }
}

foreach (['size', 'color'] as $filter_key) {
    $custom_values = nem_shop_custom_attribute_values($filter_key);
    if (!$custom_values) continue;

    $terms = [];
    foreach ($custom_values as $slug => $name) {
        $terms[] = (object) [
            'slug' => $slug,
            'name' => $name,
            'term_id' => 0,
        ];
    }
    $shop_filter_attributes[$filter_key] = [
        'name' => $filter_key,
        'parameter' => 'nem_attribute_' . $filter_key,
        'terms' => $terms,
    ];
}

$shop_url = wc_get_page_permalink('shop');
$shop_filter_args = [];
foreach ($_GET as $key => $value) {
    if (!is_scalar($value)) continue;
    if (preg_match('/^(filter_[a-z0-9_-]+|query_type_[a-z0-9_-]+|nem_attribute_(size|color))$/', (string) $key)) {
        $shop_filter_args[sanitize_key($key)] = sanitize_text_field(wp_unslash($value));
    }
}
foreach (['min_price', 'max_price'] as $price_key) {
    if (isset($_GET[$price_key]) && is_scalar($_GET[$price_key])) {
        $shop_filter_args[$price_key] = absint(wp_unslash($_GET[$price_key]));
    }
}
$selected_categories = nem_shop_selected_category_filters();
$shop_filter_args['nem_category'] = $selected_categories ?: null;
$build_filter_url = function ($changes = []) use ($shop_url, $shop_filter_args) {
    $args = array_merge($shop_filter_args, $changes);
    foreach ($args as $key => $value) {
        if ($value === null || $value === '') unset($args[$key]);
    }
    return add_query_arg($args, $shop_url);
};

do_action('woocommerce_before_main_content');

if (is_shop()) :
?>
    <a class="nem-shop-banner" href="<?php echo esc_url($shop_url); ?>" aria-label="<?php esc_attr_e('Bộ sưu tập Tailor Vietnamese Elegance', 'storefront'); ?>">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/shop-tailor-banner.jpg'); ?>" alt="<?php esc_attr_e('Tailor Vietnamese Elegance', 'storefront'); ?>" fetchpriority="high">
    </a>
<?php
endif;

$category_terms = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);
$category_terms = is_wp_error($category_terms) ? [] : array_values(array_filter($category_terms, function ($term) {
    return $term->slug !== 'uncategorized';
}));
$render_categories = function ($parent_id) use (&$render_categories, $category_terms, $selected_categories, $build_filter_url) {
    $children = array_filter($category_terms, function ($term) use ($parent_id) {
        return (int) $term->parent === (int) $parent_id;
    });
    if (!$children) return;
    echo '<ul class="product-categories">';
    foreach ($children as $term) {
        $term_id = (int) $term->term_id;
        $selected = in_array($term_id, $selected_categories, true);
        $has_children = (bool) array_filter($category_terms, function ($child) use ($term_id) {
            return (int) $child->parent === $term_id;
        });
        $next_category = $selected ? null : $term_id;
        printf(
            '<li class="%s"><a class="nem-shop-category-option%s%s" href="%s" aria-pressed="%s">%s</a>',
            trim(($selected ? 'current-cat ' : '') . ($has_children ? 'nem-shop-category-parent' : '')),
            $selected ? ' is-selected' : '',
            $has_children ? ' has-children' : '',
            esc_url($build_filter_url(['nem_category' => $next_category ?: null])),
            $selected ? 'true' : 'false',
            esc_html($term->name)
        );
        $render_categories($term_id);
        echo '</li>';
    }
    echo '</ul>';
};

$shop_archive_title = __('Tất cả sản phẩm', 'storefront');
if (is_product_category() || is_product_tag()) {
    $queried_term = get_queried_object();
    if ($queried_term instanceof WP_Term) {
        if (is_product_category() && $queried_term->parent) {
            $parent_term = get_term($queried_term->parent, 'product_cat');
            if ($parent_term instanceof WP_Term && !is_wp_error($parent_term)) $queried_term = $parent_term;
        }
        $shop_archive_title = $queried_term->name;
    }
}
if ($selected_categories) {
    $selected_category_names = [];
    foreach ($selected_categories as $category_id) {
        $category = get_term($category_id, 'product_cat');
        if ($category instanceof WP_Term && !is_wp_error($category)) {
            $selected_category_names[] = $category->name;
        }
    }
    if ($selected_category_names) {
        $shop_archive_title = implode(', ', $selected_category_names);
    }
}
$price_ranges = [
    ['label' => __('Tất cả', 'storefront'), 'min' => null, 'max' => null],
    ['label' => __('Nhỏ hơn 500,000₫', 'storefront'), 'min' => null, 'max' => 499999],
    ['label' => __('Từ 500,000₫ - 1,000,000₫', 'storefront'), 'min' => 500000, 'max' => 999999],
    ['label' => __('Từ 1,000,000₫ - 1,500,000₫', 'storefront'), 'min' => 1000000, 'max' => 1499999],
    ['label' => __('Từ 1,500,000₫ - 2,000,000₫', 'storefront'), 'min' => 1500000, 'max' => 1999999],
    ['label' => __('Từ 2,000,000₫ - 3,000,000₫', 'storefront'), 'min' => 2000000, 'max' => 2999999],
    ['label' => __('Lớn hơn 3,000,000₫', 'storefront'), 'min' => 3000001, 'max' => null],
];
$current_min = isset($_GET['min_price']) && is_scalar($_GET['min_price']) ? absint(wp_unslash($_GET['min_price'])) : null;
$current_max = isset($_GET['max_price']) && is_scalar($_GET['max_price']) ? absint(wp_unslash($_GET['max_price'])) : null;
$has_price = $current_min !== null || $current_max !== null;
?>
<div class="nem-shop-layout">
    <aside class="nem-shop-sidebar" aria-label="<?php esc_attr_e('Danh mục sản phẩm', 'storefront'); ?>">
        <h2><?php esc_html_e('Danh mục', 'storefront'); ?></h2>
        <nav class="nem-shop-category-tree" aria-label="<?php esc_attr_e('Danh mục sản phẩm', 'storefront'); ?>">
            <a class="nem-shop-all-categories<?php echo $selected_categories ? '' : ' is-current'; ?>" href="<?php echo esc_url($build_filter_url(['nem_category' => null])); ?>">
                <?php esc_html_e('Tất cả sản phẩm', 'storefront'); ?>
            </a>
            <?php $render_categories(0); ?>
        </nav>
    </aside>

    <section class="nem-shop-main" data-columns="3" aria-label="<?php esc_attr_e('Danh sách sản phẩm', 'storefront'); ?>">
        <div class="nem-shop-toolbar">
            <h1 class="nem-shop-toolbar-title"><?php echo esc_html($shop_archive_title); ?></h1>
            <div class="nem-shop-controls">
                <div class="nem-shop-filters" aria-label="<?php esc_attr_e('Lọc sản phẩm', 'storefront'); ?>">
                    <?php foreach (['size', 'color'] as $filter_key) : ?>
                        <?php if (!empty($shop_filter_attributes[$filter_key]['terms'])) : ?>
                            <?php
                            $filter_attribute = $shop_filter_attributes[$filter_key];
                            $filter_parameter = $filter_attribute['parameter'];
                            $selected_terms = isset($shop_filter_args[$filter_parameter])
                                ? array_filter(array_map('sanitize_title', explode(',', $shop_filter_args[$filter_parameter])))
                                : [];
                            ?>
                            <div class="nem-shop-filter" data-shop-filter data-filter-key="<?php echo esc_attr($filter_key); ?>">
                                <button class="nem-shop-filter-toggle<?php echo $selected_terms ? ' is-selected' : ''; ?>" type="button" aria-expanded="false">
                                    <?php echo $filter_key === 'size' ? esc_html__('Kích cỡ', 'storefront') : esc_html__('Màu sắc', 'storefront'); ?>
                                    <span aria-hidden="true"></span>
                                </button>
                                <div class="nem-shop-filter-panel<?php echo $filter_key === 'color' ? ' nem-shop-filter-panel--colors' : ''; ?>" hidden>
                                    <?php foreach ($filter_attribute['terms'] as $term) : ?>
                                        <?php
                                        $is_selected = in_array($term->slug, $selected_terms, true);
                                        $next_terms = $is_selected
                                            ? array_values(array_diff($selected_terms, [$term->slug]))
                                            : array_values(array_unique(array_merge($selected_terms, [$term->slug])));
                                        $changes = $next_terms
                                            ? [$filter_parameter => implode(',', $next_terms)]
                                            : [$filter_parameter => null];
                                        if (strpos($filter_parameter, 'filter_') === 0) {
                                            $query_type_parameter = 'query_type_' . $filter_attribute['name'];
                                            $changes[$query_type_parameter] = $next_terms ? 'or' : null;
                                        }
                                        $filter_url = $build_filter_url($changes);
                                        ?>
                                        <?php if ($filter_key === 'color') : ?>
                                            <?php
                                            $swatch_color = sanitize_hex_color(get_term_meta($term->term_id, 'product_attribute_color', true));
                                            $swatch_fallbacks = [
                                                'light-blue' => '#5ba9d6',
                                                'red' => '#e53935',
                                                'do' => '#e53935',
                                                'light-pink' => '#edc5e5',
                                                'yellow' => '#f1dc78',
                                                'vang' => '#f1dc78',
                                                'white' => '#ffffff',
                                                'black' => '#111111',
                                                'den' => '#111111',
                                                'olive' => '#8d792d',
                                                'gray' => '#999999',
                                                'xam' => '#999999',
                                                'nau' => '#8d6e63',
                                                'mint-green' => '#78d98a',
                                                'orange' => '#f28c28',
                                                'pink' => '#ed91c5',
                                                'blue-stripe' => '#5ba9d6',
                                                'blue-pattern' => '#5ba9d6',
                                            ];
                                            $swatch_color = $swatch_color ?: ($swatch_fallbacks[$term->slug] ?? null);
                                            $swatch_color = $swatch_color ?: '#cccccc';
                                            ?>
                                            <a class="nem-shop-color-option<?php echo $is_selected ? ' is-selected' : ''; ?>" href="<?php echo esc_url($filter_url); ?>" data-filter-parameter="<?php echo esc_attr($filter_parameter); ?>" data-term-slug="<?php echo esc_attr($term->slug); ?>" aria-label="<?php echo esc_attr($term->name); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
                                                <span aria-hidden="true" style="background-color: <?php echo esc_attr($swatch_color); ?>;"></span>
                                            </a>
                                        <?php else : ?>
                                            <a class="nem-shop-size-option<?php echo $is_selected ? ' is-selected' : ''; ?>" href="<?php echo esc_url($filter_url); ?>" data-filter-parameter="<?php echo esc_attr($filter_parameter); ?>" data-term-slug="<?php echo esc_attr($term->slug); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
                                                <?php echo esc_html($term->name); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="nem-shop-filter" data-shop-filter data-filter-key="price">
                        <button class="nem-shop-filter-toggle<?php echo $has_price ? ' is-selected' : ''; ?>" type="button" aria-expanded="false">
                            <?php esc_html_e('Giá', 'storefront'); ?>
                            <span aria-hidden="true"></span>
                        </button>
                        <div class="nem-shop-filter-panel nem-shop-filter-panel--prices" hidden>
                            <?php foreach ($price_ranges as $range) : ?>
                                <?php
                                $is_selected = $current_min === $range['min'] && $current_max === $range['max'];
                                $changes = ['min_price' => $range['min'], 'max_price' => $range['max']];
                                $price_url = $build_filter_url($changes);
                                ?>
                                <a class="nem-shop-price-option<?php echo $is_selected ? ' is-selected' : ''; ?>" href="<?php echo esc_url($price_url); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
                                    <span aria-hidden="true"></span>
                                    <?php echo esc_html($range['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="nem-shop-view-switcher" role="group" aria-label="<?php esc_attr_e('Cách hiển thị sản phẩm', 'storefront'); ?>">
                    <button class="nem-shop-view-button is-active" type="button" data-columns="3" aria-label="<?php esc_attr_e('Hiển thị 3 sản phẩm mỗi hàng', 'storefront'); ?>" aria-pressed="true">
                        <svg class="nem-shop-view-icon nem-shop-view-icon--three" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M1 1h4v4H1zm5 0h4v4H6zm5 0h4v4h-4zM1 6h4v4H1zm5 0h4v4H6zm5 0h4v4h-4zM1 11h4v4H1zm5 0h4v4H6zm5 0h4v4h-4z" />
                        </svg>
                    </button>
                    <button class="nem-shop-view-button" type="button" data-columns="2" aria-label="<?php esc_attr_e('Hiển thị 2 sản phẩm mỗi hàng', 'storefront'); ?>" aria-pressed="false">
                        <svg class="nem-shop-view-icon nem-shop-view-icon--two" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M1 1h6.5v6.5H1zm7.5 0H15v6.5H8.5zM1 8.5h6.5V15H1zm7.5 0H15V15H8.5z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <p class="nem-shop-filter-status screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
        <div class="nem-shop-results">
            <?php if (woocommerce_product_loop()) : ?>
                <?php do_action('woocommerce_before_shop_loop'); ?>
                <?php woocommerce_product_loop_start(); ?>
                <?php if (wc_get_loop_prop('total')) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <?php do_action('woocommerce_shop_loop'); ?>
                        <?php wc_get_template_part('content', 'product'); ?>
                    <?php endwhile; ?>
                <?php endif; ?>
                <?php woocommerce_product_loop_end(); ?>
                <?php do_action('woocommerce_after_shop_loop'); ?>
            <?php else : ?>
                <p class="nem-shop-no-results"><?php esc_html_e('Không có sản phẩm phù hợp với yêu cầu', 'storefront'); ?></p>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php
do_action('woocommerce_after_main_content');
do_action('woocommerce_sidebar');
get_footer('shop');
