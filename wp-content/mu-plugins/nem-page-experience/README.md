# NEM — Trang chủ và chi tiết sản phẩm

Module riêng cho `CK_NHOMF_CMS`. Chỉ thay template và nạp tài nguyên ở `is_front_page()` / `is_product()`.

## File mới

- `wp-content/mu-plugins/nem-page-experience.php`: đăng ký template, tài nguyên và các hook giao diện có điều kiện.
- `wp-content/mu-plugins/nem-page-experience/page.php`: hai bố cục, sử dụng nội dung `nem_home_content` và sản phẩm WooCommerce đang có.
- `pages.css`, `pages.js`: hiệu ứng và responsive có namespace `npe-`.
- `assets/`: ảnh tham chiếu NEM được bổ sung vì các attachment `uploads/nem-detail/...` hiện trỏ tới file thiếu. URL hiển thị được ánh xạ chỉ trên hai trang này. Không sửa bản ghi attachment hoặc upload cũ.

Không sửa theme cha, theme con, file WooCommerce, cấu hình hay nội dung sản phẩm. Các trang cửa hàng, bộ sưu tập, tìm kiếm, tài khoản, giỏ hàng và thanh toán giữ template hiện có.

## Chức năng

- Header sticky, menu desktop/mobile, mega menu danh mục, mở/đóng tìm kiếm và giỏ hàng xem nhanh.
- Banner chuyển tự động, mũi tên, chấm điều hướng, vuốt trên mobile, nút tạm dừng; dừng khi hover/focus/tab ẩn và tôn trọng reduced motion.
- Hover sản phẩm đổi sang góc chụp khác, lớp tối và nút viền trắng “XEM THÊM”; bổ sung ảnh đúng mã cho tám sản phẩm trang chủ vốn chưa có gallery.
- Gallery thumbnail hỗ trợ rê chuột và nhấn; kính lúp có vùng chọn trên ảnh và cửa sổ zoom bên phải, tự ẩn khi rời ảnh/cuộn/đổi ảnh. Điện thoại dùng lightbox; Escape/phím mũi tên và biến thể WooCommerce vẫn hoạt động.
- `reference.json` lưu nguồn ảnh và mô tả theo đúng SKU. Chỉ phần mô tả mẫu chung của sản phẩm khởi tạo được trình bày bằng nội dung tham chiếu tương ứng trong module; mô tả riêng sẵn có được giữ. Không ghi lại dữ liệu sản phẩm vào cơ sở dữ liệu.
- Tăng/giảm số lượng theo tồn kho; thêm giỏ và mua ngay dùng giỏ/checkout thật; không giả thành công hoặc bỏ qua biến thể.
- Khối thông tin có mã tham chiếu theo SKU, liên kết Zalo, size/màu, hướng dẫn size và nhãn số lượng theo mẫu. Sản phẩm simple chưa có thuộc tính dùng lựa chọn tham chiếu trong module, kiểm tra nonce/giá trị và lưu cùng item khi khách mua; giá/kho vẫn theo WooCommerce của shop. Sản phẩm hết hàng vẫn hiện thông tin nhưng khóa nút mua, không đổi kho để bắt chước ảnh mẫu.
- Header căn biểu tượng tài khoản, tên người đang đăng nhập và bộ đếm giỏ hàng; khách chưa đăng nhập hiển thị “Tài khoản”. Không sửa màn hình đăng nhập.
- Lưu yêu thích và lịch sử xem trong localStorage riêng (`npe_favorites`, `npe_recent`). Không sửa dữ liệu các plugin yêu thích khác.
- Popup hướng dẫn size lấy từ trang hướng dẫn sẵn có, mở form đánh giá WooCommerce, đăng ký bản tin dùng handler hiện có.

## Kiểm chứng

Đã kiểm tra PHP syntax, Chrome desktop/mobile, banner, tìm kiếm, gallery và ảnh tải thực tế, lightbox, chọn size/màu, quantity, yêu thích, popup size/đánh giá, chiều rộng 390/820/1440 px.

Đã kiểm tra riêng opacity/ảnh thứ hai của cả tám thẻ trang chủ, tọa độ kính lúp khớp con trỏ, background zoom thay đổi theo vị trí, không tràn viewport, rê thumbnail đổi ảnh, mô tả AL62372 và không nạp CSS module ở trang khác.

Đã thử thêm biến thể Q02462 với số lượng 2 trong phiên khách riêng, kiểm tra giỏ thật; thử Mua ngay đi tới checkout hiện có và xóa giỏ thử. Không đặt đơn, không đổi giá, kho hoặc nội dung sản phẩm. Trang tài khoản vẫn không có giao diện module mới.

Muốn trở về giao diện trước: đổi tên riêng file `wp-content/mu-plugins/nem-page-experience.php` thành `nem-page-experience.php.disabled` để WordPress không nạp module. Không cần sửa hoặc khôi phục bất kỳ file cũ nào.

Tham chiếu: https://nemshop.vn/ và ảnh chi tiết người dùng cung cấp. Nội dung/ảnh banner sẵn có của trang chủ được giữ nguyên.
