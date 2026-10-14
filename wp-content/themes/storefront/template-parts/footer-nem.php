<?php
/**
 * NEM Footer Template Part
 * Designed to accurately match NEM Fashion style
 */

$theme_uri = get_template_directory_uri();
$img_dir   = $theme_uri . '/assets/images/nem/';
$home_url  = esc_url( home_url( '/' ) );
?>

<div class="nem-footer-wrapper">
    <!-- Main Footer Body -->
    <div class="nem-footer-body">
        <div class="nem-footer-container">
            <!-- Column 1: Company Information -->
            <div class="nem-footer-col nem-col-company">
                <h4 class="nem-footer-heading">NEM FASHION - THỜI TRANG CÔNG SỞ</h4>
                <div class="nem-company-info">
                    <p class="nem-info-text">Công ty TNHH Dịch vụ và Thương mại An Thành.</p>
                    <p class="nem-info-text">Số ĐKKD 0107861393, Sở KHĐT Tp. Hà Nội cấp ngày 04/10/2017</p>
                    <p class="nem-info-text">Địa chỉ: Lô 1+2, Ô quy hoạch E.2/NO7 đường Lâm Hạ phường Bồ Đề, quận Long Biên, Hà Nội</p>
                    <p class="nem-info-text nem-contact-row">
                        Chăm sóc khách hàng: <strong>0246.2591551</strong>
                    </p>
                    <p class="nem-info-text nem-contact-row">
                        Mua hàng online: <strong>0966.612.569</strong>
                    </p>
                    <p class="nem-info-text nem-contact-row">
                        Email: <strong>nemcskh@stripe-vn.com</strong>
                    </p>
                </div>
            </div>

            <!-- Column 2: Giới thiệu -->
            <div class="nem-footer-col nem-col-links">
                <ul class="nem-footer-menu">
                    <li><a href="#">Giới thiệu</a></li>
                    <li><a href="#">Triết lý kinh doanh tại NEM Fashion</a></li>
                    <li><a href="<?php echo $home_url; ?>blog/">NEM's Blog</a></li>
                    <li><a href="#">Hệ thống showroom</a></li>
                </ul>
            </div>

            <!-- Column 3: Chính sách & Hướng dẫn -->
            <div class="nem-footer-col nem-col-links">
                <ul class="nem-footer-menu">
                    <li><a href="#">Chính sách giao nhận - Vận chuyển</a></li>
                    <li><a href="#">Hướng dẫn thanh toán</a></li>
                    <li><a href="#">Tra cứu đơn hàng</a></li>
                    <li><a href="#">Hướng dẫn chọn Size</a></li>
                    <li><a href="#">Quy định đổi hàng</a></li>
                    <li><a href="#">Quy định bảo hành và sửa chữa</a></li>
                    <li><a href="#">Khách hàng thân thiết</a></li>
                    <li><a href="#">Hướng dẫn bảo quản sản phẩm</a></li>
                </ul>
            </div>

            <!-- Column 4: Phương thức thanh toán & BCT -->
            <div class="nem-footer-col nem-col-payment">
                <h5 class="nem-payment-heading">Phương thức thanh toán</h5>
                <div class="nem-payment-icons">
                    <div class="nem-payment-badge" title="Bank Transfer">
                        <img src="<?php echo $img_dir; ?>method_1.png" alt="eBANK TRANSFER" />
                    </div>
                    <div class="nem-payment-badge" title="Mastercard">
                        <img src="<?php echo $img_dir; ?>method_2.png" alt="Mastercard" />
                    </div>
                    <div class="nem-payment-badge" title="VISA">
                        <img src="<?php echo $img_dir; ?>method_3.png" alt="VISA" />
                    </div>
                    <div class="nem-payment-badge" title="JCB">
                        <img src="<?php echo $img_dir; ?>method_4.png" alt="JCB" />
                    </div>
                </div>

                <div class="nem-bct-wrapper">
                    <a href="http://online.gov.vn/Home/WebDetails/46878" target="_blank" rel="noopener noreferrer" title="Đã thông báo Bộ Công Thương">
                        <img src="<?php echo $img_dir; ?>bct.png" alt="Đã thông báo Bộ Công Thương" class="nem-bct-img" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Copyright Bar -->
    <div class="nem-footer-copyright">
        <div class="nem-copyright-container">
            <span class="nem-copyright-text">© 2020 - Bản quyền NEM</span>
        </div>
    </div>
</div>

<!-- Floating Action Widgets -->
<div class="nem-floating-actions">
    <!-- Facebook Messenger Button -->
    <a href="https://m.me/nemfashion.official" target="_blank" rel="noopener noreferrer" class="nem-float-btn nem-btn-messenger" title="Nhắn tin với chúng tôi qua Facebook Messenger">
        <svg viewBox="0 0 28 28" width="28" height="28" fill="#ffffff">
            <path d="M14 2C7.373 2 2 6.969 2 13.1c0 3.493 1.742 6.611 4.469 8.652v4.248l4.062-2.23c1.109.309 2.278.475 3.469.475 6.627 0 12-4.969 12-11.1S20.627 2 14 2zm1.375 14.938l-3.328-3.551-6.496 3.551 7.145-7.586 3.407 3.551 6.417-3.551-7.145 7.586z"/>
        </svg>
    </a>

    <!-- Back to top button -->
    <button type="button" class="nem-float-btn nem-btn-scrolltop" id="nemScrollToTopBtn" title="Lên đầu trang" aria-label="Scroll to top">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>
</div>
