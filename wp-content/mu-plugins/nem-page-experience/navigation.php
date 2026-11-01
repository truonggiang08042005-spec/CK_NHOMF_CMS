<?php
/** Editable, accessible NEM catalogue navigation. */
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    register_nav_menu('npe-primary', 'NEM — Menu chính và danh mục');
});

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    $base = __DIR__ . '/';
    $url = content_url('/mu-plugins/nem-page-experience/');
    wp_enqueue_style('npe-navigation', $url . 'navigation.css', [], filemtime($base . 'navigation.css'));
    wp_enqueue_script('npe-navigation', $url . 'navigation.js', [], filemtime($base . 'navigation.js'), true);
}, 1100);

function npe_nav_shop_url() {
    return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
}

function npe_nav_page_url($slug, $fallback) {
    $page = get_page_by_path($slug);
    return $page && get_post_status($page) === 'publish' ? get_permalink($page) : $fallback;
}

function npe_nav_category($name) {
    static $terms;
    if ($terms === null) {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_wp_error($terms)) $terms = [];
    }
    $needle = sanitize_title($name);
    foreach ($terms as $term) {
        if ($term->slug === $needle || sanitize_title($term->name) === $needle) return $term;
    }
    return null;
}

function npe_nav_catalogue_link($name) {
    $term = npe_nav_category($name);
    if ($term) {
        $url = get_term_link($term);
        if (!is_wp_error($url)) return $url;
    }
    return add_query_arg(['s' => $name, 'post_type' => 'product'], home_url('/'));
}

