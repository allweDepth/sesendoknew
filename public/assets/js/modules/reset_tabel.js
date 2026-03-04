class ResetTabelModule {

    constructor(container){
        this.container = container;
        this.bindActions();
    }

    bindActions(){
        this.container.querySelectorAll('[data-action]').forEach(btn => {

            btn.addEventListener('click', e => {

                const action = btn.dataset.action;
                const table  = btn.dataset.table ?? null;

                if(action.startsWith('backup')){
                    if(!confirm('Backup sekarang?')) return;
                    window.location = '/reset_tabel/backup?action=' + action;
                    return;
                }

                if(action.startsWith('restore')){
                    if(!confirm('Restore database? Data akan tertimpa!')) return;
                    window.location = '/reset_tabel/restore?action=' + action;
                    return;
                }

                if(!table) return;

                if(!confirm(`Aksi ${action} pada tabel "${table}" ?`)) return;

                fetch('/reset_tabel/reset',{
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'action='+action+'&table='+table
                })
                .then(r=>r.json())
                .then(()=> location.reload());

            });

        });
    }

}

// REGISTER
window.Modules = window.Modules || {};
window.Modules.reset_tabel = ResetTabelModule;

// AUTO INIT (INI YANG PENTING)
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-module="reset_tabel"]')
        .forEach(el => new ResetTabelModule(el));
});