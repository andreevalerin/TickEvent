(() => {
    const menu = document.querySelector('[data-menu]');
    const navigation = document.getElementById('navegacion-principal');

    if (menu && navigation) {
        const closeMenu = () => {
            navigation.classList.remove('open');
            menu.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-label', 'Abrir menú');
        };

        menu.addEventListener('click', () => {
            const expanded = menu.getAttribute('aria-expanded') !== 'true';
            navigation.classList.toggle('open', expanded);
            menu.setAttribute('aria-expanded', String(expanded));
            menu.setAttribute('aria-label', expanded ? 'Cerrar menú' : 'Abrir menú');
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && menu.getAttribute('aria-expanded') === 'true') {
                closeMenu();
                menu.focus();
            }
        });
        document.addEventListener('click', event => {
            if (!navigation.contains(event.target) && !menu.contains(event.target)) closeMenu();
        });
        window.matchMedia('(min-width: 901px)').addEventListener('change', closeMenu);
    }

    const tablist = document.querySelector('[data-pestanas]');
    if (tablist) {
        const tabs = [...tablist.querySelectorAll('[data-panel]')];
        tablist.setAttribute('role', 'tablist');
        const selectTab = selected => {
            tabs.forEach(tab => {
                const active = tab === selected;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                document.getElementById(tab.dataset.panel).hidden = !active;
            });
        };
        tabs.forEach((tab, index) => {
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', tab.dataset.panel);
            const panel = document.getElementById(tab.dataset.panel);
            panel.setAttribute('role', 'tabpanel');
            panel.tabIndex = 0;
            tab.addEventListener('click', () => selectTab(tab));
            tab.addEventListener('keydown', event => {
                const targets = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1 };
                if (Object.hasOwn(targets, event.key)) {
                    event.preventDefault();
                    const next = tabs[targets[event.key]];
                    selectTab(next);
                    next.focus();
                }
            });
        });
        selectTab(tabs[0]);
    }

    const selection = document.getElementById('seleccion-entradas');
    if (selection) {
        const summary = document.querySelector('[data-resumen-sector]');
        const submit = document.querySelector('[data-comprar]');
        const updateSelection = () => {
            const radio = selection.querySelector('input[name="tarifa_id"]:checked:not(:disabled)');
            submit.disabled = !radio;
            summary.textContent = radio
                ? `${radio.dataset.zona} · S/${Number(radio.dataset.precio).toLocaleString('es-PE', { maximumFractionDigits: 2 })}`
                : 'Selecciona un sector';
        };
        selection.addEventListener('change', updateSelection);
        window.addEventListener('pageshow', updateSelection);
        updateSelection();
    }
})();
