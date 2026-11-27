$(function () {
    const navigation = document.getElementById('workspaceNavigation');
    const toggle = document.getElementById('toggleSidebar');
    if (!navigation || !toggle) return;

    const drawer = $(navigation);
    const mobile = matchMedia('(max-width: 900px)');
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const close = navigation.querySelector('.workspace-nav-close');
    const links = [...navigation.querySelectorAll('a[data-spa]')];
    const titles = [...navigation.querySelectorAll('.workspace-group > .title')];
    const formDrawer = $('#mainContext > .sidebarkanan');

    function syncNavigation() {
        const visible = mobile.matches ? drawer.hasClass('visible') : !document.body.classList.contains('workspace-nav-collapsed');
        navigation.inert = !visible;
        toggle.setAttribute('aria-expanded', String(visible));
    }

    drawer.sidebar({
        context: $('#mainContext'),
        transition: 'overlay',
        mobileTransition: 'overlay',
        dimPage: true,
        scrollLock: false,
        onVisible() {
            syncNavigation();
            if (mobile.matches) close.focus({ preventScroll: true });
            revealActiveLink();
        },
        onHide() {
            if (navigation.contains(document.activeElement)) toggle.focus();
        },
        onHidden: syncNavigation
    });

    const setExpanded = (content, expanded) => {
        if (content.jquery) content = content[0];
        content.previousElementSibling.setAttribute('aria-expanded', String(expanded));
        content.inert = !expanded;
    };
    drawer.accordion({
        duration: reducedMotion.matches ? 0 : 165,
        exclusive: false,
        animateChildren: false,
        onOpening() {
            const opening = this[0].previousElementSibling;
            titles.forEach((title, index) => {
                if (title !== opening && title.getAttribute('aria-expanded') === 'true') drawer.accordion('close', index);
            });
            setExpanded(this, true);
        },
        onClosing() { setExpanded(this, false); },
        onOpen() {
            if (this.querySelector('[aria-current="page"]')) revealActiveLink();
        }
    });
    titles.forEach(title => setExpanded(title.nextElementSibling, false));

    function revealActiveLink() {
        const current = navigation.querySelector('a[aria-current="page"]');
        if (!current || navigation.inert || !current.getClientRects().length) return;
        const bounds = navigation.getBoundingClientRect();
        const top = navigation.querySelector('.workspace-identity').getBoundingClientRect().bottom;
        const link = current.getBoundingClientRect();
        if (link.top < top) navigation.scrollTop += link.top - top - 6;
        else if (link.bottom > bounds.bottom) navigation.scrollTop += link.bottom - bounds.bottom + 6;
    }

    function openActiveGroup() {
        const current = navigation.querySelector('a[aria-current="page"]');
        const title = current?.closest('.workspace-group')?.querySelector('.title');
        if (title && !title.classList.contains('active')) {
            $(title.nextElementSibling).stop(true, true);
            drawer.accordion('open', titles.indexOf(title));
        } else {
            revealActiveLink();
        }
    }

    document.addEventListener('workspace:navigation', openActiveGroup);
    openActiveGroup();
    syncNavigation();

    mobile.addEventListener('change', () => {
        if (drawer.hasClass('visible')) drawer.sidebar('hide');
        syncNavigation();
    });
    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            if (formDrawer.hasClass('visible')) formDrawer.sidebar('hide', () => drawer.sidebar('show'));
            else drawer.sidebar('toggle');
            return;
        }
        const collapsed = document.body.classList.toggle('workspace-nav-collapsed');
        try { localStorage.setItem('sesendok-navigation', collapsed ? 'collapsed' : 'expanded'); } catch (_) {}
        syncNavigation();
    });
    close.addEventListener('click', () => drawer.sidebar('hide'));
    navigation.addEventListener('click', event => {
        if (mobile.matches && !event.metaKey && !event.ctrlKey && !event.altKey && !event.shiftKey && event.target.closest('a[data-spa]')) {
            drawer.sidebar('hide');
        }
    });
    document.addEventListener('keydown', event => {
        if (!mobile.matches || !drawer.hasClass('visible') || document.querySelector('.workspace-menu-dialog.visible')) return;
        if (event.key === 'Escape') drawer.sidebar('hide');
        if (event.key !== 'Tab') return;
        const controls = [...navigation.querySelectorAll('button, a[href]')].filter(control => control.offsetParent && !control.closest('[inert]'));
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    const menuSearch = $('#workspaceMenuSearch');
    const field = document.getElementById('workspaceMenuQuery');
    const results = menuSearch[0].querySelector('.workspace-search-results');
    const status = menuSearch[0].querySelector('[role="status"]');
    const shortcut = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K';
    document.querySelector('[data-menu-shortcut]').textContent = shortcut;
    const clean = value => value.toLowerCase().replaceAll(/[^\p{L}\p{N}]+/gu, ' ').trim();
    const catalog = links.map(link => {
        const group = link.closest('.workspace-group')?.querySelector('.workspace-nav-text').textContent.trim();
        const label = link.textContent.trim();
        return { link, label, group, search: clean([group, label, link.dataset.title].filter(Boolean).join(' ')) };
    });
    const isPlainClick = event => !event.metaKey && !event.ctrlKey && !event.altKey && !event.shiftKey;

    function renderResults() {
        const query = clean(field.value);
        const terms = query.split(' ').filter(Boolean);
        const matches = catalog.filter(entry => terms.every(term => entry.search.includes(term)));
        if (query) matches.sort((left, right) => Number(clean(right.label) === query) - Number(clean(left.label) === query));
        results.replaceChildren(...matches.map(({link, label, group}) => {
            const result = document.createElement('a');
            const title = document.createElement('strong');
            result.className = 'item';
            result.href = link.href;
            title.textContent = label;
            result.append(title);
            if (group) {
                const context = document.createElement('small');
                context.textContent = group;
                result.append(context);
            }
            result.addEventListener('click', event => {
                if (!isPlainClick(event)) return;
                event.preventDefault();
                menuSearch.modal('hide');
                link.click();
            });
            return result;
        }));
        status.textContent = matches.length ? `${matches.length} menu` : 'Menu tidak ditemukan.';
        results.scrollTop = 0;
    }

    menuSearch.modal({
        autofocus: false,
        duration: reducedMotion.matches ? 0 : 150,
        onVisible() { field.focus({ preventScroll: true }); }
    });
    function showSearch() {
        field.value = '';
        renderResults();
        menuSearch.modal('show');
    }
    document.querySelectorAll('[data-menu-search]').forEach(button => button.addEventListener('click', () => {
        if (mobile.matches && drawer.hasClass('visible')) drawer.sidebar('hide', showSearch);
        else showSearch();
    }));
    document.addEventListener('keydown', event => {
        if (!(event.metaKey || event.ctrlKey) || event.altKey || event.key.toLowerCase() !== 'k') return;
        event.preventDefault();
        if (mobile.matches && drawer.hasClass('visible')) drawer.sidebar('hide', showSearch);
        else showSearch();
    });
    field.addEventListener('input', renderResults);
    field.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'Enter') {
            event.preventDefault();
            const first = results.querySelector('a');
            if (event.key === 'Enter') first?.click();
            else first?.focus();
        }
    });
    results.addEventListener('keydown', event => {
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'ArrowUp') (event.target.previousElementSibling || field).focus();
        else event.target.nextElementSibling?.focus();
    });

    const themes = $('#workspaceTheme');
    const options = [...themes[0].querySelectorAll('[data-style]')];
    const applyStyle = (style, announce = false) => {
        const selected = options.find(option => option.dataset.style === style) || options[0];
        document.body.dataset.workspaceStyle = selected.dataset.style;
        options.forEach(option => option.setAttribute('aria-pressed', String(option === selected)));
        if (!announce) return;
        try { localStorage.setItem('sesendok-workspace-style', selected.dataset.style); } catch (_) {}
        themes[0].querySelector('[data-theme-status]').textContent = `Tema ${selected.textContent.trim()} aktif.`;
    };
    applyStyle(document.body.dataset.workspaceStyle);
    options.forEach(option => option.addEventListener('click', () => applyStyle(option.dataset.style, true)));
    themes.modal({
        autofocus: false,
        duration: reducedMotion.matches ? 0 : 150,
        onVisible() { themes[0].querySelector('[aria-pressed="true"]').focus(); }
    });
    document.querySelector('[data-workspace-theme]').addEventListener('click', () => {
        $('#userMenu').dropdown('hide');
        themes.modal('show');
    });
});
