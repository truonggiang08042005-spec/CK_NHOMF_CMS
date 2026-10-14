<?php
/**
 * Login & Registration Form override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$home_url = esc_url( home_url( '/' ) );
?>

<div class="nem-profile-wrapper nem-auth-page-wrapper">
    <!-- Breadcrumb -->
    <div class="nem-profile-breadcrumb">
        <a href="<?php echo $home_url; ?>">TRANG CHỦ</a>
        <span class="nem-bc-separator">/</span>
        <span class="nem-bc-active">TÀI KHOẢN</span>
    </div>

    <!-- Auth Container -->
    <div class="nem-auth-container">
        <?php do_action( 'woocommerce_before_customer_login_form' ); ?>

        <div class="nem-auth-grid" id="customer_login">
            <!-- Cột Đăng Nhập -->
            <div class="nem-auth-box nem-login-box">
                <h2 class="nem-auth-title">ĐĂNG NHẬP</h2>
                <form class="woocommerce-form woocommerce-form-login login nem-auth-form" method="post" novalidate>
                    <?php do_action( 'woocommerce_login_form_start' ); ?>

                    <p class="nem-form-row">
                        <label for="username">Tên đăng nhập hoặc Email <span class="required">*</span></label>
                        <input type="text" class="nem-auth-input" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />
                    </p>

                    <p class="nem-form-row">
                        <label for="password">Mật khẩu <span class="required">*</span></label>
                        <input class="nem-auth-input" type="password" name="password" id="password" autocomplete="current-password" required />
                    </p>

                    <?php do_action( 'woocommerce_login_form' ); ?>

                    <div class="nem-form-options">
                        <label class="nem-remember-label">
                            <input class="nem-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="nem-lost-pwd-link">Quên mật khẩu?</a>
                    </div>

                    <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                    <button type="submit" class="nem-auth-submit-btn" name="login" value="Đăng nhập">ĐĂNG NHẬP</button>

                    <?php do_action( 'woocommerce_login_form_end' ); ?>
                </form>
            </div>

            <!-- Cột Đăng Ký -->
            <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
                <div class="nem-auth-divider"></div>

                <div class="nem-auth-box nem-register-box">
                    <h2 class="nem-auth-title">ĐĂNG KÝ</h2>
                    <form method="post" class="woocommerce-form woocommerce-form-register register nem-auth-form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
                        <?php do_action( 'woocommerce_register_form_start' ); ?>

                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
                            <p class="nem-form-row">
                                <label for="reg_username">Tên tài khoản <span class="required">*</span></label>
                                <input type="text" class="nem-auth-input" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />
                            </p>
                        <?php endif; ?>

                        <p class="nem-form-row">
                            <label for="reg_email">Địa chỉ Email <span class="required">*</span></label>
                            <input type="email" class="nem-auth-input" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required />
                        </p>

                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
                            <p class="nem-form-row">
                                <label for="reg_password">Mật khẩu <span class="required">*</span></label>
                                <input type="password" class="nem-auth-input" name="password" id="reg_password" autocomplete="new-password" required />
                            </p>
                        <?php else : ?>
                            <p class="nem-reg-hint">Mật khẩu sẽ được gửi đến địa chỉ email của bạn.</p>
                        <?php endif; ?>

                        <?php do_action( 'woocommerce_register_form' ); ?>

                        <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                        <button type="submit" class="nem-auth-submit-btn nem-btn-reg" name="register" value="Đăng ký">ĐĂNG KÝ</button>

                        <?php do_action( 'woocommerce_register_form_end' ); ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php do_action( 'woocommerce_after_customer_login_form' ); ?>
    </div>
</div>

<!-- ĐĂNG KÝ BẢN TIN SECTION -->
<section class="nem-newsletter-section">
    <div class="nem-newsletter-container">
        <h2 class="nem-newsletter-heading">ĐĂNG KÝ BẢN TIN</h2>
        <p class="nem-newsletter-desc">Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất</p>

        <form class="nem-newsletter-form" action="#" method="post" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký nhận bản tin NEM!');">
            <input type="email" class="nem-newsletter-input" name="email" placeholder="Vui lòng nhập email..." required />
            <button type="submit" class="nem-newsletter-submit">ĐĂNG KÝ</button>
        </form>

        <div class="nem-newsletter-social-icons">
            <a href="https://www.facebook.com/nemfashion.official" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-facebook" title="Facebook">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="https://www.instagram.com/nemfashion.official/" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-instagram" title="Instagram">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
            <a href="https://www.youtube.com/channel/UCrDQ6euiL9CekivZmD9-MCA" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-youtube" title="YouTube">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>
        </div>
    </div>
</section>
