(function ($) {
  'use strict';
  $(function () {
    const form = document.querySelector('.nem-checkout-form');
    if (!form) return;
    const alertBox = document.querySelector('.nem-checkout-alert');
    const showError = (text) => {
      if (!alertBox) return;
      alertBox.textContent = text;
      alertBox.hidden = false;
      alertBox.scrollIntoView({behavior: 'smooth', block: 'center'});
    };
    const updateCheckout = () => $(document.body).trigger('update_checkout');
    const syncPayments = () => {
      form.querySelectorAll('.nem-payment-item').forEach((row) => {
        row.classList.toggle('is-selected', !!row.querySelector('input[name="payment_method"]:checked'));
      });
    };
    const restoreSubmit = () => {
      const submit = form.querySelector('#place_order');
      if (submit) { submit.disabled = false; submit.textContent = submit.dataset.value || 'Đặt hàng'; }
      syncPayments();
    };
    $(document.body).on('updated_checkout checkout_error', restoreSubmit);
    form.addEventListener('change', (event) => {
      if (event.target.matches('[name="payment_method"]')) syncPayments();
      if (event.target.matches('#billing_city,#billing_address_1,#billing_address_2')) updateCheckout();
    });
    $(form).on('checkout_place_order', function () {
      const invalid = ['billing_first_name','billing_phone','billing_address_1','billing_city'].find((name) => !form.elements[name]?.value.trim());
      if (invalid) {
        showError('Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ giao hàng.');
        form.elements[invalid]?.focus();
        return false;
      }
      if (form.querySelector('.nem-payment-options') && !form.querySelector('[name="payment_method"]:checked:not(:disabled)')) {
        showError('Vui lòng chọn phương thức thanh toán khả dụng.');
        return false;
      }
      if (form.elements.terms && !form.elements.terms.checked) {
        showError('Vui lòng đọc và đồng ý với điều khoản mua hàng.');
        form.elements.terms.focus();
        return false;
      }
      alertBox.hidden = true;
      const submit = form.querySelector('#place_order');
      if (submit) { submit.disabled = true; submit.textContent = 'Đang xử lý đặt hàng…'; }
      return true;
    });
    const request = async (action, data) => {
      const body = new FormData();
      body.set('action', action);
      body.set('nonce', form.dataset.nonce);
      Object.entries(data).forEach(([key, value]) => body.set(key, String(value)));
      const response = await fetch(form.dataset.ajaxUrl, {method: 'POST', credentials: 'same-origin', body});
      const json = await response.json();
      if (!response.ok || !json.success) throw new Error(json.data?.message || 'Chưa thể cập nhật. Vui lòng thử lại.');
      return json.data;
    };
    form.addEventListener('click', async (event) => {
      const button = event.target.closest('.nem-stepper-btn,.nem-cart-delete-btn,.nem-coupon-btn');
      if (!button || button.disabled) return;
      event.preventDefault();
      button.disabled = true;
      try {
        if (button.matches('.nem-coupon-btn')) {
          const code = form.querySelector('.nem-coupon-input')?.value.trim();
          if (!code) throw new Error('Vui lòng nhập mã khuyến mãi.');
          const result = await request('npe_checkout_apply_coupon', {coupon_code: code});
          const notice = form.querySelector('.nem-coupon-notice');
          if (notice) { notice.textContent = result.message; notice.hidden = false; }
        } else {
          const item = button.closest('.nem-cart-item');
          const input = item.querySelector('.nem-stepper-input');
          if (item.dataset.busy) return;
          item.dataset.busy = '1';
          try {
            if (button.matches('.nem-cart-delete-btn')) {
              const result = await request('npe_checkout_remove_item', {cart_item_key: item.dataset.key});
              item.remove();
              if (!result.cart_count) { window.location.reload(); return; }
            } else {
              const quantity = Math.max(1, Number(input.value) + (button.matches('.nem-stepper-plus') ? 1 : -1));
              const result = await request('npe_checkout_update_qty', {cart_item_key: item.dataset.key, quantity});
              input.value = quantity;
              if (result.item_subtotal) item.querySelector('.nem-cart-price').innerHTML = result.item_subtotal;
            }
          } finally { delete item.dataset.busy; }
        }
        updateCheckout();
      } catch (error) { showError(error.message || 'Kết nối bị gián đoạn. Vui lòng thử lại.'); }
      finally { button.disabled = false; }
    });
    syncPayments();
  });
})(jQuery);
