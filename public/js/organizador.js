(() => {
    const menu = document.querySelector('[data-menu-organizador]');
    const navigation = document.getElementById('menu-organizador');
    const closeMenu = () => {
        navigation?.classList.remove('is-open');
        menu?.setAttribute('aria-expanded', 'false');
        menu?.setAttribute('aria-label', 'Abrir menú');
    };
    menu?.addEventListener('click', () => {
        const open = menu.getAttribute('aria-expanded') !== 'true';
        navigation.classList.toggle('is-open', open);
        menu.setAttribute('aria-expanded', String(open));
        menu.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menu.focus();
        }
    });
    window.matchMedia('(min-width: 901px)').addEventListener('change', closeMenu);
    document.querySelector('[data-errores]')?.focus();

    const dialog = document.querySelector('[data-dialogo-retirar]');
    let pendingForm;
    document.querySelectorAll('[data-retirar-evento]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!dialog?.showModal) return;
            event.preventDefault();
            pendingForm = form;
            dialog.querySelector('[data-evento-retirado]').textContent = form.dataset.titulo;
            dialog.returnValue = '';
            dialog.showModal();
        });
    });
    dialog?.addEventListener('close', () => {
        if (dialog.returnValue === 'retirar' && pendingForm) HTMLFormElement.prototype.submit.call(pendingForm);
        pendingForm = null;
    });

    const editor = document.querySelector('[data-editor-evento]');
    if (!editor) return;
    const zones = editor.querySelector('[data-zonas]');
    const hint = editor.querySelector('[data-editor-ayuda]');
    let nextZone = Math.max(-1, ...[...zones.children].map(zone => Number(zone.dataset.zona))) + 1;
    const updateCapacity = () => {
        const capacity = [...zones.querySelectorAll('[data-capacidad]')].reduce((total, input) => total + Math.max(0, Number(input.value) || 0), 0);
        editor.querySelector('[data-aforo]').textContent = capacity.toLocaleString('es-PE');
    };
    const instantiate = (id, zone, fare = 0) => {
        const template = document.getElementById(id).content.cloneNode(true);
        template.querySelectorAll('*').forEach(element => {
            for (const attribute of [...element.attributes]) {
                if (attribute.value.includes('__ZONA__') || attribute.value.includes('__TARIFA__')) {
                    element.setAttribute(attribute.name, attribute.value.replaceAll('__ZONA__', String(zone)).replaceAll('__TARIFA__', String(fare)));
                }
            }
        });
        return template;
    };
    editor.querySelector('[data-agregar-zona]').addEventListener('click', () => {
        if (zones.children.length >= 30) { hint.textContent = 'Puedes configurar hasta 30 zonas por evento.'; return; }
        zones.append(instantiate('plantilla-zona', nextZone++));
        zones.lastElementChild.querySelector('input[type=text]').focus();
        updateCapacity();
    });
    zones.addEventListener('input', event => {
        updateCapacity();
        if (event.target.matches('[data-nombre-zona]')) {
            event.target.closest('[data-zona]').querySelector('[data-titulo-zona]').textContent = event.target.value || 'Nueva zona';
        }
    });
    zones.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        const zone = button.closest('[data-zona]');
        const fares = zone.querySelector('[data-tarifas]');
        if (button.matches('[data-agregar-tarifa]')) {
            if (fares.children.length >= 20) { hint.textContent = 'Puedes configurar hasta 20 tarifas por zona.'; return; }
            const nextFare = Math.max(-1, ...[...fares.children].map(fare => Number(fare.dataset.tarifa))) + 1;
            fares.append(instantiate('plantilla-tarifa', zone.dataset.zona, nextFare));
            fares.lastElementChild.querySelector('input[type=text]').focus();
        }
        if (button.matches('[data-quitar-tarifa]')) {
            if (fares.children.length === 1) { hint.textContent = 'Cada zona necesita al menos una tarifa.'; return; }
            button.closest('[data-tarifa]').remove();
            zone.querySelector('[data-agregar-tarifa]').focus();
        }
        if (button.matches('[data-quitar-zona]')) {
            if (zones.children.length === 1) { hint.textContent = 'El evento necesita al menos una zona.'; return; }
            zone.remove();
            editor.querySelector('[data-agregar-zona]').focus();
            updateCapacity();
        }
    });
    editor.querySelectorAll('[data-imagen]').forEach(container => {
        const input = container.querySelector('input[type=file]');
        const preview = container.querySelector('[data-preview]');
        const placeholder = container.querySelector('[data-sin-imagen]');
        const restore = container.querySelector('[data-restaurar-imagen]');
        let objectUrl;
        const release = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };
        const reset = () => {
            release();
            input.value = '';
            input.setCustomValidity('');
            preview.hidden = !preview.dataset.original;
            placeholder.hidden = !preview.hidden;
            if (preview.dataset.original) preview.src = preview.dataset.original;
            restore.hidden = true;
        };
        input.addEventListener('change', () => {
            release();
            const file = input.files[0];
            if (!file) { reset(); return; }
            const error = file.size > 5 * 1024 * 1024 ? 'La imagen no puede superar los 5 MB.' : !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ? 'Selecciona una imagen JPG, PNG o WebP.' : '';
            input.setCustomValidity(error);
            restore.hidden = false;
            if (error) { input.reportValidity(); return; }
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.hidden = false;
            placeholder.hidden = true;
        });
        restore.addEventListener('click', reset);
        window.addEventListener('pagehide', release);
    });
    updateCapacity();
})();
