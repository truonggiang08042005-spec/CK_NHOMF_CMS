(function (wp) {
    'use strict';
    if (!wp || !wp.plugins || !wp.element) return;
    var el = wp.element.createElement;
    var Panel = wp.editor && wp.editor.PluginDocumentSettingPanel || wp.editPost && wp.editPost.PluginDocumentSettingPanel;
    if (!Panel) return;
    function PreviewPanel() {
        var state = wp.element.useState('members');
        var mode = state[0], setMode = state[1];
        wp.element.useEffect(function () {
            function apply() {
                document.documentElement.dataset.nemAccountPreview = mode;
                document.querySelectorAll('iframe[name="editor-canvas"]').forEach(function (frame) {
                    if (frame.contentDocument) frame.contentDocument.documentElement.dataset.nemAccountPreview = mode;
                });
            }
            apply();
            var observer = new MutationObserver(apply);
            observer.observe(document.body, { childList: true, subtree: true });
            document.addEventListener('load', apply, true);
            return function () { observer.disconnect(); document.removeEventListener('load', apply, true); };
        }, [mode]);
        return el(Panel, {name: 'nem-account-preview', title: 'Thiết kế trang Tài khoản'},
            el(wp.components.SelectControl, {
                label: 'Bố cục đang chỉnh sửa', value: mode, onChange: setMode,
                options: [
                    {label: 'Khách đã đăng nhập', value: 'members'},
                    {label: 'Khách chưa đăng nhập', value: 'guests'},
                    {label: 'Tất cả bố cục', value: 'all'}
                ],
                help: 'Tên, điện thoại, địa chỉ và đơn hàng lấy theo khách đăng nhập. Dùng Danh sách khối để di chuyển từng thành phần.'
            })
        );
    }
    wp.plugins.registerPlugin('nem-account-design-preview', {render: PreviewPanel});
})(window.wp);
