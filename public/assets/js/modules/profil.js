/**
 * ============================================================
 * PROFIL MODULE
 * ============================================================
 */

class ProfilModule {
	constructor() {
		this.state = window.app.state;
		this.ajax = window.app.ajax;
		this.mainContainer = "#main-content";
		this.state.module = "profil";
		this.state.setTable("profil");
		this.tableManager = null;
		this.formEngine = null;
		this.formContainer = null;
	}

	init() {
		this.renderLayout();
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

	destroy() {
		this.tableManager.destroy();
		this.formEngine.destroy();
		this.formContainer.destroy();

		$(this.mainContainer).empty();
	}
}
