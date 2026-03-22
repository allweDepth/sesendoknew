class KontrakModule {
	init() {
		this.render(); // // render UI awal
		this.bindEvents(); // // binding event
	}

	render() {
		const container = document.querySelector("[data-spa-container]"); // // ambil container SPA

		if (!container) return; // // prevent crash

		container.innerHTML = `
		<div class="page-kontrak">
			<h2>Kontrak</h2>

			<button id="btnAddKontrak">Tambah Kontrak</button>

			<div id="tableKontrak"></div>
		</div>
	`; // // inject UI
	}

	bindEvents() {
		const btn = document.getElementById("btnAddKontrak"); // // ambil tombol

		if (!btn) return; // // prevent null error

		btn.addEventListener("click", () => {
			this.openFlyout();
		});
	}

	openFlyout() {
		if (typeof FlyoutController !== "undefined") {
			new FlyoutController().open({
				module: "kontrak",
				action: "add",
			}); // // trigger flyout
		}
	}
}
