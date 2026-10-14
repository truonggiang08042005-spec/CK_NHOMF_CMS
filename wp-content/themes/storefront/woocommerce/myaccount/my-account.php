<?php
/**
 * My Account page override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$display_name = ! empty( $current_user->display_name ) ? $current_user->display_name : 'NGUYỄN GIANG';
$user_email   = ! empty( $current_user->user_email ) ? $current_user->user_email : 'truonggiang08042005@gmail.com';
?>

<div class="nem-profile-wrapper">
    <!-- Breadcrumb -->
    <div class="nem-profile-breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">TRANG CHỦ</a>
        <span class="nem-bc-separator">/</span>
        <span class="nem-bc-active">TÀI KHOẢN</span>
    </div>

    <!-- Profile Header Greeting -->
    <div class="nem-profile-greeting-wrap">
        <span class="nem-greeting-sub">Xin chào,</span>
        <h1 class="nem-greeting-name"><?php echo esc_html( mb_strtoupper( $display_name, 'UTF-8' ) ); ?></h1>
    </div>

    <!-- Main Grid: Sidebar Menu + Account Content -->
    <div class="nem-profile-grid">
        <!-- Sidebar Navigation -->
        <div class="nem-profile-sidebar-col">
            <?php
            /**
             * My Account navigation.
             *
             * @since 2.6.0
             */
            do_action( 'woocommerce_account_navigation' );
            ?>
        </div>

        <!-- Main Account Content -->
        <div class="nem-profile-content-col">
            <?php
            /**
             * My Account content.
             *
             * @since 2.6.0
             */
            do_action( 'woocommerce_account_content' );
            ?>
        </div>
    </div>
</div>

<!-- ĐĂNG KÝ BẢN TIN SECTION -->
<section class="nem-newsletter-section">
    <div class="nem-newsletter-container">
        <h2 class="nem-newsletter-heading">ĐĂNG KÝ BẢN TIN</h2>
        <p class="nem-newsletter-desc">Đăng ký nhận bản tin NEM để được cập nhật những mẫu thiết kế mới nhất</p>

        <form class="nem-newsletter-form" action="#" method="post" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký nhận bản tin NEM!');">
            <input type="email" class="nem-newsletter-input" name="email" value="<?php echo esc_attr( $user_email ); ?>" placeholder="Vui lòng nhập email..." required />
            <button type="submit" class="nem-newsletter-submit">ĐĂNG KÝ</button>
        </form>

        <div class="nem-newsletter-social-icons">
            <!-- Facebook -->
            <a href="https://www.facebook.com/nemfashion.official" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-facebook" title="Facebook">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>
            <!-- Instagram -->
            <a href="https://www.instagram.com/nemfashion.official/" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-instagram" title="Instagram">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>
            <!-- YouTube -->
            <a href="https://www.youtube.com/channel/UCrDQ6euiL9CekivZmD9-MCA" target="_blank" rel="noopener noreferrer" class="nem-social-bubble nem-social-youtube" title="YouTube">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </a>
        </div>
    </div>
</section>
