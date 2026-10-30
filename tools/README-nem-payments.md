# Thanh toán NEM

## Thiết lập

Vào **WooCommerce → Cài đặt → Thanh toán**. Cửa hàng dùng VND.

- **Chuyển khoản ngân hàng**: dùng gateway BACS của WooCommerce; nhập đúng tên tài khoản, số tài khoản, ngân hàng và hướng dẫn. Đơn chuyển khoản chỉ được xác nhận sau khi cửa hàng đối chiếu tiền thực nhận.
- **COD**: dùng gateway COD của WooCommerce.
- **Mô phỏng local khi chưa có tài khoản**: trong cài đặt MoMo hoặc Payoo, chọn **Bật phương thức thanh toán** và **Mô phỏng thanh toán — không trừ tiền** rồi lưu. Chỉ hoạt động trên localhost, loopback hoặc domain `.local`/`.test`. Khách được chuyển tới màn hình ghi rõ mô phỏng và có thể chọn thành công/thất bại/hủy; không gọi nhà cung cấp. Thành công mô phỏng chỉ để đơn **on-hold**, không ghi nhận đã nhận tiền, không gọi `payment_complete`, không giảm tồn kho thật. Kết quả lưu tại `_npe_demo_result`; thông tin môi trường lưu tại `_npe_payment_demo`.
- **MoMo**: mở phương thức MoMo, nhập Partner Code, Access Key và Secret Key do MoMo cấp. Mặc định tắt và chọn Sandbox. Bật sau khi đã nhập khóa môi trường thử nghiệm. Không dùng khóa Production cho Sandbox.
- **Payoo**: đã có trang cấu hình tài khoản doanh nghiệp, Shop ID, Shop Title, domain, Checksum Key và nhóm thẻ/QR/ví điện tử. Chưa có tài khoản merchant nên chưa kích hoạt nhận tiền. Phải bổ sung adapter IPN đã xác minh với bộ tài liệu Payoo cấp riêng cho merchant trước khi phương thức này khả dụng; hệ thống chủ động khóa nếu thiếu adapter.

Khóa bí mật chỉ nhập trong quản trị. Không gửi khóa trong mã nguồn hoặc ảnh chụp. Cấu hình lưu theo WooCommerce settings; quản trị website và người có quyền database có thể đọc cấu hình này.

Trang thanh toán chỉ chuyển khách tới trang bảo mật của nhà cung cấp; website không thu số thẻ hoặc CVV.

## Sandbox và địa chỉ callback

Sandbox vẫn cần tài khoản và khóa thử nghiệm. `wordpress.local` không nhận được callback từ Internet. Để kiểm thử end-to-end, dùng domain HTTPS công khai do bạn quản lý, được nhà cung cấp chấp thuận, và cấu hình callback theo URL hiển thị trong trang cài đặt gateway.

Mô phỏng local là một chế độ riêng, không phải Sandbox của MoMo/Payoo. Nó cần được bật rõ ràng, mặc định tắt; không tự đổi cấu hình database. Liên kết mô phỏng có chữ ký HMAC ràng buộc order key, gateway, lần thử và hạn một giờ. Gửi kết quả dùng POST kèm nonce; callback trình duyệt không thể sửa một đơn thật thành đã trả tiền. Khi chuyển từ mô phỏng sang cổng thật, tạo đơn mới.

MoMo:

- IPN: `/?wc-api=npe_momo_ipn`
- Quay lại: `/?wc-api=npe_momo_return`

Đường dẫn nằm dưới URL WordPress hiện tại; lấy URL đầy đủ ở phần cài đặt MoMo. IPN chỉ nhận POST JSON, kiểm tra HMAC-SHA256, partner, order ID, request ID, số tiền, VND, môi trường và transaction ID. Callback lặp không hoàn tất đơn hai lần; các lần thử trước được giữ lại để xác minh IPN đến trễ. Chuyển trang về từ trình duyệt không xác nhận thanh toán. Chỉ `resultCode=0` qua IPN Production hợp lệ được `payment_complete`. Kết quả Sandbox hợp lệ được lưu riêng và giữ đơn `on-hold`, không giảm tồn kho, không gửi email giao dịch và không ghi nhận tiền thật. Admin và trang kết quả luôn ghi rõ đơn thử nghiệm.

## Trạng thái Payoo và giao diện adapter

Đã xác minh từ tài liệu chính thức:

- Sandbox: `https://newsandbox.payoo.com.vn/v2/checkout`; Production: `https://payoo.vn/v2/checkout`.
- Tạo `OrderXML`, checksum SHA512(ChecksumKey + OrderXML), gửi JSON và chuyển tới `payment_url` từ phản hồi có checksum hợp lệ.
- Kiểm tra checksum phản hồi trên nguyên văn giá trị `order`, ID và số tiền, chỉ nhận URL HTTPS ở đúng host Payoo.
- `payment_group` mặc định `cc,bank-account,qrcode,payoo-account`; quyền thực tế theo hợp đồng merchant.

Tài liệu IPN đầy đủ/hiện hành cho merchant chưa được cung cấp. Bản PDF chính thức hiện không tải trực tiếp được; phần trích chỉ mục không đủ xác nhận schema callback và phép chuẩn hóa chữ ký. Vì vậy mã không đoán giao thức IPN hoặc đánh dấu đã trả tiền từ URL quay lại.

Adapter Payoo được nhà cung cấp xác nhận phải:

1. Trả `true` cho filter `npe_payoo_notification_adapter_ready` sau khi đủ cấu hình hợp đồng/môi trường.
2. Xử lý action `npe_payoo_handle_verified_ipn`, xác thực chữ ký trên raw body đúng tài liệu merchant, khớp merchant/shop, order number, VND/số tiền và môi trường; chặn transaction ID dùng lại ở đơn khác, tuần tự hóa cập nhật và xử lý callback lặp.
3. Chỉ gọi `payment_complete` sau khi trạng thái thanh toán thành công được xác thực; trả acknowledgement đúng hợp đồng Payoo rồi `exit`. Nếu action không trả response, gateway trả HTTP 501.

Không bật Payoo Production trước khi adapter và các ca kiểm thử callback đã qua nghiệm thu với Payoo.

## Kiểm tra tự động không dùng tiền thật

Chạy từ thư mục gốc:

```text
php tests/nem-payments-unit.php
php tests/nem-checkout-integration.php
```

Kiểm tra này giả lập toàn bộ HTTP, order và storage. Bao gồm chữ ký, thay đổi số tiền/request/partner, callback lặp, transaction ID dùng lại, URL giả mạo, nguyên vẹn checksum Payoo, khóa khi chưa cấu hình, các kết quả mô phỏng và giới hạn domain local. Không tạo đơn trong database, không gọi cổng thanh toán thật.

Bộ `nem-checkout-integration.php` chạy trên WordPress local, chặn toàn bộ HTTP bên ngoài và email, tạo sản phẩm/đơn thử rồi dọn trong `finally`. Kiểm tra WooCommerce thật cho chuyển khoản, COD, mô phỏng MoMo/Payoo và các fragment tổng tiền/phương thức. Chuyển khoản không được cho chọn khi admin chưa nhập tài khoản nhận tiền.

## Tài liệu chính thức

- [MoMo – Thanh toán thông thường và chữ ký IPN](https://developers.momo.vn/v3/vi/docs/payment/api/wallet/onetime/)
- [Payoo – Payment Gateway Integration](https://developers.payoo.vn/docs/PayooPGW_IntegrationDocument_GooglePay.pdf) — trang 5, 7–9, 11 và 13.
