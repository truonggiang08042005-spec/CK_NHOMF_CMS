(function () {
    'use strict';
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const resultForm = $('.sim-result-form');
    const digits = (v) => (v || '').replace(/\D/g, '');
    let otpTries = 0;
    let pendingResult = 'success';
    let finished = false;

    function show(step) {
        $$('.sim-step').forEach((el) => { el.hidden = el.dataset.step !== step; });
        const first = $(`.sim-step[data-step="${step}"] input`);
        if (first) first.focus();
    }

    function finish(result) {
        if (finished) return;
        finished = true;
        resultForm.elements.demo_result.value = result;
        if (result === 'cancel') { resultForm.submit(); return; }
        show('processing');
        $('.sim-cancel').hidden = true;
        setTimeout(() => resultForm.submit(), 1600);
    }

    function error(form, text) {
        const box = $('.sim-error', form);
        box.textContent = text;
        box.hidden = !text;
    }

    // Countdown — an expired session is treated as a failed payment.
    const countdown = $('[data-sim-countdown]');
    if (countdown) {
        let left = Math.max(0, parseInt(countdown.dataset.simCountdown, 10) || 0);
        const tick = () => {
            countdown.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
            if (left <= 0) { clearInterval(timer); finish('failure'); }
            left--;
        };
        const timer = setInterval(tick, 1000);
        tick();
    }

    // MoMo QR code (generated locally by WooCommerce's bundled jquery.qrcode).
    const qr = $('.sim-qr');
    if (qr && window.jQuery && jQuery.fn.qrcode) jQuery(qr).qrcode({ text: qr.dataset.qr, width: 220, height: 220, correctLevel: 1 });

    document.addEventListener('click', (event) => {
        const go = event.target.closest('[data-go]');
        if (go) { show(go.dataset.go); return; }
        const result = event.target.closest('[data-result]');
        if (result) {
            if (result.dataset.result === 'cancel' && !confirm('Bạn chắc chắn muốn hủy giao dịch?')) return;
            finish(result.dataset.result);
            return;
        }
        const bank = event.target.closest('.sim-bank');
        if (bank) {
            $$('[data-bank-name]').forEach((el) => { el.textContent = bank.dataset.bank; });
            show('card');
        }
    });

    // Card helpers.
    const luhn = (num) => {
        let sum = 0;
        for (let i = 0; i < num.length; i++) {
            let d = Number(num[num.length - 1 - i]);
            if (i % 2) { d *= 2; if (d > 9) d -= 9; }
            sum += d;
        }
        return num.length > 0 && sum % 10 === 0;
    };
    const brandOf = (num) => {
        if (/^4/.test(num)) return 'visa';
        if (/^(5[1-5]|2(2[2-9]|[3-6]\d|7[01]|720))/.test(num)) return 'mastercard';
        if (/^35(2[89]|[3-8]\d)/.test(num)) return 'jcb';
        if (/^3[47]/.test(num)) return 'amex';
        return '';
    };
    const groupNumber = (num) => (brandOf(num) === 'amex' ? num.replace(/^(\d{0,4})(\d{0,6})(\d{0,5}).*/, '$1 $2 $3') : num.replace(/(\d{4})(?=\d)/g, '$1 ')).trim();

    $$('.sim-card-form').forEach((form) => {
        const field = (name) => $(`[data-field="${name}"]`, form);
        const mirror = (name, value, fallback) => { const el = $(`[data-mirror="${name}"]`, form); if (el) el.textContent = value || fallback; };
        const kind = form.dataset.kind;

        field('number').addEventListener('input', (e) => {
            const num = digits(e.target.value).slice(0, 19);
            e.target.value = groupNumber(num);
            mirror('number', e.target.value, kind === 'atm' ? '9704 •••• •••• ••••' : '•••• •••• •••• ••••');
            if (kind === 'intl') {
                const brand = brandOf(num);
                mirror('brand', brand ? brand.toUpperCase() : 'CARD', 'CARD');
                $$('[data-brand]', form).forEach((b) => b.classList.toggle('is-active', b.dataset.brand === brand));
            }
        });
        field('name').addEventListener('input', (e) => {
            e.target.value = e.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/gi, 'D').toUpperCase().replace(/[^A-Z ]/g, '');
            mirror('name', e.target.value, kind === 'atm' ? 'TEN CHU THE' : 'CARDHOLDER NAME');
        });
        field('date').addEventListener('input', (e) => {
            const d = digits(e.target.value).slice(0, 4);
            e.target.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
            mirror('date', e.target.value, 'MM/YY');
        });
        if (field('cvv')) field('cvv').addEventListener('input', (e) => { e.target.value = digits(e.target.value).slice(0, 4); });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const num = digits(field('number').value);
            const name = field('name').value.trim();
            const [mm, yy] = field('date').value.split('/').map(Number);
            const now = new Date();
            const curYY = now.getFullYear() % 100;
            const curMM = now.getMonth() + 1;
            if (!mm || mm > 12 || isNaN(yy) || field('date').value.length !== 5) return error(form, 'Ngày trên thẻ không hợp lệ (MM/YY).');
            if (name.split(' ').filter(Boolean).length < 2) return error(form, 'Vui lòng nhập họ tên chủ thẻ như in trên thẻ.');
            if (kind === 'atm') {
                if (!/^9704\d{12}(\d{3})?$/.test(num)) return error(form, 'Số thẻ ATM nội địa phải bắt đầu bằng 9704 và gồm 16 hoặc 19 chữ số.');
                if (yy > curYY || (yy === curYY && mm > curMM)) return error(form, 'Ngày phát hành không được ở tương lai.');
                pendingResult = 'success';
            } else {
                const brand = brandOf(num);
                const cvv = field('cvv').value;
                if (!brand) return error(form, 'Chỉ chấp nhận thẻ Visa, Mastercard, JCB hoặc American Express.');
                if (num.length < (brand === 'amex' ? 15 : 16) || !luhn(num)) return error(form, 'Số thẻ không hợp lệ. Vui lòng kiểm tra lại.');
                if (yy < curYY || (yy === curYY && mm < curMM)) return error(form, 'Thẻ đã hết hạn.');
                if (!/^\d{3,4}$/.test(cvv) || (brand === 'amex' ? cvv.length !== 4 : cvv.length !== 3)) return error(form, 'Mã CVV/CVC không hợp lệ.');
                pendingResult = num === '4000000000000002' ? 'failure' : 'success';
            }
            error(form, '');
            // Clear sensitive values as soon as they are no longer needed.
            if (field('cvv')) field('cvv').value = '';
            if (pendingResult === 'failure') { show('processing'); setTimeout(() => finish('failure'), 300); return; }
            show('otp');
        });
    });

    const otpForm = $('.sim-otp-form');
    if (otpForm) {
        const otp = $('[data-field="otp"]', otpForm);
        otp.addEventListener('input', () => { otp.value = digits(otp.value).slice(0, 6); });
        otpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (otp.value === '123456') return finish(pendingResult);
            otpTries++;
            otp.value = '';
            if (otpTries >= 3) { error(otpForm, 'Nhập sai OTP quá 3 lần. Giao dịch bị từ chối.'); setTimeout(() => finish('failure'), 900); return; }
            error(otpForm, `Mã OTP không đúng. Bạn còn ${3 - otpTries} lần thử.`);
        });
    }
})();

