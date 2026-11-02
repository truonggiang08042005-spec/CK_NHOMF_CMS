# Chỉnh sửa trang Tài khoản

Trang WooCommerce Tài khoản (hiện là trang #10, `/my-account/`) dùng các khối Gutenberg làm nguồn bố cục. Tên, điện thoại, quốc gia, địa chỉ và đơn hàng được lấy theo khách đang đăng nhập, không lưu thông tin khách vào nội dung thiết kế. Header và footer tiếp tục dùng chung với website.

Sau khi lấy code về máy khác, chạy một lần:

```powershell
php tools/setup-nem-account-editor.php
```

Script sao lưu shortcode cũ vào metadata `_nem_account_editor_original_content`. Chạy lại không ghi đè thiết kế đã chỉnh sửa; nếu trang đã có nội dung riêng khác shortcode, script dừng để giữ nội dung đó.

Mở trang Tài khoản trong WordPress, dùng bảng **Thiết kế trang Tài khoản → Bố cục đang chỉnh sửa** để chọn khách đã đăng nhập, chưa đăng nhập hoặc tất cả. Dùng **Danh sách khối** để di chuyển menu, các cột, trường thông tin, tiêu đề, mô tả, bản tin và liên kết mạng xã hội. Trong editor, cả ba bảng thông tin tài khoản, đơn hàng và giao hàng đều hiện để chỉnh; ngoài website chúng hoạt động theo tab. Có thể thêm khối ảnh từ Media Library như các trang Bộ sưu tập và Sản phẩm mới.

Các khối giá trị tự động hiển thị nội dung mẫu trong editor; dữ liệu thật chỉ được chèn khi khách xem trang. Có thể đổi nhãn và thứ tự trường; giữ các lớp CSS chức năng và HTML anchor trong phần nâng cao để các form và tab tiếp tục hoạt động. Đăng nhập, đăng ký và bản tin giữ nonce và bộ xử lý hiện có; hộp sửa địa chỉ tiếp tục dùng AJAX. Các đường dẫn khôi phục mật khẩu, xem đơn, thanh toán và sửa địa chỉ riêng được giao cho WooCommerce.

Kiểm tra:

```powershell
php tools/test-nem-account-editor.php
php tests/nem-account-integration.php
node tools/test-nem-profile.cjs
```

Các bài kiểm tra PHP tạo khách/đơn hàng tạm và xóa trong `finally`; không sửa nội dung thiết kế đã lưu. Bài kiểm tra JavaScript dùng DOM và phản hồi mạng giả lập để kiểm tra chuyển tab, lưu/xóa địa chỉ và trường hợp xóa bớt khối hiển thị. CSS `nem-account-blocks.css` dùng chung cho editor và trang công khai, chỉ nạp trên trang Tài khoản. Editor Tài khoản không nạp CSS/JS kéo thả riêng của SALE.
