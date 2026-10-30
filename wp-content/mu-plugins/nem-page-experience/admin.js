(function ($) {
  'use strict';
  $(function () {
    var list = $('.npe-builder-order');
    var status = $('.npe-order-status');
    function refresh(message) {
      list.children().each(function (index) {
        $(this).find('.npe-move-up').prop('disabled', index === 0);
        $(this).find('.npe-move-down').prop('disabled', index === list.children().length - 1);
      });
      if (message) status.text(message);
    }
    if ($.fn.sortable) list.sortable({handle: '.npe-drag-handle', placeholder: 'npe-sort-placeholder', update: function () { refresh('Đã thay đổi thứ tự. Bấm Lưu để áp dụng.'); }});
    list.on('click', '.npe-move-up, .npe-move-down', function () {
      var button = $(this), item = button.closest('li');
      if (button.hasClass('npe-move-up')) item.insertBefore(item.prev());
      else item.insertAfter(item.next());
      refresh('Đã thay đổi thứ tự. Bấm Lưu để áp dụng.');
      button.trigger('focus');
    });
    refresh();
    $('.npe-builder').on('click', '.npe-select-image', function () {
      var field = $(this).closest('.npe-image-field');
      var frame = wp.media({title: 'Chọn ảnh hiển thị', button: {text: 'Dùng ảnh này'}, library: {type: 'image'}, multiple: false});
      frame.on('select', function () {
        var image = frame.state().get('selection').first().toJSON();
        field.find('.npe-image-id').val(image.id);
        var preview = image.sizes && image.sizes.medium ? image.sizes.medium.url : image.url;
        field.find('.npe-image-preview').empty().append($('<img>', {src: preview, alt: image.alt || image.title || ''}));
      });
      frame.open();
    }).on('click', '.npe-remove-image', function () {
      var field = $(this).closest('.npe-image-field');
      field.find('.npe-image-id').val('0');
      field.find('.npe-image-preview').empty().append($('<span>').text('Chưa chọn ảnh'));
    });
  });
})(jQuery);
