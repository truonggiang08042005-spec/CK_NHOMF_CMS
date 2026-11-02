// Run: node tools/test-nem-profile.cjs. Isolated DOM and network doubles; no browser or server writes.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
let checks = 0;
function check(condition, message) { assert.ok(condition, message); checks++; }
class Element {
    constructor(text = '') {
        this.textContent = text;
        this.value = '';
        this.hidden = false;
        this.dataset = {};
        this.attributes = {};
        this.events = {};
        this.classes = new Set();
        this.classList = {
            add: key => this.classes.add(key),
            remove: key => this.classes.delete(key),
            toggle: (key, on) => on ? this.classes.add(key) : this.classes.delete(key),
            contains: key => this.classes.has(key)
        };
    }
    addEventListener(name, fn) { this.events[name] = fn; }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
    focus() { this.focused = true; }
    reportValidity() { return true; }
    async fire(name) { await this.events[name]?.call(this, {target: this, preventDefault() {}}); }
}
async function scenario(removePresentationFields) {
    const nodes = {};
    for (const id of ['nemPanelAccount', 'nemPanelOrders', 'nemPanelAddress', 'nemBcActive', 'nemAddressModal', 'nemAddressForm', 'nemModalInputName', 'nemModalInputPhone', 'nemModalInputCountry', 'nemModalInputAddress', 'nemAddressDetails', 'nemAddressEmpty', 'nemAddressStatus', 'nemAddressFormStatus', 'nemModalBtnSubmit', 'nemBtnDeleteAddr', 'nemFieldFullname', 'nemFieldPhone', 'nemFieldCountry', 'nemAddrValAddress', 'nemModalTitle', 'nemBtnEditAddr', 'nemBtnAddNewAddr', 'nemModalCloseBtn', 'nemModalBtnCancel', 'nemAddrDisplayName', 'nemAddrValPhone', 'nemGreetingName']) nodes[id] = new Element();
    nodes.nemBcActive.textContent = 'HỒ SƠ CỦA TÔI';
    nodes.nemFieldFullname.value = nodes.nemModalInputName.value = 'Nguyễn Ánh';
    nodes.nemFieldCountry.value = nodes.nemModalInputCountry.value = 'Vietnam';
    nodes.nemFieldPhone.value = nodes.nemModalInputPhone.value = '0123456789';
    nodes.nemAddrValAddress.textContent = nodes.nemModalInputAddress.value = 'Địa chỉ cũ';
    if (removePresentationFields) {
        for (const id of ['nemFieldFullname', 'nemFieldPhone', 'nemFieldCountry', 'nemAddrDisplayName', 'nemAddrValAddress', 'nemAddrValPhone', 'nemGreetingName', 'nemAddressDetails', 'nemAddressEmpty', 'nemAddressStatus']) delete nodes[id];
    }
    const tabs = ['account', 'orders', 'address'].map(tab => {
        const item = new Element(); item.dataset.target = tab;
        const link = new Element(); link.dataset.tab = tab;
        return {item, link};
    });
    const header = new Element();
    const selectors = {'.nem-account-nav-item': tabs.map(x => x.item), '.nem-account-nav-link': tabs.map(x => x.link), '.nem-user-name, .nem-user-dropdown-name': [header]};
    const requests = [];
    let fail = false;
    const data = {fullname: 'Trần Bình', phone: '0987654321', country: 'Vietnam', address: 'Địa chỉ mới'};
    const context = {
        document: {readyState: 'complete', getElementById: id => nodes[id] || null, querySelectorAll: query => selectors[query] || [], addEventListener() {}},
        window: {addEventListener() {}}, location: {hash: '#address'}, history: {replaceState() {}},
        URLSearchParams, nemProfile: {ajax_url: '/admin-ajax.php', nonce: 'test-nonce'}, confirm: () => true,
        fetch: async (url, options) => {
            requests.push(new URLSearchParams(options.body));
            return {ok: !fail, json: async () => fail ? {success: false, data: {message: 'Phiên làm việc đã hết hạn.'}} : {success: true, data: requests.at(-1).get('action') === 'nem_delete_profile_address' ? {message: 'Đã xóa thông tin giao hàng.'} : data}};
        }
    };
    vm.runInNewContext(fs.readFileSync('wp-content/themes/storefront/assets/js/nem-profile.js', 'utf8'), context);
    check(nodes.nemPanelAddress.classList.contains('is-active'), 'Deep links select the delivery tab.');
    await tabs[0].link.fire('click');
    check(nodes.nemPanelAccount.classList.contains('is-active') && !nodes.nemPanelAddress.classList.contains('is-active'), 'Tabs switch after layout rearrangement.');
    check(nodes.nemBcActive.textContent === 'HỒ SƠ CỦA TÔI', 'The edited account breadcrumb survives tab switching.');
    check(tabs[0].link.attributes['aria-current'] === 'true' && !tabs[2].link.attributes['aria-current'], 'Active navigation remains accessible.');
    await nodes.nemBtnEditAddr.fire('click');
    check(nodes.nemAddressModal.classList.contains('is-open') && nodes.nemModalInputName.value === 'Nguyễn Ánh', 'The modal opens even if presentation fields were removed.');
    nodes.nemModalInputPhone.value = '+84 abc12345678901';
    await nodes.nemModalInputPhone.fire('input');
    check(nodes.nemModalInputPhone.value === '8412345678', 'Phone input keeps digits only, up to ten characters.');
    for (const [key, id] of Object.entries({fullname: 'nemModalInputName', phone: 'nemModalInputPhone', country: 'nemModalInputCountry', address: 'nemModalInputAddress'})) nodes[id].value = data[key];
    await nodes.nemAddressForm.fire('submit');
    check(requests[0].get('action') === 'nem_save_profile_address' && requests[0].get('nonce') === 'test-nonce', 'Saving uses the existing authenticated endpoint and nonce.');
    check(requests[0].get('phone') === '0987654321' && requests[0].get('address') === 'Địa chỉ mới', 'Saving submits the entered values.');
    check(!nodes.nemAddressModal.classList.contains('is-open') && header.textContent === 'Trần Bình', 'Successful saving updates the customer name and closes the dialog.');
    await nodes.nemBtnEditAddr.fire('click');
    fail = true;
    await nodes.nemAddressForm.fire('submit');
    check(nodes.nemAddressModal.classList.contains('is-open') && nodes.nemAddressFormStatus.textContent.includes('hết hạn'), 'Failed requests stay open and show the server error.');
    fail = false;
    await nodes.nemModalBtnCancel.fire('click');
    await nodes.nemBtnDeleteAddr.fire('click');
    check(requests.at(-1).get('action') === 'nem_delete_profile_address', 'Deleting retains the authenticated endpoint.');
    if (!removePresentationFields) check(nodes.nemAddressDetails.hidden && !nodes.nemAddressEmpty.hidden && nodes.nemFieldPhone.value === '', 'Deleting updates the empty state and clears the displayed phone.');
}
(async () => {
    await scenario(false);
    await scenario(true);
    console.log(`PASS: ${checks} profile interaction checks.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
