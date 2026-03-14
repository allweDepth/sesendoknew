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
	}

	init() {
		this.initUI();
		this.bindFormWatcher();
		this.bindPhotoWatcher();
	}

	// ======================================================
	// UI INIT
	// ======================================================

	initUI() {
		$(".ui.dropdown").dropdown();

		$(".blurring.dimmable.image").dimmer({
			on: "hover",
		});
	}

	// ======================================================
	// SYNC FORM → CARD
	// ======================================================

	bindFormWatcher() {
		const form = $('form[name="profil"]');

		const syncCard = () => {
			$("#card_nama").text(form.find('[name="nama"]').val() || "-");
			$("#card_type").text(form.find('[name="type_user"]').val() || "-");
			$("#card_tahun").text(form.find('[name="tahun"]').val() || "-");
		};

		form.on("change keyup", "input,textarea", syncCard);

		// initial sync
		setTimeout(syncCard, 300);
	}

	// ======================================================
	// WATCH PHOTO CHANGE
	// ======================================================

	bindPhotoWatcher() {
		$(document).on("upload:success", (e, res) => {
			if (!res || !res.photo) return;

			const url = "uploads/" + res.photo;

			$("#preview_photo").attr("src", url);
		});
	}

	destroy() {
		$(document).off("upload:success");
	}
}
