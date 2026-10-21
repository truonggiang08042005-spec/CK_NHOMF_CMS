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
