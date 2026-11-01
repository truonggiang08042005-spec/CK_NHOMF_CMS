<?php
if (! defined('ABSPATH')) {
    exit;
}

// Tải CSS giỏ hàng
function storefront_custom_cart_styles()
{
    if (! function_exists('is_cart') || ! is_cart()) {
        return;
    }

    $file = get_template_directory() . '/assets/css/custom-cart.css';

    if (file_exists($file)) {
        wp_enqueue_style(
            'storefront-custom-cart',
            get_template_directory_uri() . '/assets/css/custom-cart.css',
            array(),
            filemtime($file)
        );
    }

    $script_file = get_template_directory() . '/assets/js/custom-cart-quantity.js';

    if (file_exists($script_file)) {
        wp_enqueue_script(
            'storefront-custom-cart-quantity',
            get_template_directory_uri() . '/assets/js/custom-cart-quantity.js',
            array(),
            filemtime($script_file),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'storefront_custom_cart_styles', 99);

// Thay Cart Block bằng giỏ hàng tùy chỉnh
function storefront_custom_cart_block($content, $block)
{
    if (
        ! function_exists('is_cart') ||
        ! is_cart() ||
        empty($block['blockName']) ||
        'woocommerce/cart' !== $block['blockName']
    ) {
        return $content;
    }

    return do_shortcode('[storefront_custom_cart]');
}
add_filter('render_block', 'storefront_custom_cart_block', 10, 2);

// Hiển thị giỏ hàng
function storefront_custom_cart_shortcode()
{
    if (! function_exists('WC') || ! WC()->cart) {
        return '';
    }

    if (WC()->cart->is_empty()) {
        return '<div class="storefront-custom-cart-empty"><p>Giỏ hàng trống</p><a href="' .
            esc_url(wc_get_page_permalink('shop')) .
            '">Tiếp tục để mua hàng</a></div>';
    }

    ob_start();
?>
    <div class="storefront-custom-cart">
        <form class="storefront-custom-cart-form"
            action="<?php echo esc_url(wc_get_cart_url()); ?>"
            method="post">

            <div class="storefront-custom-cart-head">
                <span class="storefront-custom-cart-product-heading">Sản phẩm</span>
                <span class="storefront-custom-cart-price-heading">Giá</span>
                <span class="storefront-custom-cart-quantity-heading">Số lượng</span>
                <span class="storefront-custom-cart-total-heading">Tổng tiền</span>
            </div>

            <div class="storefront-custom-cart-items">
                <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $product = apply_filters(
                        'woocommerce_cart_item_product',
                        $cart_item['data'],
                        $cart_item,
                        $cart_item_key
                    );

                    if (
                        ! $product ||
                        ! $product->exists() ||
                        $cart_item['quantity'] < 1
                    ) {
                        continue;
                    }

                    $product_name = apply_filters(
                        'woocommerce_cart_item_name',
                        $product->get_name(),
                        $cart_item,
                        $cart_item_key
                    );

                    $product_permalink = $product->is_visible()
                        ? $product->get_permalink($cart_item)
                        : '';

                    $thumbnail = $product->get_image('woocommerce_thumbnail');
                    $quantity = max(1, (int) $cart_item['quantity']);

                    $line_total = WC()->cart->get_product_subtotal(
                        $product,
                        $quantity
                    );

                    $product_details = array(
                        'Thương hiệu' => array(
                            'Thương hiệu',
                            'brand',
                            'pa_brand',
                            'pa_thuong-hieu',
                        ),
                        'Phiên bản' => array(
                            'Phiên bản',
                            'version',
                            'pa_version',
                            'pa_phien-ban',
                            'Size',
                            'size',
                            'pa_size',
                            'pa_kich-co',
                        ),
                    );

                    foreach ($product_details as $label => $attribute_names) {
                        $value = '';
                        $matched_attribute = '';

                        foreach ($attribute_names as $attribute_name) {
                            $value = $product->get_attribute($attribute_name);

                            if ('' !== $value) {
                                $matched_attribute = $attribute_name;
                                break;
                            }
                        }

                        if (
                            'Phiên bản' === $label &&
                            '' !== $value &&
                            in_array($matched_attribute, array('Size', 'size', 'pa_size', 'pa_kich-co'), true) &&
                            ! preg_match('/^size\b/i', $value)
                        ) {
                            $value = 'Size ' . $value;
                        }

                        $product_details[$label] = $value;
                    }

                    if (taxonomy_exists('product_brand')) {
                        $brand_product_id = $product->is_type('variation')
                            ? $product->get_parent_id()
                            : $product->get_id();
                        $brand_terms = wp_get_post_terms(
                            $brand_product_id,
                            'product_brand',
                            array('fields' => 'names')
                        );

                        if (! is_wp_error($brand_terms) && ! empty($brand_terms)) {
                            $product_details['Thương hiệu'] = implode(', ', $brand_terms);
                        }
                    }
                ?>

                    <div class="storefront-custom-cart-item">
                        <div class="storefront-custom-cart-product">
                            <a class="storefront-custom-cart-image"
                                href="<?php echo esc_url($product_permalink ?: wc_get_cart_url()); ?>">
                                <?php echo wp_kses_post($thumbnail); ?>
                            </a>

                            <div class="storefront-custom-cart-details">
                                <div class="storefront-custom-cart-name">
                                    <?php
                                    if ($product_permalink) {
                                        echo '<a href="' . esc_url($product_permalink) . '">' .
                                            wp_kses_post($product_name) .
                                            '</a>';
                                    } else {
                                        echo wp_kses_post($product_name);
                                    }
                                    ?>
                                </div>

                                <?php echo wc_get_formatted_cart_item_data($cart_item); ?>

                                <?php foreach ($product_details as $label => $value) : ?>
                                    <?php if ('' !== $value) : ?>
                                        <div class="storefront-custom-cart-product-detail">
                                            <span><?php echo esc_html($label); ?>:</span>
                                            <?php echo esc_html($value); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <div class="storefront-custom-cart-remove">
                                    <a
                                        href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>"
                                        aria-label="<?php echo esc_attr(sprintf(
                                                        'Xóa %s khỏi giỏ hàng',
                                                        wp_strip_all_tags($product_name)
                                                    )); ?>"
                                        class="remove">
                                        Xóa
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="storefront-custom-cart-price">
                            <?php echo wp_kses_post(WC()->cart->get_product_price($product)); ?>

                            <?php if ($product->is_on_sale()) : ?>
                                <div class="storefront-custom-cart-discount">
                                    <?php
                                    echo wp_kses_post(
                                        wc_price(
                                            wc_get_price_to_display(
                                                $product,
                                                array(
                                                    'price' => $product->get_regular_price()
                                                )
                                            )
                                        )
                                    );
                                    ?>
                                </div>

                                <span class="storefront-custom-cart-sale-badge">
                                    <?php
                                    $regular_price = (float) $product->get_regular_price();
                                    $sale_price = (float) $product->get_price();

                                    $discount = $regular_price > 0
                                        ? round(($regular_price - $sale_price) / $regular_price * 100)
                                        : 0;

                                    echo esc_html('-' . $discount . '%');
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="storefront-custom-cart-quantity">
                            <?php
                            $quantity_input = woocommerce_quantity_input(
                                array(
                                    'input_name'   => "cart[{$cart_item_key}][qty]",
                                    'input_value'  => $quantity,
                                    'min_value'    => 1,
                                    'max_value'    => $product->is_sold_individually()
                                        ? 1
                                        : $product->get_max_purchase_quantity(),
                                    'product_name' => $product_name,
                                ),
                                $product,
                                false
                            );
                            ?>
                            <div class="storefront-custom-cart-quantity-control">
                                <button
                                    type="button"
                                    class="storefront-custom-cart-quantity-button storefront-custom-cart-quantity-decrease"
                                    aria-label="Giảm số lượng"
                                    data-quantity-change="-1"></button>
                                <?php echo $quantity_input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <button
                                    type="button"
                                    class="storefront-custom-cart-quantity-button storefront-custom-cart-quantity-increase"
                                    aria-label="Tăng số lượng"
                                    data-quantity-change="1"></button>
                            </div>
                        </div>

                        <div class="storefront-custom-cart-line-total">
                            <?php echo wp_kses_post($line_total); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="storefront-custom-cart-bottom">
                <div class="storefront-custom-cart-note">
                    <label for="storefront-cart-note">Chú thích</label>
                    <textarea
                        name="cart_note"
                        id="storefront-cart-note"><?php echo esc_textarea(WC()->session->get('storefront_cart_note', '')); ?></textarea>
                </div>

                <div class="storefront-custom-cart-actions">
                    <div class="storefront-custom-cart-grand-total">
                        <span>Tổng tiền</span>
                        <strong><?php echo wp_kses_post(WC()->cart->get_total()); ?></strong>
                    </div>

                    <div class="storefront-custom-cart-buttons">
                        <button type="submit"
                            class="storefront-custom-cart-update"
                            name="update_cart"
                            value="1">
                            Cập nhật
                        </button>

                        <a class="storefront-custom-cart-checkout"
                            href="<?php echo esc_url(wc_get_checkout_url()); ?>">
                            Thanh toán
                        </a>
                    </div>
                </div>
            </div>

            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
            <input type="hidden" name="storefront_custom_cart_submit" value="1">
        </form>
    </div>
<?php

    return ob_get_clean();
}
add_shortcode('storefront_custom_cart', 'storefront_custom_cart_shortcode');

