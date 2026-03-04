class ResetTabelModule {

	constructor(container) {

		this.container =
			container || document.querySelector('[data-module="reset_tabel"]');

	}

	init(){

		if(!this.container) return;

		this.bind();

	}

	bind(){

		this.container.querySelectorAll('[data-action]').forEach(btn=>{

			btn.onclick = (e)=>{

				e.preventDefault();

				const action = btn.dataset.action;
				const table  = btn.dataset.table;

				if(action.startsWith('backup')){
					window.location='/reset_tabel/backup?action='+action;
					return;
				}

				if(action.startsWith('restore')){
					window.location='/reset_tabel/restore?action='+action;
					return;
				}

				$('#dialogTitle').html(
					'<i class="trash red icon"></i> Konfirmasi Reset'
				);

				$('#dialogMessage').html(
					`Yakin menjalankan <b>${action}</b> pada tabel <b>${table}</b>?`
				);

				$('#globalDialog')
				.modal({
					closable:false,
					onApprove:()=>{

						fetch('/reset_tabel/reset',{
							method:'POST',
							headers:{
								'Content-Type':'application/x-www-form-urlencoded'
							},
							body:`action=${action}&table=${table}`
						})
						.then(r=>r.json())
						.then(res=>{
							Toast.success(res.message || 'Berhasil');
						});

					}
				})
				.modal('show');

			};

		});

	}

}

window.Modules = window.Modules || {};
window.Modules.reset_tabel = ResetTabelModule;