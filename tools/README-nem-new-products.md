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

## Chỉnh sửa bố cục

Trang có nội dung Gutenberg sẽ hiển thị trực tiếp các khối đã lưu trong trình sửa trang. Có thể đổi chữ, ảnh, vị trí danh mục và thứ tự các thẻ sản phẩm rồi bấm **Lưu/Cập nhật**. Bộ lọc và phân trang tiếp tục dùng sản phẩm WooCommerce; không ghi thay đổi giá hoặc tồn kho từ các thẻ trang trí. Header dùng chung, bản tin và footer đã có trong nội dung sẽ không được chèn thêm lần nữa.

CSS `nem-new-products-blocks.css` dùng chung cho trang công khai và trình sửa; tay nắm kéo thả chỉ xuất hiện trong editor. Các trang khác giữ tài nguyên và template riêng.

Sau khi clone hoặc nhập database, thư mục `uploads/nem-new-products/` là dữ liệu được tạo lại và không được Git theo dõi. Nếu banner hoặc các ảnh thu nhỏ trong editor bị thiếu, chạy:

```powershell
php tools/repair-nem-new-products-banner.php
php tools/test-nem-new-products-editor.php
```

Lệnh sửa banner khôi phục từ ảnh nguồn trong theme và tạo lại thumbnail bằng WordPress, giữ nguyên ID ảnh và nội dung trang. Lệnh kiểm tra không ghi thay đổi trang hoặc sản phẩm vào database.
