(function () {
    'use strict';

    function init() {
        var panels = { account: 'nemPanelAccount', orders: 'nemPanelOrders', address: 'nemPanelAddress' };
        var breadcrumb = document.getElementById('nemBcActive');
        var accountCaption = breadcrumb ? breadcrumb.textContent : 'TÀI KHOẢN';
        function switchTab(key) {
            if (!panels[key]) return;
            document.querySelectorAll('.nem-account-nav-item').forEach(function (item) {
                item.classList.toggle('is-active', item.dataset.target === key);
            });
            Object.keys(panels).forEach(function (name) {
                var panel = document.getElementById(panels[name]);
                if (panel) panel.classList.toggle('is-active', name === key);
            });
            document.querySelectorAll('.nem-account-nav-link').forEach(function (link) {
                if (link.dataset.tab === key) link.setAttribute('aria-current', 'true');
                else link.removeAttribute('aria-current');
            });
            if (breadcrumb) breadcrumb.textContent = key === 'address' ? 'ĐỊA CHỈ' : accountCaption;
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
        function read(id, fallback) {
            var node = document.getElementById(id);
            return node ? ('value' in node ? node.value : node.textContent.trim()) : fallback;
        }
        function write(id, value, input) {
            var node = document.getElementById(id);
            if (node) node[input ? 'value' : 'textContent'] = value;
        }

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
            if (status) status.textContent = '';
            fields.fullname.value = read('nemFieldFullname', fields.fullname.value);
            fields.phone.value = read('nemFieldPhone', fields.phone.value);
            fields.country.value = read('nemFieldCountry', fields.country.value);
            fields.address.value = mode === 'edit' ? read('nemAddrValAddress', fields.address.value) : '';
            document.getElementById('nemModalTitle').textContent = mode === 'edit' ? 'CHỈNH SỬA ĐỊA CHỈ' : 'THÊM ĐỊA CHỈ MỚI';
            submit.textContent = mode === 'edit' ? 'LƯU THAY ĐỔI' : 'THÊM ĐỊA CHỈ';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            fields.fullname.focus();
        }
        document.getElementById('nemBtnEditAddr')?.addEventListener('click', function (event) {
            event.preventDefault();
            if (!busy) open('edit', this);
        });
        document.getElementById('nemBtnAddNewAddr')?.addEventListener('click', function () {
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
            if (remove) remove.setAttribute('aria-disabled', String(value));
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
                write('nemAddrDisplayName', saved.fullname.toUpperCase());
                write('nemAddrValAddress', saved.address);
                write('nemAddrValPhone', saved.phone);
                write('nemGreetingName', saved.fullname.toUpperCase());
                write('nemFieldFullname', saved.fullname, true);
                write('nemFieldPhone', saved.phone, true);
                write('nemFieldCountry', saved.country, true);
                document.querySelectorAll('.nem-user-name, .nem-user-dropdown-name').forEach(function (name) {
                    name.textContent = saved.fullname;
                    name.title = saved.fullname;
                });
                if (details) details.hidden = false;
                if (empty) empty.hidden = true;
                if (status) status.textContent = 'Đã lưu thông tin giao hàng.';
                setBusy(false);
                close();
            } catch (error) {
                formStatus.textContent = error.message || 'Không thể kết nối. Vui lòng thử lại.';
            } finally {
                setBusy(false);
            }
        });
        remove?.addEventListener('click', async function (event) {
            event.preventDefault();
            if (busy || !confirm('Bạn có chắc chắn muốn xóa thông tin giao hàng này không?')) return;
            if (status) status.textContent = '';
            setBusy(true);
            try {
                var result = await request('nem_delete_profile_address', {});
                if (details) details.hidden = true;
                if (empty) empty.hidden = false;
                write('nemAddrValAddress', '');
                write('nemAddrValPhone', '');
                write('nemFieldPhone', '', true);
                write('nemFieldCountry', 'Vietnam', true);
                fields.phone.value = '';
                fields.address.value = '';
                if (status) status.textContent = result.message;
            } catch (error) {
                if (status) status.textContent = error.message || 'Không thể kết nối. Vui lòng thử lại.';
            } finally {
                setBusy(false);
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
