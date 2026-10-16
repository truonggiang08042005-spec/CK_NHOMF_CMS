document.addEventListener('click', function (event) {
    const button = event.target.closest('.storefront-custom-cart-quantity-button');

    if (!button) {
        return;
    }

    const control = button.closest('.storefront-custom-cart-quantity-control');
    const input = control && control.querySelector('input.qty');

    if (!input) {
        return;
    }

    const step = Number(input.step);
    const amount = Number.isFinite(step) && step > 0 ? step : 1;
    const minimum = input.min === '' ? -Infinity : Number(input.min);
    const maximum = input.max === '' ? Infinity : Number(input.max);
    const current = input.value === '' || !Number.isFinite(Number(input.value))
        ? (Number.isFinite(minimum) ? minimum : 0)
        : Number(input.value);
    const direction = Number(button.dataset.quantityChange);
    const precision = Math.min(
        10,
        Math.max(
            (String(amount).split('.')[1] || '').length,
            (String(current).split('.')[1] || '').length
        )
    );
    const next = Math.min(
        maximum,
        Math.max(minimum, Number((current + direction * amount).toFixed(precision)))
    );

    input.value = String(next);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
});
