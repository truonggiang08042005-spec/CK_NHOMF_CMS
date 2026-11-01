# Sửa trực quan ba trang NEM

Vào **Pages → All Pages** và chọn **Trang chủ NEM**, **Chi tiết sản phẩm** hoặc **Thanh toán**. Bấm tiêu đề trang hoặc **Edit Page · Sửa trực quan** để mở giao diện thật trong khung chỉnh sửa. Khi đang xem trang chủ, một sản phẩm hoặc thanh toán, nút **Edit Page · Sửa trực quan** trên thanh WordPress cũng mở đúng trình sửa tương ứng.

- Bấm vào tiêu đề, đoạn chữ hoặc nhãn có viền xanh để gõ nội dung mới.
- Chọn chữ rồi chỉnh cỡ chữ, màu, độ đậm, căn lề và chiều cao dòng ở cột trái.
- Kéo tay nắm của khối trên trang, hoặc kéo khối trong danh sách **Bố cục**. Nút ↑ / ↓ hỗ trợ thao tác bằng bàn phím.
- Trang chủ: kéo ô ảnh nổi bật sang trái/phải, kéo đổi thứ tự sản phẩm/bài viết; có thể kéo sản phẩm sang nhóm sản phẩm khác.
- Chi tiết sản phẩm: kéo trực tiếp ảnh chính hoặc tay nắm **Ảnh** sang phía nội dung; kéo tay nắm **Nội dung** sang phía ảnh để đổi chỗ hai cột. Khung nét đứt chỉ vị trí sẽ thả. Bấm **Lưu thay đổi** để giữ bố cục mới.
- Thanh toán: kéo tay nắm của khối từ cột trái sang cột phải hoặc vùng toàn chiều ngang. Có thể thả vào cột đang trống. Các khối liên hệ, địa chỉ, thanh toán và tóm tắt đơn hàng luôn được giữ.
- Mở **Nội dung, ảnh và liên kết** để chọn ảnh từ Thư viện, đổi đường dẫn hoặc nội dung không tiện bấm trên trang. Danh sách ID sản phẩm/bài viết được tải lại sau khi lưu.
- Chuyển chế độ máy tính, máy tính bảng hoặc điện thoại ở thanh trên.
- Bấm **Lưu thay đổi** để áp dụng cho website. Nếu rời trang khi chưa lưu, trình duyệt nhắc xác nhận.

Bố cục và kiểu chữ ở trang chi tiết áp dụng cho mọi sản phẩm. Menu **Sản phẩm xem trước** chọn sản phẩm cụ thể. Sửa tên hoặc mô tả ngay trên bản xem trước chỉ thay dữ liệu của sản phẩm đó. Liên kết **Sửa ảnh, giá, màu và size sản phẩm** mở màn hình WooCommerce chuẩn.

Thanh toán trong khung sửa dùng một đơn hàng minh họa, bỏ trống thông tin khách, tắt thao tác mua hàng và không thêm sản phẩm vào giỏ. Đơn hàng minh họa chỉ hiện trong bản xem trước đã xác thực. Quản trị viên có thể mở trang thanh toán với giỏ trống để truy cập Edit Page; chuyển hướng giỏ trống của khách mua vẫn dùng hành vi WooCommerce.

## Cài đặt và dữ liệu

Chạy CLI một lần: `php tools/setup-nem-visual-editor.php`. Chạy lại không tạo trùng trang. Trang chủ và thanh toán dùng Page hiện có; chỉ bổ sung Page **Chi tiết sản phẩm**. Công cụ giữ bản sao cấu hình và hai Page ban đầu trong option `npe_visual_import_backup`. Không thay nội dung checkout block gốc.

Module ở `wp-content/mu-plugins/nem-page-experience/visual-editor.php`, `visual-editor.js`, `visual-editor.css`. Nó dùng cùng `page.php` và `checkout.php` với website, không cần cài thêm trình dựng trang. Trang thường tiếp tục dùng trình sửa WordPress vốn có.

Các giá trị chữ/ảnh/bố cục tiếp tục dùng `nem_home_content` và `npe_page_builder`; kiểu chữ ở `npe_visual_styles`. Mỗi lần lưu giữ tối đa 10 bản cấu hình trước đó trong meta `_npe_visual_history` của Page. Lưu qua AJAX kiểm tra nonce, quyền quản trị và quyền sửa Page; dữ liệu sản phẩm còn kiểm tra quyền sửa sản phẩm tương ứng. CSS chỉ nhận selector và thuộc tính nằm trong danh sách cho phép.

## Kiểm tra

`php tests/nem-visual-editor-integration.php` kiểm tra All Pages, đường dẫn Edit Page, HTML bản xem trước, lưu và hiển thị công khai chữ/cỡ chữ/thứ tự khối, cột thanh toán và chặn yêu cầu không có quyền. Test dùng phiên xác thực tạm của quản trị viên hiện có, phục hồi cấu hình sau khi chạy và xóa phiên tạm; không tạo hoặc thanh toán đơn hàng. Chỉ chạy ở site local.

Kiểm tra thủ công trên Chrome: mở lần lượt ba trang, sửa chữ và cỡ chữ, kéo khối trong canvas/danh sách, chuyển kích thước xem trước, chọn ảnh, lưu và tải lại trang. Kiểm tra tên/mô tả trên một sản phẩm thử riêng nếu cần thử chỉnh dữ liệu sản phẩm.
