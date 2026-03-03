class ResetTabelModule {

    constructor(container){
        this.container = container;
        this.init();
    }

    init(){
        this.bindActions();
    }

    bindActions(){
        this.container.querySelectorAll('[data-action]')
            .forEach(btn => {

                btn.addEventListener('click', e => {

                    const action = btn.dataset.action;
                    const table  = btn.dataset.table ?? null;

                    if(action.startsWith('backup')){
                        window.location = '/reset_tabel/backup?action=' + action;
                        return;
                    }

                    if(action.startsWith('restore')){
                        window.location = '/reset_tabel/restore?action=' + action;
                        return;
                    }

                    if(!table) return;

                    if(!confirm(action.toUpperCase() + ' ?')) return;

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

window.Modules = window.Modules || {};
window.Modules.reset_tabel = ResetTabelModule;