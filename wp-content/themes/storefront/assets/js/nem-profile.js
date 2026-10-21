(function () {
    'use strict';

    function init() {
        var panels = { account: 'nemPanelAccount', orders: 'nemPanelOrders', address: 'nemPanelAddress' };
        function switchTab(key) {
            if (!panels[key]) return;
            document.querySelectorAll('.nem-account-nav-item').forEach(function (item) {
                item.classList.toggle('is-active', item.dataset.target === key);
            });
            Object.keys(panels).forEach(function (name) {
                var panel = document.getElementById(panels[name]);
                if (panel) panel.classList.toggle('is-active', name === key);
            });
            var breadcrumb = document.getElementById('nemBcActive');
            if (breadcrumb) breadcrumb.textContent = key === 'address' ? 'ĐỊA CHỈ' : 'TÀI KHOẢN';
        }
        document.querySelectorAll('.nem-account-nav-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!document.getElementById(panels[link.dataset.tab])) return;
                event.preventDefault();
                switchTab(link.dataset.tab);
                history.replaceState(null, '', '#' + link.dataset.tab);
            });
        });
        switchTab(location.hash.slice(1));
        window.addEventListener('hashchange', function () { switchTab(location.hash.slice(1)); });

        var modal = document.getElementById('nemAddressModal');
        var form = document.getElementById('nemAddressForm');
        if (!modal || !form) return;
        var fields = {
            fullname: document.getElementById('nemModalInputName'),
            phone: document.getElementById('nemModalInputPhone'),
            country: document.getElementById('nemModalInputCountry'),
            address: document.getElementById('nemModalInputAddress')
        };
        var details = document.getElementById('nemAddressDetails');
        var empty = document.getElementById('nemAddressEmpty');
        var status = document.getElementById('nemAddressStatus');
        var formStatus = document.getElementById('nemAddressFormStatus');
        var submit = document.getElementById('nemModalBtnSubmit');
        var remove = document.getElementById('nemBtnDeleteAddr');
        var busy = false;
        var opener;

        // Apply to typing, pasting, mobile input, and autofill.
        fields.phone.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
        function close() {
            if (busy) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (opener) opener.focus();
        }
        function open(mode, button) {
            opener = button;
            formStatus.textContent = '';
            status.textContent = '';
            fields.fullname.value = document.getElementById('nemFieldFullname').value;
            fields.phone.value = document.getElementById('nemFieldPhone').value;
            fields.country.value = document.getElementById('nemFieldCountry').value;
            fields.address.value = mode === 'edit' ? document.getElementById('nemAddrValAddress').textContent.trim() : '';
            document.getElementById('nemModalTitle').textContent = mode === 'edit' ? 'CHỈNH SỬA ĐỊA CHỈ' : 'THÊM ĐỊA CHỈ MỚI';
            submit.textContent = mode === 'edit' ? 'LƯU THAY ĐỔI' : 'THÊM ĐỊA CHỈ';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            fields.fullname.focus();
        }
        document.getElementById('nemBtnEditAddr').addEventListener('click', function (event) {
            event.preventDefault();
            if (!busy) open('edit', this);
        });
        document.getElementById('nemBtnAddNewAddr').addEventListener('click', function () {
            if (!busy) open('new', this);
        });
        document.getElementById('nemModalCloseBtn').addEventListener('click', close);
        document.getElementById('nemModalBtnCancel').addEventListener('click', close);
        modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });

        async function request(action, data) {
            var payload = new URLSearchParams(data);
            payload.set('action', action);
            payload.set('nonce', nemProfile.nonce);
            var response = await fetch(nemProfile.ajax_url, { method: 'POST', credentials: 'same-origin', body: payload });
            var result;
            try { result = await response.json(); } catch (error) {
                throw new Error('Không thể xử lý phản hồi. Vui lòng tải lại trang và thử lại.');
            }
            if (!response.ok || !result.success) {
                throw new Error(result.data && result.data.message || 'Không thể lưu thay đổi. Vui lòng thử lại.');
            }
            return result.data;
        }
        function setBusy(value) {
            busy = value;
            submit.disabled = value;
            remove.setAttribute('aria-disabled', String(value));
            form.setAttribute('aria-busy', String(value));
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (busy || !form.reportValidity()) return;
            var data = {};
            Object.keys(fields).forEach(function (key) { data[key] = fields[key].value; });
            formStatus.textContent = '';
            setBusy(true);
            try {
                var saved = await request('nem_save_profile_address', data);
                document.getElementById('nemAddrDisplayName').textContent = saved.fullname.toUpperCase();
                document.getElementById('nemAddrValAddress').textContent = saved.address;
                document.getElementById('nemAddrValPhone').textContent = saved.phone;
                document.getElementById('nemGreetingName').textContent = saved.fullname.toUpperCase();
                document.getElementById('nemFieldFullname').value = saved.fullname;
                document.getElementById('nemFieldPhone').value = saved.phone;
                document.getElementById('nemFieldCountry').value = saved.country;
                document.querySelectorAll('.nem-user-name, .nem-user-dropdown-name').forEach(function (name) {
                    name.textContent = saved.fullname;
                    name.title = saved.fullname;
                });
                details.hidden = false;
                empty.hidden = true;
                status.textContent = 'Đã lưu thông tin giao hàng.';
                setBusy(false);
                close();
            } catch (error) {
                formStatus.textContent = error.message || 'Không thể kết nối. Vui lòng thử lại.';
            } finally {
                setBusy(false);
            }
        });
        remove.addEventListener('click', async function (event) {
            event.preventDefault();
            if (busy || !confirm('Bạn có chắc chắn muốn xóa thông tin giao hàng này không?')) return;
            status.textContent = '';
            setBusy(true);
            try {
                var result = await request('nem_delete_profile_address', {});
                details.hidden = true;
                empty.hidden = false;
                document.getElementById('nemAddrValAddress').textContent = '';
                document.getElementById('nemAddrValPhone').textContent = '';
                document.getElementById('nemFieldPhone').value = '';
                document.getElementById('nemFieldCountry').value = 'Vietnam';
                fields.phone.value = '';
                fields.address.value = '';
                status.textContent = result.message;
            } catch (error) {
                status.textContent = error.message || 'Không thể kết nối. Vui lòng thử lại.';
            } finally {
                setBusy(false);
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