// Cập nhật số lượng và lưu chú thích
function storefront_custom_cart_update()
{
    if (
        ! function_exists('is_cart') ||
        ! is_cart() ||
        empty($_POST['storefront_custom_cart_submit']) ||
        empty($_POST['woocommerce-cart-nonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['woocommerce-cart-nonce'])),
            'woocommerce-cart'
        )
    ) {
        return;
    }

    // Cập nhật số lượng sản phẩm
    if (isset($_POST['cart']) && is_array($_POST['cart'])) {
        foreach ($_POST['cart'] as $cart_item_key => $values) {
            if (! isset(WC()->cart->cart_contents[$cart_item_key])) {
                continue;
            }

            $quantity = isset($values['qty'])
                ? absint($values['qty'])
                : 1;

            $quantity = max(1, $quantity);

            WC()->cart->set_quantity($cart_item_key, $quantity, false);
        }

        WC()->cart->calculate_totals();
    }

    // Lưu chú thích vào Session
    if (isset($_POST['cart_note']) && WC()->session) {
        $note = sanitize_textarea_field(
            wp_unslash($_POST['cart_note'])
        );

        WC()->session->set('storefront_cart_note', $note);
    }

    wp_safe_redirect(wc_get_cart_url());
    exit;
}
add_action('wp_loaded', 'storefront_custom_cart_update', 20);

// Điền chú thích vào ô ghi chú ở trang thanh toán classic
function storefront_custom_cart_checkout_note($fields)
{
    if (WC()->session && isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['default'] =
            WC()->session->get('storefront_cart_note', '');
    }

    return $fields;
}
add_filter(
    'woocommerce_checkout_fields',
    'storefront_custom_cart_checkout_note'
);

// Lưu chú thích vào đơn hàng
function storefront_custom_cart_save_order_note($order, $data)
{
    if (! WC()->session) {
        return;
    }

    $note = WC()->session->get('storefront_cart_note', '');

    if ($note !== '') {
        $order->set_customer_note($note);
    }
}
add_action(
    'woocommerce_checkout_create_order',
    'storefront_custom_cart_save_order_note',
    10,
    2
);

// Xóa chú thích khỏi Session sau khi đặt hàng thành công
function storefront_custom_cart_clear_note($order_id)
{
    if (WC()->session) {
        WC()->session->__unset('storefront_cart_note');
    }
}
add_action(
    'woocommerce_thankyou',
    'storefront_custom_cart_clear_note'
);
