# Sản phẩm mới

Trang `/san-pham-moi/` sử dụng `page-san-pham-moi.php`, module `inc/nem-new-products.php`, CSS và JS riêng. Liên kết cũ `/shop/?orderby=date` cũng mở giao diện này.

Danh sách hiển thị sản phẩm WooCommerce đã xuất bản, sắp theo ngày tạo mới nhất và ID; mỗi trang 12 sản phẩm. Phân trang lấy số lượng thực tế, không cố định 17 trang. Bộ lọc danh mục, kích cỡ (`pa_kich-co`), màu (`pa_mau-sac`) và khoảng giá kết hợp với nhau; chọn nhiều kích cỡ/màu dùng OR trong từng nhóm và AND giữa các nhóm. Bộ lọc giữ nguyên khi đổi trang hoặc số cột. Sản phẩm ẩn khỏi catalog không xuất hiện.

Dữ liệu tham chiếu của 12 sản phẩm đầu, ảnh và các biến thể nằm trong `nem-new-products-assets.json`, nguồn https://nemshop.vn/collections/san-pham-moi. Ảnh được tối ưu WebP và lưu trong theme; bản sao Media Library nằm trong `uploads/nem-new-products/`. Không thay đổi giá hoặc biến thể của sản phẩm đã tồn tại khi chạy lại script.

Khi cài trên database khác, chạy từ thư mục gốc:

```powershell
php -d memory_limit=512M tools/setup-nem-new-products.php
php tools/test-nem-new-products.php
```

Sản phẩm mới tạo có đầy đủ biến thể và giá tham chiếu; cửa hàng cần quản lý tồn kho thực tế trong WooCommerce. Thêm sản phẩm mới tại Sản phẩm, gán danh mục và thuộc tính chung Kích cỡ/Màu sắc để chúng tham gia các bộ lọc.
