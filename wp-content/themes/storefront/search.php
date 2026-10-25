<?php
/**
 * Search results template.
 *
 * @package storefront
 */

defined('ABSPATH') || exit;

$is_product_search = function_exists('nem_is_product_search') && nem_is_product_search();

get_header();
?>

<div id="primary" class="content-area<?php echo $is_product_search ? ' nem-product-search' : ''; ?>">
    <main id="main" class="site-main<?php echo $is_product_search ? ' nem-shop-main' : ''; ?>" role="main">
        <?php if ($is_product_search) : ?>
            <nav class="nem-search-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'storefront'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'storefront'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php esc_html_e('Tìm kiếm', 'storefront'); ?></span>
            </nav>

            <div class="nem-shop-toolbar">
                <h1 class="nem-shop-toolbar-title">
                    <?php esc_html_e('Kết quả tìm kiếm:', 'storefront'); ?>
                    <span><?php echo esc_html(get_search_query()); ?></span>
                </h1>
            </div>

            <p class="nem-shop-filter-status screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
            <div class="nem-shop-results">
                <?php if (have_posts()) : ?>
                    <?php
                    wc_set_loop_prop('columns', 4);
                    woocommerce_product_loop_start();
                    while (have_posts()) :
                        the_post();
                        wc_get_template_part('content', 'product');
                    endwhile;
                    woocommerce_product_loop_end();
                    woocommerce_pagination();
                    wc_reset_loop();
                    ?>
                <?php else : ?>
                    <div class="nem-search-empty">
                        <p>
                            <?php
                            printf(
                                esc_html__('Không tìm thấy kết quả tìm kiếm phù hợp cho: %s', 'storefront'),
                                '<strong>' . esc_html(get_search_query()) . '</strong>'
                            );
                            ?>
                        </p>
                        <form role="search" method="get" class="nem-search-empty-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <label class="screen-reader-text" for="nem-search-empty-input"><?php esc_html_e('Tìm kiếm sản phẩm', 'storefront'); ?></label>
                            <input type="search" id="nem-search-empty-input" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Tìm kiếm sản phẩm...', 'storefront'); ?>">
                            <input type="hidden" name="post_type" value="product">
                            <button type="submit"><?php esc_html_e('Tìm kiếm', 'storefront'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (have_posts()) : ?>
            <header class="page-header">
                <h1 class="page-title">
                    <?php
                    printf(
                        esc_html__('Search Results for: %s', 'storefront'),
                        '<span>' . esc_html(get_search_query()) . '</span>'
                    );
                    ?>
                </h1>
            </header>
            <?php get_template_part('loop'); ?>
        <?php else : ?>
            <?php get_template_part('content', 'none'); ?>
        <?php endif; ?>
    </main>
</div>

<?php
if (!$is_product_search) {
    do_action('storefront_sidebar');
}
get_footer();
