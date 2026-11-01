(function ($) {
    'use strict';
    const cfg = window.npeVisual;
    if (!cfg) return;
    const frame = document.getElementById('nve-preview');
    const status = document.getElementById('nve-status');
    const data = { fields: { ...cfg.fields }, order: [...cfg.order], hidden: [...cfg.hidden], columns: { ...cfg.columns }, featureOrder: [...(cfg.featureOrder || [])], styles: { ...JSON.parse(JSON.stringify(cfg.styles)) }, product: cfg.product, productFields: {} };
    let doc, connectedDoc, selected = '', dirty = false, saving = false, dragKey = '';
    const required = cfg.type === 'checkout' ? ['contact', 'address', 'payment', 'summary'] : [];
    const styleProps = { fontSize: 'font-size', fontWeight: 'font-weight', color: 'color', textAlign: 'text-align', lineHeight: 'line-height', letterSpacing: 'letter-spacing' };
    const controls = { fontSize: 'nve-font-size', fontWeight: 'nve-font-weight', color: 'nve-color', textAlign: 'nve-align', lineHeight: 'nve-line-height' };
    function message(text, error = false) { status.textContent = text; status.classList.toggle('is-error', error); }
    function changed() { dirty = true; message('Có thay đổi chưa lưu.'); }
    function nodes(selector) { return doc ? Array.from(doc.querySelectorAll(selector)) : []; }
    function styleSheet() {
        if (!doc) return;
        let sheet = doc.getElementById('nve-live-styles');
        if (!sheet) { sheet = doc.createElement('style'); sheet.id = 'nve-live-styles'; doc.head.appendChild(sheet); }
        sheet.textContent = Object.entries(data.styles).map(([key, values]) => {
            const target = cfg.targets[key];
            if (!target) return '';
            return `${target.selector}{${Object.entries(values).map(([prop, value]) => `${styleProps[prop]}:${value}${['fontSize', 'letterSpacing'].includes(prop) ? 'px' : ''}!important`).join(';')}}`;
        }).join('\n');
    }
    function select(key, node) {
        selected = key;
        nodes('.nve-selected').forEach(el => el.classList.remove('nve-selected'));
        if (node) node.classList.add('nve-selected');
        const target = cfg.targets[key];
        document.getElementById('nve-selection').textContent = cfg.schema[target.field]?.label || ({ product_name: 'Tên sản phẩm', product_description: 'Mô tả sản phẩm', product_price: 'Giá sản phẩm', catalog_names: 'Tên trong danh sách sản phẩm', catalog_prices: 'Giá trong danh sách sản phẩm', form_text: 'Chữ trong biểu mẫu', footer_text: 'Chữ chân trang' })[key] || 'Kiểu chữ đang chọn';
        const values = data.styles[key] || {};
        Object.entries(controls).forEach(([prop, id]) => { document.getElementById(id).value = values[prop] ?? (prop === 'color' ? '#222222' : ''); });
    }
    function capture(node, target) {
        if (target.field) {
            data.fields[target.field] = node.innerText.replace(/\u00a0/g, ' ');
            const field = document.querySelector(`[data-nve-field="${target.field}"]`);
            if (field) field.value = data.fields[target.field];
        } else if (target.productField) {
            data.productFields[target.productField] = target.productField === 'description' ? node.innerHTML : node.textContent;
        }
        changed();
    }
    function applyOrder() {
        if (!doc) return;
        const root = doc.querySelector(cfg.type === 'checkout' ? '.nem-checkout-grid' : '#npe-main');
        if (!root) return;
        data.order.forEach(key => {
            const node = root.querySelector(`[data-npe-section="${key}"]`);
            if (!node) return;
            const isHidden = data.hidden.includes(key) && !required.includes(key);
            node.classList.toggle('nve-hidden-section', isHidden);
            if (cfg.type === 'checkout') {
                const column = data.columns[key] || (['summary', 'coupon'].includes(key) ? 'sidebar' : 'main');
                root.querySelector(`.nem-checkout-${column}`)?.appendChild(node);
            } else root.appendChild(node);
        });
    }
    function move(key, delta) {
        const index = data.order.indexOf(key), next = index + delta;
        if (next < 0 || next >= data.order.length) return;
        [data.order[index], data.order[next]] = [data.order[next], data.order[index]];
        renderSections(); applyOrder(); changed();
    }
    function renderSections() {
        const list = document.getElementById('nve-sections');
        list.replaceChildren();
        data.order.forEach(key => {
            const li = document.createElement('li'); li.dataset.key = key;
            const grip = document.createElement('span'); grip.className = 'nve-grip'; grip.textContent = '⠿'; grip.title = 'Kéo để đổi vị trí';
            const label = document.createElement('label');
            const box = document.createElement('input'); box.type = 'checkbox'; box.checked = !data.hidden.includes(key) || required.includes(key); box.disabled = required.includes(key);
            box.addEventListener('change', () => { data.hidden = data.hidden.filter(k => k !== key); if (!box.checked) data.hidden.push(key); applyOrder(); changed(); });
            label.append(box, document.createTextNode(cfg.sections[key] + (required.includes(key) ? ' · bắt buộc' : '')));
            li.append(grip, label);
            ['↑', '↓'].forEach((text, i) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = text; b.title = `${text === '↑' ? 'Đưa lên' : 'Đưa xuống'} ${cfg.sections[key]}`; b.addEventListener('click', () => move(key, i ? 1 : -1)); li.appendChild(b); });
            if (cfg.type === 'checkout') {
                const placement = document.createElement('span'); placement.className = 'nve-help';
                placement.textContent = ({ main: 'Cột trái', sidebar: 'Cột phải', full: 'Toàn chiều ngang' })[data.columns[key] || (['summary', 'coupon'].includes(key) ? 'sidebar' : 'main')];
                li.appendChild(placement);
            }
            list.appendChild(li);
        });
        if ($(list).hasClass('ui-sortable')) $(list).sortable('destroy');
        $(list).sortable({ handle: '.nve-grip', update() { data.order = Array.from(list.children).map(li => li.dataset.key); applyOrder(); changed(); } });
    }
    function applyField(key, value) {
        data.fields[key] = value;
        Object.values(cfg.targets).filter(t => t.field === key).forEach(t => nodes(t.selector).forEach(el => { el.textContent = value; }));
        if (key === 'gallery_position') nodes('.npe-detail').forEach(el => el.classList.toggle('npe-gallery-right', value === 'right'));
        if (key === 'hero_url') nodes('.npe-slide').forEach(el => el.href = value);
        if (key === 'collection_url') nodes('.npe-collection').forEach(el => el.href = value);
        if (key === 'support_url') nodes('.npe-consult').forEach(el => el.href = value);
        if (key === 'brand') nodes('.npe-brand').forEach(el => { const br = el.querySelector('br'); if (br) { while (el.firstChild && el.firstChild !== br) el.firstChild.remove(); el.prepend(doc.createTextNode('Thương hiệu: ' + value)); } });
        changed();
    }
    function updateField(key, value) {
        data.fields[key] = value;
        const input = document.querySelector(`[data-nve-field="${key}"]`);
        if (input) input.value = value;
    }
    function pieceHandle(element, label) {
        const handle = doc.createElement('span');
        handle.className = 'nve-piece-handle'; handle.textContent = '⠿ ' + label;
        handle.setAttribute('role', 'button'); handle.setAttribute('aria-label', 'Kéo ' + label + ' để đổi vị trí');
        handle.tabIndex = 0; element.prepend(handle); element.classList.add('nve-piece');
    }
    function sortablePieces(root, items, label, stop, extra = {}) {
        Array.from(root.children).filter(el => el.matches(items)).forEach(el => {
            pieceHandle(el, label(el));
            el.querySelector('.nve-piece-handle').addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault(); event.stopPropagation();
                const siblings = Array.from(root.children).filter(child => child.matches(items));
                const previous = ['ArrowLeft', 'ArrowUp'].includes(event.key);
                const next = siblings[siblings.indexOf(el) + (previous ? -1 : 1)];
                if (!next) return;
                if (previous) root.insertBefore(el, next); else next.after(el);
                stop(); changed(); event.currentTarget.focus();
            });
        });
        $(root).on('dragstart.nve-pieces', 'img', event => event.preventDefault());
        $(root).sortable({
            items: '> ' + items, handle: '.nve-piece-handle, img',
            cancel: 'input,textarea,select,option,[contenteditable="true"],button:not(.npe-main-photo)',
            helper: 'clone', appendTo: doc.body, tolerance: 'pointer', distance: 8,
            placeholder: 'nve-piece-placeholder', forcePlaceholderSize: true, scroll: true,
            start() { doc.body.classList.add('nve-dragging-pieces'); $(root).sortable('refreshPositions'); },
            stop() { doc.body.classList.remove('nve-dragging-pieces'); stop(); changed(); },
            ...extra
        });
    }
    function connectProductColumns() {
        const detail = doc.querySelector('.npe-detail');
        if (!detail) return;
        const gallery = detail.querySelector('.npe-gallery'), summary = detail.querySelector('.npe-summary');
        if (!gallery || !summary) return;
        detail.classList.add('nve-product-columns');
        // Use DOM order in the editor so the sortable geometry matches what is visible.
        if (data.fields.gallery_position === 'right') detail.insertBefore(summary, gallery);
        sortablePieces(detail, '.npe-gallery, .npe-summary', el => el === gallery ? 'Ảnh · kéo sang trái / phải' : 'Nội dung · kéo sang trái / phải', () => {
            const columns = Array.from(detail.children).filter(el => el === gallery || el === summary);
            const position = columns[0] === gallery ? 'left' : 'right';
            updateField('gallery_position', position);
            detail.classList.toggle('npe-gallery-right', position === 'right');
        }, { items: '> .npe-gallery, > .npe-summary', handle: '.nve-piece-handle, .npe-main-photo img' });
    }
    function connectHomeRows() {
        const features = doc.querySelector('.npe-features');
        if (features) sortablePieces(features, 'a[data-npe-feature]', () => 'Ô ảnh · kéo đổi vị trí', () => {
            data.featureOrder = Array.from(features.children).filter(el => el.hasAttribute('data-npe-feature')).map(el => Number(el.dataset.npeFeature));
        });
        const tracks = nodes('[data-npe-section^="products_"] .npe-home-track');
        const syncProducts = () => tracks.forEach(track => {
            const section = track.closest('[data-npe-section]').dataset.npeSection.replace('products_', '');
            const ids = Array.from(track.children).filter(el => el.matches('.npe-card[data-product-id]')).map(el => el.dataset.productId);
            updateField(`section_${section}_products`, ids.join(', '));
        });
        tracks.forEach(track => sortablePieces(track, '.npe-card', () => 'Sản phẩm · kéo đổi vị trí', syncProducts, { connectWith: tracks }));
        const blog = doc.querySelector('.npe-blog-track');
        if (blog) sortablePieces(blog, 'a[data-npe-post-id]', () => 'Bài viết · kéo đổi vị trí', () => {
            updateField('posts', Array.from(blog.children).filter(el => el.hasAttribute('data-npe-post-id')).map(el => el.dataset.npePostId).join(', '));
        });
    }
    function connectCheckoutColumns() {
        const columns = nodes('.nem-checkout-full, .nem-checkout-main, .nem-checkout-sidebar');
        columns.forEach(column => {
            const position = column.classList.contains('nem-checkout-full') ? 'full' : (column.classList.contains('nem-checkout-sidebar') ? 'sidebar' : 'main');
            column.dataset.nveColumn = position;
            const caption = doc.createElement('p'); caption.className = 'nve-column-caption';
            caption.textContent = ({ full: 'Thả khối vào đây để đặt toàn chiều ngang', main: 'Cột trái · kéo khối vào đây', sidebar: 'Cột phải · kéo khối vào đây' })[position];
            column.prepend(caption);
        });
        columns.forEach(column => $(column).sortable({
            connectWith: columns, items: '> [data-npe-section]', handle: '.nve-block-handle',
            cancel: 'input,textarea,select,option,[contenteditable="true"],button:not(.nve-block-handle)',
            helper: 'clone', appendTo: doc.body, tolerance: 'pointer', distance: 8,
            placeholder: 'nve-piece-placeholder', forcePlaceholderSize: true, dropOnEmpty: true,
            start() { doc.body.classList.add('nve-dragging-pieces'); $(this).sortable('refreshPositions'); },
            stop() {
                doc.body.classList.remove('nve-dragging-pieces');
                const order = [];
                columns.forEach(container => Array.from(container.children).forEach(el => {
                    const key = el.dataset.npeSection;
                    if (!cfg.sections[key]) return;
                    data.columns[key] = container.dataset.nveColumn; order.push(key);
                }));
                data.order = [...new Set([...order, ...data.order])];
                renderSections(); changed();
            }
        }));
        nodes('.nve-block-handle').forEach(handle => { handle.draggable = false; });
    }
    function imageNodes(key) {
        const map = { image_logo: '.nem-logo-img, .nem-drawer-logo, .npe-logo img', image_hero: '.npe-slide:nth-of-type(1) img', image_hero3: '.npe-slide:nth-of-type(2) img', image_hero4: '.npe-slide:nth-of-type(3) img', image_feature1: '.npe-features > a[data-npe-feature="0"] img', image_feature2: '.npe-features > a[data-npe-feature="1"] img', image_collection: '.npe-collection img', banner_image: '.npe-promo img', image_bct: '.npe-certification', image_payment1: '.npe-payments img:nth-child(1)', image_payment2: '.npe-payments img:nth-child(2)', image_payment: '.npe-payments img:nth-child(3)', image_payment4: '.npe-payments img:nth-child(4)' };
        return nodes(map[key] || '[data-no-image]');
    }
    function renderFields() {
        const container = document.getElementById('nve-fields');
        Object.entries(cfg.schema).forEach(([key, field]) => {
            if (cfg.type === 'product' && key === 'gallery_position') return;
            const wrap = document.createElement('div'); wrap.className = 'nve-field';
            const label = document.createElement('label'); label.textContent = field.label; label.htmlFor = 'nve-field-' + key; wrap.appendChild(label);
            let input;
            if (field.type === 'image') {
                const button = document.createElement('button'); button.className = 'button'; button.type = 'button'; button.textContent = 'Chọn / thay ảnh';
                const hint = document.createElement('span'); hint.className = 'nve-help'; hint.textContent = data.fields[key] ? 'Đã chọn ảnh #' + data.fields[key] : 'Chưa chọn ảnh';
                button.addEventListener('click', () => {
                    const media = wp.media({ title: field.label, library: { type: 'image' }, multiple: false });
                    media.on('select', () => { const image = media.state().get('selection').first().toJSON(); data.fields[key] = image.id; hint.textContent = 'Đã chọn: ' + image.filename; imageNodes(key).forEach(el => { el.src = image.url; el.removeAttribute('srcset'); el.removeAttribute('sizes'); }); changed(); }); media.open();
                });
                wrap.append(button, hint);
            } else {
                if (field.type === 'select') {
                    input = document.createElement('select'); Object.entries(field.choices).forEach(([value, text]) => input.add(new Option(text, value)));
                } else if (field.type === 'textarea') { input = document.createElement('textarea'); input.rows = 4; }
                else { input = document.createElement('input'); input.type = field.type === 'url' ? 'url' : 'text'; }
                input.id = 'nve-field-' + key; input.dataset.nveField = key; input.value = Array.isArray(data.fields[key]) ? data.fields[key].join(', ') : data.fields[key] ?? '';
                input.addEventListener('input', () => applyField(key, input.value)); wrap.appendChild(input);
                if (['posts', 'products'].includes(field.type)) { const help = document.createElement('p'); help.className = 'nve-help'; help.textContent = 'Nhập các ID phân cách bằng dấu phẩy. Danh sách được tải lại sau khi lưu.'; wrap.appendChild(help); }
            }
            container.appendChild(wrap);
        });
    }
    function connect() {
        try { doc = frame.contentDocument; if (!doc?.querySelector('body')) throw new Error('Không đọc được nội dung trang.'); }
        catch (error) { message('Không tải được xem trước. Kiểm tra đăng nhập WordPress và mở lại Edit Page.', true); return; }
        if (!doc.querySelector('#npe-main, .nem-checkout-grid')) { message('Phiên đăng nhập hết hạn hoặc trang chưa tải đúng. Đăng nhập lại rồi mở Edit Page.', true); return; }
        if (doc === connectedDoc) return;
        connectedDoc = doc;
        doc.getElementById('npe-visual-typography')?.remove();
        const style = doc.createElement('style');
        style.textContent = 'html{scroll-behavior:auto!important;margin-top:0!important}body{overflow-x:hidden}.nve-editable{outline:1px dashed transparent;cursor:text;white-space:pre-wrap}.nve-editable:hover{outline-color:#2383d1}.nve-selected{outline:2px solid #2383d1!important;outline-offset:5px}.nve-hidden-section{display:none!important}[data-npe-section]{position:relative}[data-npe-section].nve-drop-target{outline:3px dashed #2383d1}.nve-block-handle{position:absolute!important;top:6px!important;left:6px!important;z-index:50!important;background:#2271b1!important;color:white!important;padding:6px 10px!important;border:0!important;border-radius:4px!important;font:12px Arial!important;cursor:grab!important;opacity:.85}.npe-recent[hidden]{display:block!important}.npe-recent[hidden] .npe-product-track:empty::after{content:"Sản phẩm đã xem sẽ hiện tại đây khi khách xem sản phẩm.";display:block;padding:24px;color:#666}.npe-slide:not(.is-active){display:none!important}.nem-checkout-main,.nem-checkout-sidebar{min-height:80px}';
        doc.head.appendChild(style);
        const canvasStyle = doc.createElement('link'); canvasStyle.rel = 'stylesheet'; canvasStyle.href = cfg.canvasStyle;
        doc.head.appendChild(canvasStyle);
        doc.addEventListener('submit', event => { event.preventDefault(); event.stopImmediatePropagation(); }, true);
        doc.addEventListener('click', event => {
            // Selection and text editing are active; commerce/navigation are inert.
            if (event.target.closest('a') || !event.target.closest('[contenteditable="true"]')) event.preventDefault();
            event.stopPropagation();
        }, true);
        Object.entries(cfg.targets).forEach(([key, target]) => nodes(target.selector).forEach(el => {
            if (target.field || target.productField) {
                el.contentEditable = 'true'; el.classList.add('nve-editable'); el.spellcheck = false;
                el.addEventListener('input', () => capture(el, target));
                el.addEventListener('paste', event => { event.preventDefault(); const text = event.clipboardData.getData('text/plain'); const selection = doc.getSelection(); if (!selection?.rangeCount) return; const range = selection.getRangeAt(0); range.deleteContents(); const textNode = doc.createTextNode(text); range.insertNode(textNode); range.setStartAfter(textNode); range.collapse(true); selection.removeAllRanges(); selection.addRange(range); capture(el, target); });
            }
            el.addEventListener('pointerdown', event => { event.stopPropagation(); select(key, el); });
            el.addEventListener('focus', () => select(key, el));
        }));
        nodes('[data-npe-section]').forEach(el => {
            const key = el.dataset.npeSection;
            if (!cfg.sections[key]) return;
            const handle = doc.createElement('button'); handle.type = 'button'; handle.draggable = true; handle.className = 'nve-block-handle'; handle.textContent = '⠿ ' + cfg.sections[key]; handle.setAttribute('aria-label', 'Kéo khối ' + cfg.sections[key]);
            // Do not nest a button inside an anchor (collection banner).
            if (el.tagName === 'A') { const span = doc.createElement('span'); span.className = handle.className; span.draggable = true; span.textContent = handle.textContent; el.appendChild(span); span.addEventListener('dragstart', event => { dragKey = key; event.dataTransfer.setData('text/plain', key); }); }
            else { el.appendChild(handle); handle.addEventListener('dragstart', event => { dragKey = key; event.dataTransfer.setData('text/plain', key); }); }
            el.addEventListener('dragover', event => { if (!dragKey) return; event.preventDefault(); el.classList.add('nve-drop-target'); });
            el.addEventListener('dragleave', () => el.classList.remove('nve-drop-target'));
            el.addEventListener('drop', event => { event.preventDefault(); el.classList.remove('nve-drop-target'); if (!dragKey || dragKey === key) { dragKey = ''; return; } data.order = data.order.filter(k => k !== dragKey); data.order.splice(data.order.indexOf(key), 0, dragKey); if (cfg.type === 'checkout') data.columns[dragKey] = data.columns[key] || (['summary', 'coupon'].includes(key) ? 'sidebar' : 'main'); dragKey = ''; renderSections(); applyOrder(); changed(); });
            el.addEventListener('dragend', () => { dragKey = ''; nodes('.nve-drop-target').forEach(n => n.classList.remove('nve-drop-target')); });
        });
        applyOrder(); styleSheet();
        if (cfg.type === 'product') connectProductColumns();
        if (cfg.type === 'home') connectHomeRows();
        if (cfg.type === 'checkout') connectCheckoutColumns();
        message(dirty ? 'Có thay đổi chưa lưu.' : 'Sẵn sàng. Bấm vào chữ hoặc kéo khối để chỉnh sửa.');
    }
    Object.entries(controls).forEach(([prop, id]) => document.getElementById(id).addEventListener('input', event => {
        if (!selected) { message('Hãy chọn chữ trên bản xem trước trước.', true); return; }
        data.styles[selected] ||= {};
        if (event.target.value === '') delete data.styles[selected][prop];
        else data.styles[selected][prop] = ['fontSize', 'lineHeight'].includes(prop) ? Number(event.target.value) : event.target.value;
        styleSheet(); changed();
    }));
    document.getElementById('nve-reset-style').addEventListener('click', () => { if (!selected) return; delete data.styles[selected]; styleSheet(); select(selected, nodes(cfg.targets[selected].selector)[0]); changed(); });
    document.getElementById('nve-device').addEventListener('change', event => { frame.style.width = event.target.value; });
    document.getElementById('nve-product')?.addEventListener('change', event => {
        if (dirty && !window.confirm('Bạn có thay đổi chưa lưu. Chuyển sản phẩm và bỏ các thay đổi này?')) { event.target.value = cfg.product; return; }
        dirty = false; const url = new URL(location.href); url.searchParams.set('product', event.target.value); location.href = url.href;
    });
    document.getElementById('nve-save').addEventListener('click', async () => {
        if (saving) return;
        saving = true; const button = document.getElementById('nve-save'); button.disabled = true; message('Đang lưu…');
        try {
            const payload = { ...data, fields: { ...data.fields } };
            Object.entries(cfg.schema).forEach(([key, field]) => { if (['posts', 'products'].includes(field.type) && Array.isArray(payload.fields[key])) payload.fields[key] = payload.fields[key].join(','); });
            const body = new URLSearchParams({ action: 'npe_visual_save', nonce: cfg.nonce, post: cfg.post, data: JSON.stringify(payload) });
            const response = await fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.data?.message || 'Không lưu được. Kiểm tra phiên đăng nhập rồi thử lại.');
            dirty = false; data.productFields = {}; message(result.data.message); frame.src = frame.src.replace(/([?&])_nve_refresh=\d+/, '$1').replace(/[?&]$/, '') + '&_nve_refresh=' + Date.now();
        } catch (error) { message(error.message, true); }
        finally { saving = false; button.disabled = false; }
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    frame.addEventListener('load', connect);
    renderSections(); renderFields();
    if (frame.contentDocument?.readyState === 'complete') connect();
})(jQuery);
