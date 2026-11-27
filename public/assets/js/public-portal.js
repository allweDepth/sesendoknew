(() => {
  const nav = document.querySelector('.public-nav');
  const menuToggle = nav.querySelector('.nav-toggle');
  const setMenu = (open) => {
    nav.classList.toggle('open', open);
    menuToggle.setAttribute('aria-expanded', String(open));
  };
  menuToggle.addEventListener('click', () => setMenu(!nav.classList.contains('open')));
  nav.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('open')) {
      setMenu(false);
      menuToggle.focus();
    }
  });

  const library = document.querySelector('[data-library]');
  if (library) {
    const search = library.querySelector('[data-search]');
    const filters = [...library.querySelectorAll('[data-filter]')];
    const cards = [...library.querySelectorAll('[data-card]')];
    const groups = [...library.querySelectorAll('[data-card-group]')];
    const empty = library.querySelector('[data-empty]');
    const count = library.querySelector('[data-count]');
    let category = '';
    const update = () => {
      const terms = search.value.trim().toLocaleLowerCase('id').split(/\s+/).filter(Boolean);
      let matches = 0;
      cards.forEach((card) => {
        const words = card.dataset.keywords.toLocaleLowerCase('id');
        const visible = (!category || card.dataset.category === category) && terms.every((term) => words.includes(term));
        card.hidden = !visible;
        if (visible) matches++;
      });
      filters.forEach((filter) => {
        const selected = filter.dataset.filter === category;
        filter.classList.toggle('is-selected', selected);
        filter.setAttribute('aria-pressed', String(selected));
      });
      groups.forEach((group) => { group.hidden = !group.querySelector('[data-card]:not([hidden])'); });
      empty.hidden = matches > 0;
      count.textContent = `${matches} artikel`;
    };
    search.addEventListener('input', update);
    filters.forEach((filter) => filter.addEventListener('click', () => {
      category = filter.dataset.filter;
      update();
    }));
    library.querySelector('[data-reset]')?.addEventListener('click', () => {
      search.value = '';
      category = '';
      update();
      search.focus();
    });
  }

  const article = document.querySelector('[data-reading]');
  const hero = document.querySelector('[data-parallax]');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let progress;
  if (article) {
    progress = document.createElement('div');
    progress.className = 'reading-progress';
    progress.setAttribute('aria-hidden', 'true');
    document.querySelector('.portal-header').append(progress);
  }
  if (article || hero) {
    let scheduled = false;
    const draw = () => {
      scheduled = false;
      if (hero) {
        const offset = reduced.matches ? 0 : Math.max(-34, Math.min(34, -hero.getBoundingClientRect().top * .12));
        hero.style.setProperty('--parallax-offset', `${offset}px`);
      }
      if (article) {
        const bounds = article.getBoundingClientRect();
        const distance = Math.max(1, bounds.height - window.innerHeight * .7);
        const percent = Math.min(1, Math.max(0, (window.innerHeight * .3 - bounds.top) / distance));
        progress.style.transform = `scaleX(${percent})`;
      }
    };
    const schedule = () => {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(draw);
    };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule);
    reduced.addEventListener('change', schedule);
    schedule();
  }

  if (article?.dataset.section === 'data_teknis') {
    article.querySelectorAll('.reading-body table').forEach((table, tableIndex) => {
      if (table.tHead?.rows[0]?.cells[0]?.textContent.trim() !== 'Kecamatan') return;
      const rows = [...(table.tBodies[0]?.rows || [])];
      if (!rows.length) return;

      const field = document.createElement('div');
      field.className = 'table-lookup ui form';
      const heading = document.createElement('span');
      heading.id = `district-label-${tableIndex}`;
      heading.textContent = 'Tampilkan kecamatan';

      const select = document.createElement('select');
      select.className = 'ui fluid search selection dropdown';
      select.setAttribute('aria-labelledby', heading.id);
      select.add(new Option('Semua kecamatan', 'all', true, true));
      rows.forEach((row, index) => select.add(new Option(row.cells[0].textContent.trim(), String(index))));
      field.append(heading, select);
      table.before(field);

      select.addEventListener('change', () => {
        rows.forEach((row, index) => { row.hidden = select.value !== 'all' && select.value !== String(index); });
      });
      if (window.jQuery?.fn.dropdown) {
        window.jQuery(select).dropdown({fullTextSearch: true, forceSelection: false});
        field.querySelector('.dropdown > input.search')?.setAttribute('aria-labelledby', heading.id);
      }
    });
  }

  if (article?.dataset.section === 'pelayanan') {
    const steps = article.querySelector('.reading-body ol');
    if (!steps) return;
    steps.classList.add('service-checklist');
    const items = [...steps.children];
    items.forEach((item) => {
      const label = document.createElement('label');
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      const text = document.createElement('span');
      text.append(...item.childNodes);
      label.append(checkbox, text);
      item.append(label);
    });
    const result = document.createElement('p');
    result.setAttribute('aria-live', 'polite');
    const updateChecklist = () => {
      const completed = steps.querySelectorAll(':checked').length;
      result.textContent = completed === items.length
        ? 'Semua langkah persiapan sudah ditandai.'
        : `${completed} dari ${items.length} langkah siap`;
    };
    steps.after(result);
    steps.addEventListener('change', updateChecklist);
    updateChecklist();
  }
})();
