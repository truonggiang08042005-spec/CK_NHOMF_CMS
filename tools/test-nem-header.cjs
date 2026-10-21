// Run: node tools/test-nem-header.cjs
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const documentEvents = {};
let focused = null;
class Element {
    constructor() {
        this.handlers = {};
        this.attributes = {};
        this.classes = new Set();
        this.classList = {
            add: key => this.classes.add(key),
            contains: key => this.classes.has(key),
            toggle: (key, value) => value ? this.classes.add(key) : this.classes.delete(key)
        };
    }
    addEventListener(name, callback) { this.handlers[name] = callback; }
    setAttribute(name, value) { this.attributes[name] = value; }
    getAttribute(name) { return this.attributes[name]; }
    focus() { focused = this; }
    fire(name, extra = {}) { this.handlers[name]?.({target: this, preventDefault() {}, ...extra}); }
}
function makeItem(path, id) {
    const item = new Element();
    item.link = new Element();
    item.link.href = 'http://wordpress.local/CK_NHOMF_CMS/' + path;
    item.toggle = new Element();
    item.toggle.setAttribute('aria-controls', id);
    item.toggle.setAttribute('aria-expanded', 'false');
    item.first = new Element();
    item.contains = node => [item, item.link, item.toggle, item.first].includes(node);
    item.querySelector = selector => selector === '.nem-nav-link' ? item.link : item.toggle;
    item.toggle.closest = () => item;
    return item;
}
const items = [makeItem('shop/', 'products'), makeItem('bo-suu-tap/', 'collections')];
const nav = new Element();
nav.querySelectorAll = () => items;
nav.contains = node => items.some(item => item.contains(node));
const document = {
    body: {},
    get activeElement() { return focused; },
    addEventListener: (name, callback) => { (documentEvents[name] ??= []).push(callback); },
    querySelector: selector => selector === '.nem-desktop-nav' ? nav : null,
    querySelectorAll: () => [],
    getElementById: id => {
        const item = items.find(item => item.toggle.getAttribute('aria-controls') === id);
        return item ? {querySelector: () => item.first} : null;
    }
};
vm.runInNewContext(fs.readFileSync('wp-content/themes/storefront/assets/js/nem-header-footer.js', 'utf8'), {
    document, URL, location: {href: 'http://wordpress.local/CK_NHOMF_CMS/bo-suu-tap/'}, window: {addEventListener() {}}
});
documentEvents.DOMContentLoaded[0]();
const [products, collections] = items;
assert(collections.classes.has('is-active'));
assert(!products.classes.has('is-active'));
products.fire('pointerenter', {pointerType: 'mouse'});
assert(products.classes.has('is-open'));
collections.fire('pointerenter', {pointerType: 'mouse'});
assert(!products.classes.has('is-open'));
assert(collections.classes.has('is-open'));
collections.fire('pointerleave', {pointerType: 'mouse'});
assert(!collections.classes.has('is-open'));
// Touch pointerdown happens before focusin; the click must still open the menu.
collections.toggle.fire('pointerdown');
collections.fire('focusin');
collections.toggle.fire('click');
assert(collections.classes.has('is-open'));
assert.equal(collections.toggle.getAttribute('aria-expanded'), 'true');
collections.toggle.fire('keydown', {key: 'ArrowDown'});
assert.equal(focused, collections.first);
nav.fire('keydown', {key: 'Escape', target: collections.toggle});
assert(!collections.classes.has('is-open'));
assert.equal(focused, collections.link);
collections.fire('focusin');
collections.fire('focusout', {relatedTarget: {}});
assert(!collections.classes.has('is-open'));
collections.fire('focusin');
documentEvents.click.forEach(callback => callback({target: {}}));
assert(!collections.classes.has('is-open'));
console.log('PASS: active navigation, mouse hover, touch click, keyboard, outside click and Escape.');
