(() => {
  'use strict';

  const ready = () => {
    const form = document.querySelector('form.nem-checkout-form');
    if (!form) return;

    const ajaxUrl = form.dataset.ajaxUrl || '/wp-admin/admin-ajax.php';
    const nonce = form.dataset.nonce || '';

    // Payment method selection
    const paymentItems = document.querySelectorAll('.nem-payment-item');
    const syncPaymentSelection = () => paymentItems.forEach(item => {
      item.classList.toggle('is-selected', !!item.querySelector('.nem-payment-radio')?.checked);
    });
    paymentItems.forEach(item => {
      item.querySelector('.nem-payment-radio')?.addEventListener('change', syncPaymentSelection);
    });
    syncPaymentSelection();

    // Helper: Format VND
    const formatVND = num => {
      return new Intl.NumberFormat('vi-VN').format(num) + '₫';
    };

    // Helper: Update summary display
    const updateSummary = data => {
      if (!data) return;
      const subtotalEl = document.querySelector('.nem-summary-subtotal');
      const totalEl = document.querySelector('.nem-summary-total');
      const discountRow = document.querySelector('.nem-summary-discount-row');
      const discountVal = document.querySelector('.nem-summary-discount');

      if (subtotalEl && data.subtotal_html) subtotalEl.innerHTML = data.subtotal_html;
      if (totalEl && data.total_html) totalEl.innerHTML = data.total_html;

      if (data.discount_html && discountRow && discountVal) {
        discountRow.hidden = false;
        discountVal.innerHTML = data.discount_html;
      }
    };

    // Quantity steppers & Remove items
    const bindCartEvents = () => {
      document.querySelectorAll('.nem-cart-item').forEach(item => {
        const key = item.dataset.key;
        const input = item.querySelector('.nem-stepper-input');
        const btnMinus = item.querySelector('.nem-stepper-minus');
        const btnPlus = item.querySelector('.nem-stepper-plus');
        const btnDelete = item.querySelector('.nem-cart-delete-btn');

        const updateQty = async newQty => {
          if (newQty < 1) return;
          if (input) input.value = newQty;
          item.style.opacity = '0.6';

          try {
            const formData = new FormData();
            formData.append('action', 'npe_checkout_update_qty');
            formData.append('nonce', nonce);
            formData.append('cart_item_key', key);
            formData.append('quantity', newQty);

            const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
            const json = await res.json();
            if (json.success && json.data) {
              const priceEl = item.querySelector('.nem-cart-price');
              if (priceEl && json.data.item_subtotal) priceEl.innerHTML = json.data.item_subtotal;
              updateSummary(json.data);
            }
          } catch (e) {
            console.error('Update qty error:', e);
          } finally {
            item.style.opacity = '1';
          }
        };

        btnMinus?.addEventListener('click', e => {
          e.preventDefault();
          const curr = parseInt(input?.value || '1', 10);
          if (curr > 1) updateQty(curr - 1);
        });

        btnPlus?.addEventListener('click', e => {
          e.preventDefault();
          const curr = parseInt(input?.value || '1', 10);
          updateQty(curr + 1);
        });

        btnDelete?.addEventListener('click', async e => {
          e.preventDefault();
          if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
          item.style.opacity = '0.4';

          try {
            const formData = new FormData();
            formData.append('action', 'npe_checkout_remove_item');
            formData.append('nonce', nonce);
            formData.append('cart_item_key', key);

            const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
            const json = await res.json();
            if (json.success && json.data) {
              item.remove();
              updateSummary(json.data);
              if (json.data.cart_count === 0) {
                const list = document.querySelector('.nem-cart-items');
                if (list) list.innerHTML = '<p style="color:#888;padding:12px 0;">Giỏ hàng của bạn đang trống.</p>';
              }
            }
          } catch (err) {
            console.error('Remove item error:', err);
            item.style.opacity = '1';
          }
        });
      });
    };

    bindCartEvents();

    // Coupon Apply
    const couponBtn = document.querySelector('.nem-coupon-btn');
    const couponInput = document.querySelector('.nem-coupon-input');
    const couponNotice = document.querySelector('.nem-coupon-notice');

    couponBtn?.addEventListener('click', async e => {
      e.preventDefault();
      const code = couponInput?.value.trim();
      if (!code) return;

      couponBtn.disabled = true;
      couponBtn.textContent = 'Đang xử lý...';
      if (couponNotice) couponNotice.hidden = true;

      try {
        const formData = new FormData();
        formData.append('action', 'npe_checkout_apply_coupon');
        formData.append('nonce', nonce);
        formData.append('coupon_code', code);

        const res = await fetch(ajaxUrl, { method: 'POST', body: formData });
        const json = await res.json();
        if (couponNotice) {
          couponNotice.hidden = false;
          couponNotice.style.color = json.success ? '#2e7d32' : '#c62828';
          couponNotice.textContent = json.data?.message || (json.success ? 'Áp dụng mã thành công!' : 'Mã không hợp lệ');
        }
        if (json.success && json.data) {
          updateSummary(json.data);
        }
      } catch (err) {
        console.error('Apply coupon error:', err);
      } finally {
        couponBtn.disabled = false;
        couponBtn.textContent = 'Áp dụng';
      }
    });

    // Quick Coupon Suggestion Modal / Toggle
    const couponSelectRow = document.querySelector('.nem-coupon-row-top');
    const couponSuggestions = document.querySelector('.nem-coupon-suggestions');
    couponSelectRow?.addEventListener('click', () => {
      if (couponSuggestions) {
        couponSuggestions.hidden = !couponSuggestions.hidden;
      }
    });

    document.querySelectorAll('.nem-quick-coupon-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        const c = tag.dataset.code;
        if (couponInput && c) {
          couponInput.value = c;
          couponBtn?.click();
        }
      });
    });

    // Vietnam Province Selector
    const addressInput = document.querySelector('#nem_billing_address_group');
    const addressDropdown = document.querySelector('.nem-address-dropdown');
    const hiddenCity = document.querySelector('input[name="billing_city"]');
    const hiddenState = document.querySelector('input[name="billing_state"]');

    const provinces = [
      'Hà Nội', 'TP Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
      'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
      'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
      'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông',
      'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang',
      'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình',
      'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu',
      'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định',
      'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên',
      'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị',
      'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên',
      'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang',
      'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
    ];

    if (addressInput && addressDropdown) {
      const renderOptions = filter => {
        const filtered = provinces.filter(p => !filter || p.toLowerCase().includes(filter.toLowerCase()));
        addressDropdown.innerHTML = '';
        if (!filtered.length) {
          addressDropdown.innerHTML = '<div class="nem-address-option" style="color:#999;">Không tìm thấy kết quả</div>';
          return;
        }
        filtered.forEach(p => {
          const opt = document.createElement('div');
          opt.className = 'nem-address-option';
          opt.textContent = p;
          opt.addEventListener('click', () => {
            addressInput.value = p;
            if (hiddenCity) hiddenCity.value = p;
            if (hiddenState) hiddenState.value = p;
            addressDropdown.hidden = true;
          });
          addressDropdown.append(opt);
        });
      };

      renderOptions();

      addressInput.addEventListener('focus', () => {
        addressDropdown.hidden = false;
        renderOptions(addressInput.value);
      });

      addressInput.addEventListener('input', () => {
        addressDropdown.hidden = false;
        renderOptions(addressInput.value);
      });

      document.addEventListener('click', e => {
        if (!addressInput.contains(e.target) && !addressDropdown.contains(e.target)) {
          addressDropdown.hidden = true;
        }
      });
    }

    // Checkout Form Submission
    const submitBtn = document.querySelector('.nem-checkout-submit-btn');
    const alertBox = document.querySelector('.nem-checkout-alert');

    form.addEventListener('submit', async e => {
      // Basic validation
      const name = form.querySelector('[name="billing_first_name"]')?.value.trim();
      const phone = form.querySelector('[name="billing_phone"]')?.value.trim();
      const address = form.querySelector('[name="billing_address_1"]')?.value.trim();

      if (!name || !phone || !address) {
        e.preventDefault();
        if (alertBox) {
          alertBox.hidden = false;
          alertBox.textContent = 'Vui lòng nhập đầy đủ Họ và tên, Số điện thoại và Địa chỉ giao hàng.';
          alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang xử lý đặt hàng...';
      }
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
  } else {
    ready();
  }
})();
