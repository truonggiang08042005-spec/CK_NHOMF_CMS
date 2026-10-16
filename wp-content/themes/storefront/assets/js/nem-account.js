/**
 * NEM - Trang Tài khoản:
 * Nút "ĐĂNG KÝ" luôn giữ màu đen (không bị disable chuyển màu xám).
 * Kiểm tra điều khoản khi người dùng nhấn submit.
 */
( function () {
	var form = document.querySelector( '.woocommerce-form-register' );
	var terms = document.getElementById( 'reg_terms' );
	var btn = document.getElementById( 'nem-register-submit' );
	if ( ! form || ! terms || ! btn ) {
		return;
	}

	// Đảm bảo nút luôn active, không bị disable
	btn.disabled = false;

	form.addEventListener( 'submit', function ( e ) {
		if ( ! terms.checked ) {
			e.preventDefault();
			if ( typeof terms.reportValidity === 'function' ) {
				terms.setCustomValidity( 'Bạn cần đồng ý với các điều khoản của NEM để đăng ký.' );
				terms.reportValidity();
			} else {
				alert( 'Bạn cần đồng ý với các điều khoản của NEM để đăng ký.' );
			}
			terms.focus();
		}
	} );

	terms.addEventListener( 'change', function () {
		if ( terms.checked && typeof terms.setCustomValidity === 'function' ) {
			terms.setCustomValidity( '' );
		}
	} );
} )();

