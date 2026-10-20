# Trang chủ NEM

Đã triển khai tại child theme `wp-content/themes/storefront-child/`.
Trang: `http://wordpress.local/CK_NHOMF_CMS/`.

## Quản lý nội dung

- Giao diện → Trang chủ NEM: tiêu đề, banner (ID ảnh trong Thư viện), sản phẩm hiển thị, bài viết, thông tin công ty, menu và liên kết.
- Sản phẩm: 8 sản phẩm có SKU bắt đầu bằng `NEM-`, giá và ảnh giống dữ liệu mẫu. Chưa có tồn kho thực tế nên các sản phẩm mẫu để hết hàng; cập nhật kho khi kinh doanh.
- Bài viết: 3 bài NEM, nội dung giới thiệu được biên soạn cho bản mẫu, không phải toàn văn bài gốc.
- Trang: trang chủ và các trang thông tin ở chân trang. Nội dung chính sách là nội dung mẫu cần điều chỉnh theo cửa hàng.
- Đăng ký bản tin: danh sách email đăng ký thành công. Chỉ lưu email, chưa gửi email tự động.

Ảnh được lưu cục bộ trong `wp-content/uploads/nem-home/`, đăng ký vào Thư viện và liên kết qua ID trong database. Banner chính `hero-sale70.png` đã được dựng lại bằng built-in image_gen theo ảnh tham chiếu: nền vàng, đầm ren xanh, giảm 70%, ngày 25.9–4.10. Đây là bản tái tạo, không phải ảnh chiến dịch gốc. Prompt được lưu tại `tools/nem-banner-prompt.txt`. Có thể thay trong Giao diện → Trang chủ NEM. Script CLI `tools/update-nem-banner.php` đăng ký ảnh và cập nhật banner trong database.

## Database và cài đặt lại

Database đang chạy đã được cập nhật. `db/wp_nhomf_nem.sql` là bản xuất đầy đủ sau thay đổi, giữ nguyên `db/wp_nhomf.sql` gốc. Khi chuyển máy, chép cả source/ảnh và nhập SQL vào database riêng, điều chỉnh wp-config và URL bằng công cụ hỗ trợ dữ liệu serialize của WordPress.

Các script chỉ chạy CLI:

```powershell
php -d memory_limit=512M tools/setup-nem.php
php -d memory_limit=512M tools/verify-nem.php
php -d memory_limit=512M tools/export-nem.php
```

Setup có thể chạy lại: dùng SKU, slug và metadata để tránh tạo trùng; giữ cấu hình trang chủ đã có. Không sửa template `woocommerce/templates/auth/form-login.php`.
