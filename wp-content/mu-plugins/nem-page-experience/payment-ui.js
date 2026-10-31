(function ($) {
    'use strict';
    $(function () {
        const qr = document.getElementById('woocommerce_bacs_npe_qr_image');
        if (qr && window.wp?.media) {
            const choose = $('<button type="button" class="button">Chọn / thay ảnh QR</button>');
            const clear = $('<button type="button" class="button">Bỏ ảnh QR</button>');
            $(qr).after(choose, ' ', clear);
            choose.on('click', function () {
                const frame = wp.media({ title: 'Chọn QR tài khoản ngân hàng nhận tiền', library: { type: 'image' }, multiple: false });
                frame.on('select', () => { qr.value = frame.state().get('selection').first().get('id'); $(qr).trigger('change'); }); frame.open();
            });
            clear.on('click', () => { qr.value = ''; $(qr).trigger('change'); });
        }
        function updateEmail() {
            const email = document.getElementById('billing_email');
            if (email) email.required = document.querySelector('input[name="payment_method"]:checked')?.value === 'momo_card';
        }
        $(document.body).on('change','input[name="payment_method"]',updateEmail).on('updated_checkout',updateEmail);
        updateEmail();
        $(document).on('click','.npe-copy-payment',async function () {
            const text = this.dataset.copy || '';
            const status = this.closest('.npe-bank-instructions')?.querySelector('.npe-copy-status');
            try {
                await navigator.clipboard.writeText(text);
                if (status) status.textContent = 'Đã sao chép: ' + text;
                const label = this.textContent; this.textContent = 'Đã chép ✓';
                setTimeout(() => { this.textContent = label; }, 1500);
            }
            catch { if (status) status.textContent = 'Vui lòng chọn và sao chép thủ công: ' + text; }
        });
        // Dynamic VietQR rendered locally (WooCommerce's bundled jquery.qrcode).
        $('.npe-vietqr').each(function () {
            if ($.fn.qrcode) $(this).qrcode({ text: this.dataset.qr, width: 240, height: 240, correctLevel: 1 });
        });
    });
})(jQuery);
