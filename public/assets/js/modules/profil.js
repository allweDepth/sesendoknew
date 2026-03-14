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

		this.loadData();
	}

	// ======================================================
	// LOAD PROFIL DARI DYNAMIC
	// ======================================================

	loadProfil() {
		const form = $('form[name="profil"]');

		const id = form.attr("id_row");

		if (!id) return;

		this.ajax
			.post("/dynamic", {
				action: "edit",
				tbl: "profil",
				id: id,
			})
			.then((res) => {
				if (!res || !res.data) return;

				const data = res.data;

				Object.keys(data).forEach((k) => {
					const field = form.find(`[name="${k}"]`);

					if (field.length) {
						field.val(data[k]);
					}
				});

				// trigger watcher agar card update
				form.trigger("change");
			});
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

			// login terakhir
			$("#card_login").text(form.find('[name="tgl_login"]').val() || "-");
		};

		form.on("change keyup", "input,textarea", syncCard);

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
	loadData() {
		this.ajax.request({
			data: {
				action: "edit",
				tbl: "profil",
				id_row: window.app.user.id,
			},
			success: (res) => {
				if (!res.success) return;

				const data = res.data;

				const form = $('form[name="profil"]');

				Object.keys(data).forEach((k) => {
					form.find(`[name="${k}"]`).val(data[k]);
				});

				form.trigger("change");
			},
		});
	}
}
