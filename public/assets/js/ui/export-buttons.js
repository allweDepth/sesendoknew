/** Compact, accessible Fomantic export actions on both initial and AJAX views. */
(() => {
    const selector = 'button.ui.button, a.ui.button';
    function compact(button) {
        if (button.dataset.exportCompact === '1') return;
        const icon = Array.from(button.children).find(el => el.matches('i.icon'));
        if (!icon) return;
        const label = button.textContent.trim().replace(/\s+/g, ' ');
        const excel = /\bExcel\b/i.test(label) || icon.matches('.file.excel');
        const pdf = /\bPDF\b/i.test(label) || icon.matches('.file.pdf');
        if (!excel && !pdf) return;
        // Document creation/print settings keep their meaningful action labels.
        if (/generate|buat|surat penawaran|cetak penawaran|pengaturan/i.test(label)) return;
        button.dataset.exportCompact = '1';
        button.classList.add('icon', 'app-export-button');
        button.classList.remove('labeled', 'basic', 'primary', 'secondary', 'red', 'orange', 'yellow', 'olive', 'green', 'teal', 'blue', 'violet', 'purple', 'pink', 'brown', 'grey', 'black');
        button.classList.add(excel ? 'green' : 'red');
        button.setAttribute('aria-label', label || (excel ? 'Ekspor Excel' : 'Ekspor PDF'));
        button.setAttribute('title', label || (excel ? 'Ekspor Excel' : 'Ekspor PDF'));
        if (excel && !icon.matches('.file.excel')) icon.className = 'file excel icon';
        for (const node of Array.from(button.childNodes)) {
            if (node === icon || (node.nodeType === Node.TEXT_NODE && !node.textContent.trim())) continue;
            const hidden = document.createElement('span');
            hidden.className = 'app-export-label';
            button.insertBefore(hidden, node);
            hidden.appendChild(node);
        }
    }
    function scan(root) {
        if (root.nodeType !== Node.ELEMENT_NODE) return;
        if (root.matches(selector)) compact(root);
        root.querySelectorAll(selector).forEach(compact);
    }
    function init() {
        scan(document.body);
        new MutationObserver(records => {
            for (const record of records) for (const node of record.addedNodes) scan(node);
        }).observe(document.body, {childList: true, subtree: true});
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
    else init();
})();
