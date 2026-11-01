jQuery(function ($) {
  $('.nem-admin-select').on('click', function () {
    const card = $(this).closest('.nem-admin-image');
    const picker = wp.media({
      title: 'Chọn ảnh trang chủ',
      button: { text: 'Dùng ảnh này' },
      library: { type: 'image' },
      multiple: false
    });
    picker.on('open', function () {
      const id = Number(card.find('input[type=hidden]').val());
      if (id) picker.state().get('selection').add(wp.media.attachment(id));
    });
    picker.on('select', function () {
      const image = picker.state().get('selection').first().toJSON();
      card.find('input[type=hidden]').val(image.id);
      const preview = $('<img>').attr({
        src: image.sizes?.large?.url || image.url,
        alt: image.alt || image.title || ''
      }).css({ maxWidth: '100%', maxHeight: '180px', objectFit: 'contain' });
      card.find('.nem-admin-preview').empty().append(preview);
    });
    picker.open();
  });
});
