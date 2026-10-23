<?php
defined('ABSPATH') || exit;

// Ensure WooCommerce cart is initialized
if (null === WC()->cart) {
    wc_load_cart();
}

$cart = WC()->cart;
$current_user = wp_get_current_user();
$checkout = WC()->checkout();

// Pre-fill fields if user logged in
$default_name = $current_user->ID ? ($current_user->display_name ?: trim($current_user->first_name . ' ' . $current_user->last_name)) : '';
$default_phone = $current_user->ID ? get_user_meta($current_user->ID, 'billing_phone', true) : '';
$default_email = $current_user->ID ? $current_user->user_email : '';
$default_address = $current_user->ID ? get_user_meta($current_user->ID, 'billing_address_1', true) : '';
$default_city = $current_user->ID ? get_user_meta($current_user->ID, 'billing_city', true) : '';

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thanh toán — NEM FASHION</title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(content_url('/mu-plugins/nem-page-experience/checkout.css?v=' . filemtime(__DIR__ . '/checkout.css'))); ?>">
</head>
<body class="nem-checkout-body">
    <div class="nem-checkout-container">
        <!-- Brand Header -->
        <header class="nem-checkout-header">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nem-checkout-logo">NEM FASHION</a>
        </header>

        <!-- Image 2: Auth Banner (only shown when not logged in) -->
        <?php if (!is_user_logged_in()) : ?>
        <div class="nem-checkout-auth-banner">
            <span class="nem-checkout-auth-text">Đăng nhập để mua hàng tiện lợi và nhận nhiều ưu đãi hơn nữa</span>
            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="nem-checkout-auth-btn">Đăng nhập</a>
        </div>
        <?php endif; ?>

        <div class="nem-checkout-alert" hidden></div>

        <!-- Checkout Form -->
        <form name="checkout" method="post" class="checkout woocommerce-checkout nem-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('nem_checkout_nonce')); ?>">
            <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
            <input type="hidden" name="billing_country" value="VN">
            <input type="hidden" name="billing_city" value="<?php echo esc_attr($default_city); ?>">
            <input type="hidden" name="billing_state" value="<?php echo esc_attr($default_city); ?>">

            <div class="nem-checkout-grid">
                <!-- LEFT COLUMN -->
                <div class="nem-checkout-left">
                    <!-- Thông tin giao hàng -->
                    <section class="nem-card">
                        <h2 class="nem-card-title">Thông tin giao hàng</h2>
                        <div class="nem-form-group">
                            <input type="text" name="billing_first_name" class="nem-input" placeholder="Nhập họ và tên" value="<?php echo esc_attr($default_name); ?>" required>
                        </div>
                        <div class="nem-form-group nem-input-with-icon">
                            <input type="tel" name="billing_phone" class="nem-input" placeholder="Nhập số điện thoại" value="<?php echo esc_attr($default_phone); ?>" required>
                            <span class="nem-input-icon-right" title="Việt Nam">
                                <svg width="22" height="15" viewBox="0 0 22 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="22" height="15" rx="2" fill="#DA251D"/>
                                    <polygon points="11,3 12.2,6.8 16.2,6.8 13,9.1 14.2,13 11,10.6 7.8,13 9,9.1 5.8,6.8 9.8,6.8" fill="#FFFF00"/>
                                </svg>
                            </span>
                        </div>
                        <div class="nem-form-group">
                            <input type="email" name="billing_email" class="nem-input" placeholder="Nhập email (không bắt buộc)" value="<?php echo esc_attr($default_email); ?>">
                        </div>
                        <div class="nem-form-group nem-country-box">
                            <div class="nem-country-label">Quốc gia</div>
                            <div class="nem-country-value">Vietnam</div>
                        </div>
                        <div class="nem-form-group">
                            <input type="text" name="billing_address_1" class="nem-input" placeholder="Địa chỉ, tên đường" value="<?php echo esc_attr($default_address); ?>" required>
                        </div>
                        <div class="nem-form-group nem-address-picker">
                            <input type="text" id="nem_billing_address_group" class="nem-input" placeholder="Tỉnh/TP, Quận/Huyện, Phường/Xã" value="<?php echo esc_attr($default_city); ?>" autocomplete="off">
                            <div class="nem-address-dropdown" hidden></div>
                        </div>
                    </section>

                    <!-- Phương thức giao hàng -->
                    <section class="nem-card">
                        <h2 class="nem-card-title">Phương thức giao hàng</h2>
                        <div class="nem-shipping-placeholder">
                            Nhập địa chỉ để xem các phương thức giao hàng
                        </div>
                    </section>

                    <!-- Phương thức thanh toán -->
                    <section class="nem-card">
                        <h2 class="nem-card-title">Phương thức thanh toán</h2>
                        <div class="nem-payment-options">
                            <!-- Option 1: MoMo -->
                            <label class="nem-payment-item is-selected">
                                <input type="radio" name="payment_method" value="momo" class="nem-payment-radio" checked>
                                <span class="nem-payment-icon">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                                        <rect width="28" height="28" rx="6" fill="#A50064"/>
                                        <text x="14" y="12" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="13" font-weight="700">mo</text>
                                        <text x="14" y="23" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="13" font-weight="700">mo</text>
                                    </svg>
                                </span>
                                <span class="nem-payment-text">Thanh toán online qua ví MoMo</span>
                            </label>

                            <!-- Option 2: Payoo -->
                            <label class="nem-payment-item">
                                <input type="radio" name="payment_method" value="payoo" class="nem-payment-radio">
                                <span class="nem-payment-icon">
                                    <svg class="nem-payoo-logo" width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
                                        <text x="14" y="17" text-anchor="middle" fill="#268ac2" font-family="Arial,sans-serif" font-size="7" font-style="italic">Payoo</text>
                                    </svg>
                                </span>
                                <div class="nem-payment-text">
                                    <div>Thẻ ATM/ Visa/ Master/ JCB/ QR Pay/ Ví điện tử khác qua Payoo</div>
                                    <div class="nem-payment-badges">
                                        <svg viewBox="0 0 25 14" role="img" aria-label="ATM"><rect x="1" y="1" width="23" height="12" rx="2" fill="#f5f5f5" stroke="#aaa"/><text x="12.5" y="10" text-anchor="middle" font-family="Arial" font-size="8" fill="#555">ATM</text></svg>
                                        <svg viewBox="0 0 32 14" role="img" aria-label="Visa"><text x="1" y="11" fill="#15469a" font-family="Arial" font-style="italic" font-weight="bold" font-size="13">VISA</text></svg>
                                        <svg viewBox="0 0 22 14" role="img" aria-label="JCB"><rect x="1" y="1" width="6" height="12" rx="1" fill="#1264a3"/><rect x="8" y="1" width="6" height="12" rx="1" fill="#d72a3b"/><rect x="15" y="1" width="6" height="12" rx="1" fill="#199950"/><text x="11" y="10" text-anchor="middle" fill="white" font-family="Arial" font-weight="bold" font-size="9">JCB</text></svg>
                                        <svg viewBox="0 0 24 14" role="img" aria-label="Mastercard"><circle cx="9" cy="7" r="6" fill="#eb001b"/><circle cx="16" cy="7" r="6" fill="#f79e1b" fill-opacity=".9"/></svg>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Chuyển khoản ngân hàng -->
                            <label class="nem-payment-item">
                                <input type="radio" name="payment_method" value="bacs" class="nem-payment-radio">
                                <span class="nem-payment-icon">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 21h18"/>
                                        <path d="M3 10h18"/>
                                        <path d="M5 6l7-3 7 3"/>
                                        <path d="M4 10v11"/>
                                        <path d="M20 10v11"/>
                                        <path d="M8 14v4"/>
                                        <path d="M12 14v4"/>
                                        <path d="M16 14v4"/>
                                    </svg>
                                </span>
                                <span class="nem-payment-text">Chuyển khoản qua ngân hàng</span>
                            </label>

                            <!-- Option 4: COD -->
                            <label class="nem-payment-item">
                                <input type="radio" name="payment_method" value="cod" class="nem-payment-radio">
                                <span class="nem-payment-icon">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                                    </svg>
                                </span>
                                <span class="nem-payment-text">Thanh toán khi giao hàng (COD)</span>
                            </label>
                        </div>
                    </section>

                    <!-- Ghi chú đơn hàng -->
                    <section class="nem-card">
                        <div class="nem-form-group">
                            <input type="text" name="order_comments" class="nem-input nem-note-input" placeholder="Ghi chú đơn hàng">
                        </div>
                    </section>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="nem-checkout-right">
                    <!-- Giỏ hàng -->
                    <section class="nem-card">
                        <h2 class="nem-card-title">Giỏ hàng</h2>
                        <div class="nem-cart-items">
                            <?php if ($cart && !$cart->is_empty()) : ?>
                                <?php foreach ($cart->get_cart() as $cart_item_key => $cart_item) :
                                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                    if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0) continue;

                                    $product_name = $_product->get_name();
                                    $thumbnail = $_product->get_image('thumbnail');
                                    $price = WC()->cart->get_product_price($_product);
                                    $subtotal = WC()->cart->get_product_subtotal($_product, $cart_item['quantity']);

                                    // Attribute formatting (e.g. Size 4 / Vàng >)
                                    $variant_labels = [];
                                    if ($_product->is_type('variation')) {
                                        $attrs = $_product->get_attributes();
                                        foreach ($attrs as $name => $val) {
                                            $variant_labels[] = $val;
                                        }
                                    } elseif (!empty($cart_item['npe_options'])) {
                                        foreach ($cart_item['npe_options'] as $name => $val) {
                                            $variant_labels[] = $val;
                                        }
                                    }
                                    $variant_string = implode(' / ', $variant_labels);
                                ?>
                                <div class="nem-cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
                                    <div class="nem-cart-thumb">
                                        <?php echo $thumbnail; ?>
                                    </div>
                                    <div class="nem-cart-details">
                                        <div class="nem-cart-top-row">
                                            <h3 class="nem-cart-item-title"><?php echo esc_html($product_name); ?></h3>
                                            <button type="button" class="nem-cart-delete-btn" aria-label="Xóa sản phẩm" title="Xóa">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <?php if ($variant_string) : ?>
                                        <div class="nem-cart-variant-pill"><?php echo esc_html($variant_string); ?> &gt;</div>
                                        <?php endif; ?>
                                        <div class="nem-cart-bottom-row">
                                            <div class="nem-cart-price"><?php echo $subtotal; ?></div>
                                            <div class="nem-stepper">
                                                <button type="button" class="nem-stepper-btn nem-stepper-minus" aria-label="Giảm">−</button>
                                                <input type="number" class="nem-stepper-input" value="<?php echo esc_attr($cart_item['quantity']); ?>" min="1" readonly>
                                                <button type="button" class="nem-stepper-btn nem-stepper-plus" aria-label="Tăng">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p style="color:#888;padding:12px 0;">Giỏ hàng của bạn đang trống.</p>
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="nem-checkout-auth-btn" style="text-align:center;display:block;">Tiếp tục mua sắm</a>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Mã khuyến mãi -->
                    <section class="nem-card">
                        <div class="nem-coupon-row-top" title="Xem danh sách mã">
                            <div class="nem-coupon-label-wrap">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/>
                                    <line x1="9" y1="12" x2="15" y2="12"/>
                                </svg>
                                <span>Chọn mã</span>
                            </div>
                            <span class="nem-coupon-arrow">&gt;</span>
                        </div>
                        <div class="nem-coupon-suggestions" hidden style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="nem-quick-coupon-tag nem-badge" style="cursor:pointer;padding:4px 8px;font-size:11px;" data-code="NEM10">NEM10 (-10%)</span>
                            <span class="nem-quick-coupon-tag nem-badge" style="cursor:pointer;padding:4px 8px;font-size:11px;" data-code="FREESHIP">FREESHIP</span>
                        </div>
                        <div class="nem-coupon-input-group">
                            <input type="text" class="nem-coupon-input" placeholder="Nhập mã khuyến mãi">
                            <button type="button" class="nem-coupon-btn">Áp dụng</button>
                        </div>
                        <div class="nem-coupon-notice" style="font-size:12px;margin-top:8px;" hidden></div>
                    </section>

                    <!-- Tóm tắt đơn hàng -->
                    <section class="nem-card">
                        <h2 class="nem-card-title">Tóm tắt đơn hàng</h2>
                        <div class="nem-summary-rows">
                            <div class="nem-summary-row">
                                <span>Tổng tiền hàng</span>
                                <span class="nem-summary-subtotal"><?php echo $cart ? $cart->get_cart_subtotal() : '0₫'; ?></span>
                            </div>
                            <div class="nem-summary-row">
                                <span>Phí vận chuyển</span>
                                <span>-</span>
                            </div>
                            <div class="nem-summary-row nem-summary-discount-row" hidden>
                                <span>Giảm giá</span>
                                <span class="nem-summary-discount" style="color:#e44141;">-0₫</span>
                            </div>
                            <div class="nem-summary-row is-total">
                                <span>Tổng thanh toán</span>
                                <span class="nem-summary-total nem-summary-value"><?php echo $cart ? $cart->get_total() : '0₫'; ?></span>
                            </div>
                        </div>

                        <button type="submit" class="nem-checkout-submit-btn" id="place_order">Đặt hàng</button>
                    </section>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <footer class="nem-checkout-footer">
            <span>Powered by</span> <span class="nem-haravan-brand"><svg class="nem-haravan-mark" width="24" height="24" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><g fill="currentColor"><path d="M7 8 23 6l2 18-17 4L7 8Zm-4 3 3-1 1 17-3-2L3 11Z"/><path d="m24 9 4 2-1 10-2 2-1-14Z"/></g><path d="M11 9V6a5 5 0 0 1 10 0v2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M12 12v10m7-11v10m-7-5 7-1" fill="none" stroke="#f5f5f7" stroke-width="2.2"/><path d="M2 24c-4 9 16 7 29-11M3 27c5 3 19-3 26-10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span>haravan</span></span>
        </footer>
    </div>

    <script src="<?php echo esc_url(content_url('/mu-plugins/nem-page-experience/checkout.js?v=' . filemtime(__DIR__ . '/checkout.js'))); ?>"></script>
    <?php wp_footer(); ?>
</body>
</html>
