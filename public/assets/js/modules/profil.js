/**
 * ============================================================
 * PROFIL MODULE
 * ============================================================
 */

class ProfilModule {
	constructor() {
		this.state = window.app.state;
		this.ajax = window.app.ajax;
		this.state.module = "profil";
		this.state.setTable("profil");
	}

	init() {
		this.renderLayout();
		this.initEngine();
	}

	renderLayout() {
		const html = `
            <div class="ui segment">
                <h3 class="ui header">Profil</h3>
                <div id="table-container"></div>
                <div id="form-container" style="display:none;"></div>
            </div>
        `;

		$(this.mainContainer).html(html);
	}

	initEngine() {
		this.tableManager = new TableManager({
			state: this.state,
			ajax: this.ajax,
			container: "#table-container",
		});

		this.formContainer = new FormContainerManager({
			container: "#form-container",
		});

		this.formEngine = new FormEngine({
			state: this.state,
			ajax: this.ajax,
			formSelector: "#dynamic-form",
		});

		this.tableManager.init();
		this.formContainer.init();
		this.formEngine.init();
	}

	destroy() {
		this.tableManager.destroy();
		this.formEngine.destroy();
		this.formContainer.destroy();

		$(this.mainContainer).empty();
	}
}
