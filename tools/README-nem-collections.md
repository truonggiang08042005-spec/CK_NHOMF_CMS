# Bộ sưu tập NEM

Trang: `/bo-suu-tap/` (template `page-bo-suu-tap.php`).

Sau khi lấy mã nguồn sang một cơ sở dữ liệu WordPress khác, chạy từ thư mục gốc:

```powershell
php -d memory_limit=512M tools/setup-nem-collections.php
```

Script tạo trang nếu chưa có, thêm các sản phẩm còn thiếu theo SKU, gắn nhãn bộ sưu tập và đưa ảnh vào Media Library. Chạy lại không tạo thêm trang hoặc sản phẩm trùng; giá và tồn kho của sản phẩm đã có được giữ nguyên. Khi ảnh WebP đã có trong theme, script sử dụng ảnh cục bộ. Ảnh chính và ảnh thay thế của sản phẩm vẫn có thể được quản lý trong WooCommerce; bộ ảnh dùng riêng trên trang Bộ sưu tập được lưu ở metadata `_nem_collection_images`.

Hai bộ sưu tập hiện có sản phẩm là `thoi-nha` và `nhe-buoc-moi-ngay`. Menu `giao-thoi` và `nhip-thanh-pho` dẫn đến bộ lọc tương ứng; thêm nhãn sản phẩm có slug `bst-giao-thoi` hoặc `bst-nhip-thanh-pho` để đưa sản phẩm vào các bộ sưu tập này.

Ảnh tham chiếu: https://nemshop.vn/pages/bo-suu-tap (URL gốc nằm trong `nem-collection-assets.json`). Ảnh sử dụng trên trang được lưu tại `wp-content/themes/storefront/assets/images/nem/collections/` và đã tối ưu sang WebP. Giới hạn xử lý ảnh chỉ được nâng cho tiến trình CLI, không thay đổi cấu hình PHP của website.

Kiểm tra tích hợp: `php tools/test-nem-collections.php`. Kiểm tra trạng thái menu: `node tools/test-nem-header.cjs`.

## Chỉnh sửa bố cục bằng Gutenberg

Khi trang Bộ sưu tập có các khối Gutenberg, template hiển thị trực tiếp nội dung đã lưu. Hai bộ sưu tập, banner, ảnh phối cảnh, thẻ sản phẩm, mô tả, liên kết và bản tin có thể sửa hoặc di chuyển bằng trình chỉnh sửa WordPress. Header và footer tiếp tục dùng mẫu chung. Không chèn thêm bản tin khi phần này đã nằm trong thiết kế.

`nem-collections-blocks.css` dùng chung cho editor và trang công khai, giữ hai sản phẩm mỗi hàng và tỷ lệ hai cột của mẫu. CSS và thao tác kéo thả riêng của SALE được loại khỏi editor Bộ sưu tập. Dùng tay nắm Gutenberg hoặc Danh sách khối để di chuyển các thành phần.

Với bản nội dung block đã nhập từ database trước đây, chạy một lần:

```powershell
php tools/setup-nem-collections-editor.php
php tools/test-nem-collections-editor.php
```

Lệnh thiết lập giữ lại hình ảnh, mô tả và thứ tự đã có, chuẩn hóa thuộc tính block và chuyển các liên kết mạng xã hội thành khối chỉnh sửa được. Nội dung gốc được sao lưu trong metadata của trang. Các bài kiểm tra chỉ đọc dữ liệu; sửa giá trong khối trình bày không thay đổi giá WooCommerce.