function npe_nav_category_children($term, $depth = 0) {
    if (!$term || $depth >= 3) return [];
    $terms = get_terms(['taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => false]);
    if (is_wp_error($terms)) return [];
    $children = [];
    foreach ($terms as $child) {
        $url = get_term_link($child);
        if (is_wp_error($url)) continue;
        $children[] = ['title' => $child->name, 'url' => $url, 'children' => npe_nav_category_children($child, $depth + 1)];
    }
    return $children;
}

function npe_nav_defaults() {
    $shop = npe_nav_shop_url();
    $products = [];
    $fallback_children = [
        'Đầm' => ['Đầm suông', 'Đầm xòe', 'Đầm ôm'],
        'Áo' => ['Áo sơ mi', 'Áo kiểu', 'Áo thun'],
        'Áo khoác' => ['Áo vest', 'Áo khoác dạ', 'Áo khoác nhẹ'],
        'Chân váy' => ['Chân váy chữ A', 'Chân váy dài', 'Chân váy ngắn'],
        'Quần' => ['Quần dài', 'Quần short', 'Quần jeans'],
    ];
    foreach (['Đầm', 'Áo', 'Áo khoác', 'Áo dài', 'Chân váy', 'Quần', 'Set bộ'] as $label) {
        $children = npe_nav_category_children(npe_nav_category($label));
        if (!$children) {
            foreach ($fallback_children[$label] ?? [] as $child) {
                $children[] = ['title' => $child, 'url' => npe_nav_catalogue_link($child), 'children' => []];
            }
        }
        $products[] = ['title' => $label, 'url' => npe_nav_catalogue_link($label), 'children' => $children];
    }
    $collections = [];
    foreach (['BST The essence of her', 'GIAO THỜI', 'Nhịp Thành phố', 'Áo dài THỜI NHÀ', 'NHẸ BƯỚC MỖI NGÀY'] as $label) {
        $collections[] = ['title' => $label, 'url' => npe_nav_page_url(sanitize_title($label), npe_nav_catalogue_link($label)), 'children' => []];
    }
    return [
        ['title' => 'SẢN PHẨM', 'url' => $shop, 'children' => $products],
        ['title' => 'SẢN PHẨM MỚI', 'url' => npe_nav_page_url('san-pham-moi', add_query_arg('orderby', 'date', $shop)), 'children' => []],
        ['title' => 'NEM PLUS+', 'url' => npe_nav_page_url('nem-plus', npe_nav_catalogue_link('NEM PLUS+')), 'children' => []],
        ['title' => 'SẢN PHẨM SALE', 'url' => add_query_arg('npe_sale', '1', $shop), 'children' => [
            ['title' => 'CHỈ TỪ 199K', 'url' => add_query_arg(['npe_sale' => '1', 'max_price' => '199000'], $shop), 'children' => []],
            ['title' => 'SALE UP 50%', 'url' => add_query_arg('npe_sale', '1', $shop), 'children' => []],
        ]],
        ['title' => 'BỘ SƯU TẬP', 'url' => npe_nav_page_url('bo-suu-tap', $shop), 'children' => $collections],
    ];
}

/** Build only reachable descendants; malformed or cyclic menu entries are skipped. */
function npe_nav_menu_tree($items, $parent = 0, $visited = [], $depth = 0) {
    if ($depth >= 5) return [];
    $tree = [];
    foreach ($items as $item) {
        $id = (int) $item->ID;
        if ((int) $item->menu_item_parent !== (int) $parent || isset($visited[$id])) continue;
        $seen = $visited;
        $seen[$id] = true;
        $tree[] = [
            'title' => $item->title,
            'url' => $item->url,
            'target' => $item->target === '_blank' ? '_blank' : '',
            'current' => !empty($item->current),
            'children' => npe_nav_menu_tree($items, $id, $seen, $depth + 1),
        ];
    }
    return $tree;
}

function npe_nav_render_items($items, $depth = 0) {
    static $counter = 0;
    foreach ($items as $item) {
        $children = $item['children'] ?? [];
        $submenu_id = $children ? 'npe-submenu-' . (++$counter) : '';
        echo '<li class="npe-nav-item npe-nav-depth-' . (int) $depth . ($children ? ' npe-nav-has-children' : '') . '">';
        echo '<a class="npe-nav-link" href="' . esc_url($item['url']) . '"';
        if (!empty($item['current'])) echo ' aria-current="page"';
        if (($item['target'] ?? '') === '_blank') echo ' target="_blank" rel="noopener noreferrer"';
        echo '>' . esc_html($item['title']) . '</a>';
        if ($children) {
            echo '<button type="button" class="npe-submenu-toggle" aria-expanded="false" aria-controls="' . esc_attr($submenu_id) . '" aria-label="' . esc_attr('Mở danh mục ' . $item['title']) . '"><svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
            echo '<ul class="npe-nav-submenu" id="' . esc_attr($submenu_id) . '">';
            npe_nav_render_items($children, $depth + 1);
            echo '</ul>';
        }
        echo '</li>';
    }
}

function npe_render_navigation($id = 'npe-navigation', $extra_class = '') {
    $locations = get_nav_menu_locations();
    $items = !empty($locations['npe-primary']) ? wp_get_nav_menu_items($locations['npe-primary']) : [];
    $tree = $items && !is_wp_error($items) ? npe_nav_menu_tree($items) : [];
    if (!$tree) $tree = npe_nav_defaults();
    echo '<nav id="' . esc_attr($id) . '" class="' . esc_attr(trim('npe-navigation ' . $extra_class)) . '" aria-label="Menu chính"><ul class="npe-nav-list">';
    npe_nav_render_items($tree);
    echo '</ul></nav>';
}

/** Preserve the existing Storefront logo/actions/drawer and share only its menu. */
function npe_nav_legacy_header_markup($header) {
    ob_start();
    npe_render_navigation('npe-navigation', 'npe-navigation-legacy');
    $desktop = ob_get_clean();
    ob_start();
    npe_render_navigation('npe-mobile-navigation', 'npe-navigation-drawer');
    $mobile = ob_get_clean();
    $header = preg_replace_callback('/<nav\b[^>]*class="nem-desktop-nav"[^>]*>.*?<\/nav>/s', function () use ($desktop) { return $desktop; }, $header, 1);
    return preg_replace_callback('/(<div class="nem-drawer-body">\s*)<ul class="nem-mobile-nav-list">.*?<\/ul>\s*(<div class="nem-drawer-footer">)/s', function ($match) use ($mobile) { return $match[1] . $mobile . $match[2]; }, $header, 1);
}

// The sale links use the live WooCommerce sale list, including variable products.
add_action('woocommerce_product_query', function ($query) {
    if (!isset($_GET['npe_sale']) || !is_scalar($_GET['npe_sale']) || sanitize_text_field(wp_unslash($_GET['npe_sale'])) !== '1') return;
    $sale_ids = array_map('absint', wc_get_product_ids_on_sale());
    $existing = array_filter((array) $query->get('post__in'));
    if ($existing) $sale_ids = array_intersect($sale_ids, $existing);
    $query->set('post__in', $sale_ids ?: [0]);
});
