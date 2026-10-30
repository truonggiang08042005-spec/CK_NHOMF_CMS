# Quản trị giao diện NEM

Mở **Giao diện NEM → Bố cục & nội dung** (`/wp-admin/admin.php?page=npe-builder`).

- **Trang chủ**: đổi logo, banner, ảnh khối nổi bật/bộ sưu tập, tiêu đề, liên kết, danh sách ID sản phẩm và bài viết; kéo đổi thứ tự từng khối hoặc dùng nút lên/xuống, bật/tắt khối.
- **Chi tiết sản phẩm**: đổi vị trí ảnh trái/phải, tiêu đề/nhãn/liên kết tư vấn, ảnh và chữ của khối ưu đãi; sắp xếp thông tin sản phẩm, đánh giá, sản phẩm liên quan, đã xem và bản tin. Tên, giá, ảnh đại diện, album, biến thể và mô tả của từng sản phẩm sửa tại **Sản phẩm → Sửa sản phẩm**.
- **Thanh toán**: đổi tiêu đề, nút đặt hàng, ảnh và chữ thông báo; kéo đổi thứ tự và chọn cột chính, cột phải hoặc toàn chiều ngang. Các khối liên hệ, địa chỉ, phương thức thanh toán và tóm tắt đơn luôn được giữ để form đặt hàng hoạt động.

Bấm **Chọn / Thay ảnh** để tải ảnh lên Thư viện hoặc chọn ảnh đang có, sau đó lưu. Các thay đổi được áp dụng trực tiếp; dữ liệu trang chủ cũ trong `nem_home_content` được bảo toàn.

## Menu xổ xuống

Vào **Giao diện → Menu**, tạo/chọn menu rồi gán vào vị trí **NEM — Menu chính và danh mục**. Kéo mục vào dưới mục cha để tạo menu con. Menu này được dùng chung trên trang chủ, chi tiết sản phẩm và các trang Storefront khác.

Khi chưa gán menu, hệ thống dùng nhóm Sản phẩm, Sản phẩm mới, NEM Plus+, Sản phẩm sale và Bộ sưu tập theo giao diện mẫu. Danh mục thật được ưu tiên; nếu chưa có danh mục/bộ sưu tập tương ứng, liên kết dẫn đến tìm kiếm sản phẩm thay vì đường dẫn không tồn tại. Có thể thay các liên kết này trong trình quản lý Menu.

Menu mở khi rê chuột hoặc đặt focus; trên điện thoại bấm nút mũi tên. Hỗ trợ Tab, phím mũi tên và Escape.

## Thanh toán

Tab Thanh toán có liên kết nhanh đến cài đặt ngân hàng, MoMo, Payoo và COD. Xem `README-nem-payments.md` để chuyển giữa mô phỏng local, sandbox và cổng thật. Nhập thông tin tài khoản nhận tiền tại **WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng** để khách có thể chọn chuyển khoản.
