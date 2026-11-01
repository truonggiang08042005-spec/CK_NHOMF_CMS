(() => {
    'use strict';
    document.querySelectorAll('.npe-navigation').forEach(nav => {
    const desktop = window.matchMedia('(min-width: 761px)');
    const isDesktop = () => desktop.matches && !nav.classList.contains('npe-navigation-drawer');
    const items = [...nav.querySelectorAll('.npe-nav-has-children')];
    const buttonFor = item => [...item.children].find(el => el.classList.contains('npe-submenu-toggle'));
    const listFor = item => [...item.children].find(el => el.classList.contains('npe-nav-submenu'));
    const linksIn = list => [...list.children].map(item => [...item.children].find(el => el.tagName === 'A')).filter(Boolean);
    const setOpen = (item, open) => {
        item.classList.toggle('is-submenu-open', open);
        buttonFor(item)?.setAttribute('aria-expanded', String(open));
        if (open) delete item.dataset.npeClosed;
    };
    const closeBranch = item => {
        setOpen(item, false);
        item.querySelectorAll('.npe-nav-has-children').forEach(child => setOpen(child, false));
    };
    const closeAll = () => items.forEach(closeBranch);

    items.forEach(item => {
        item.addEventListener('pointerenter', event => {
            if (!isDesktop() || event.pointerType === 'touch') return;
            delete item.dataset.npeClosed;
            buttonFor(item)?.setAttribute('aria-expanded', 'true');
        });
        item.addEventListener('pointerleave', () => {
            if (isDesktop() && !item.contains(document.activeElement)) closeBranch(item);
        });
    });

    nav.addEventListener('click', event => {
        const button = event.target.closest('.npe-submenu-toggle');
        if (!button || !nav.contains(button)) return;
        event.preventDefault();
        const item = button.parentElement;
        const open = !item.classList.contains('is-submenu-open');
        [...item.parentElement.children].filter(sibling => sibling !== item).forEach(closeBranch);
        setOpen(item, open);
        if (!open && isDesktop()) item.dataset.npeClosed = 'true';
    });

    nav.addEventListener('focusin', event => {
        if (!isDesktop()) return;
        let item = event.target.closest('.npe-nav-item');
        while (item && nav.contains(item)) {
            delete item.dataset.npeClosed;
            buttonFor(item)?.setAttribute('aria-expanded', 'true');
            item = item.parentElement.closest('.npe-nav-item');
        }
    });

    nav.addEventListener('focusout', () => {
        requestAnimationFrame(() => items.forEach(item => {
            if (!item.contains(document.activeElement) && (!isDesktop() || !item.matches(':hover'))) closeBranch(item);
        }));
    });

    nav.addEventListener('keydown', event => {
        if (!['Escape', 'ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        const item = event.target.closest('.npe-nav-item');
        if (!item) return;
        const list = item.parentElement;
        const children = listFor(item);
        const links = linksIn(list);
        const link = [...item.children].find(el => el.tagName === 'A');
        const index = links.indexOf(link);
        const parentItem = list.classList.contains('npe-nav-submenu') ? list.parentElement : null;
        let target;
        if (event.key === 'Escape') {
            const branch = children && (item.classList.contains('is-submenu-open') || buttonFor(item)?.getAttribute('aria-expanded') === 'true') ? item : parentItem;
            if (!branch) return;
            closeBranch(branch);
            buttonFor(branch)?.focus();
            branch.dataset.npeClosed = 'true';
            buttonFor(branch)?.setAttribute('aria-expanded', 'false');
            event.preventDefault();
            event.stopPropagation();
            return;
        }
        if ((event.key === 'ArrowDown' && children && list.classList.contains('npe-nav-list')) || (event.key === 'ArrowRight' && children && parentItem)) {
            setOpen(item, true);
            target = linksIn(children)[0];
        } else if (event.key === 'ArrowLeft' && parentItem) {
            closeBranch(parentItem);
            target = buttonFor(parentItem) || parentItem.querySelector('a');
        } else if (event.key === 'Home') target = links[0];
        else if (event.key === 'End') target = links[links.length - 1];
        else {
            const step = ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : -1;
            target = links[(index + step + links.length) % links.length];
        }
        if (target) {
            event.preventDefault();
            target.focus();
            if (event.key === 'ArrowLeft' && parentItem) {
                parentItem.dataset.npeClosed = 'true';
                buttonFor(parentItem)?.setAttribute('aria-expanded', 'false');
            }
        }
    });

    document.addEventListener('click', event => {
        if (!nav.contains(event.target)) closeAll();
    });
    const reset = () => {
        closeAll();
        items.forEach(item => delete item.dataset.npeClosed);
    };
    if (desktop.addEventListener) desktop.addEventListener('change', reset);
    });
})();
