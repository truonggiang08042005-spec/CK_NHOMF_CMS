<?php
/** Delivery editor shared with the native account design. Customer data is never saved in blocks. */
defined( 'ABSPATH' ) || exit;
$customer = nem_account_customer_data();
?>
<div class="nem-modal-overlay" id="nemAddressModal" aria-hidden="true">
    <div class="nem-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="nemModalTitle">
        <button type="button" class="nem-modal-close-btn" id="nemModalCloseBtn" aria-label="Đóng">&times;</button>
        <h3 class="nem-modal-title" id="nemModalTitle">CHỈNH SỬA ĐỊA CHỈ</h3>
        <form id="nemAddressForm">
            <p id="nemAddressFormStatus" role="alert"></p>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputName">Họ và tên</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputName" value="<?php echo esc_attr( $customer['fullname'] ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputPhone">Số điện thoại</label>
                <input type="tel" class="nem-modal-input" maxlength="10" inputmode="numeric" pattern="[0-9]{1,10}" title="Chỉ nhập chữ số, tối đa 10 ký tự" id="nemModalInputPhone" value="<?php echo esc_attr( $customer['phone'] ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputCountry">Quốc gia</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputCountry" value="<?php echo esc_attr( $customer['country'] ); ?>" required />
            </div>
            <div class="nem-modal-form-group">
                <label class="nem-modal-label" for="nemModalInputAddress">Địa chỉ chi tiết</label>
                <input type="text" class="nem-modal-input" maxlength="250" id="nemModalInputAddress" value="<?php echo esc_attr( $customer['address'] ); ?>" required />
            </div>
            <div class="nem-modal-actions">
                <button type="submit" class="nem-modal-btn-submit" id="nemModalBtnSubmit">LƯU THAY ĐỔI</button>
                <button type="button" class="nem-modal-btn-cancel" id="nemModalBtnCancel">HỦY</button>
            </div>
        </form>
    </div>
</div>
