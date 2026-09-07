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
		this.pendingPhoto = null;
		this.pendingPhotoPreview = null;

		this.state.module = "profil";
		this.state.setTable("profil");
	}

	init() {
		this.initUI();
		this.bindFormWatcher();
		this.bindSubmit();
		this.bindPhotoWatcher();
		this.bindPeriodSelector();
		// this.loadProfil();
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
		$(".ui.dropdown").not("#planningPeriod, #budgetYear").dropdown();

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

		form.on("change keyup", "input,textarea,select", syncCard);

		setTimeout(syncCard, 300);
	}

	bindSubmit() {
		const form = $('form[name="profil"]');
		form.off("submit.profile").on("submit.profile", (e) => {
			e.preventDefault();
			if (window.FormValidation && !FormValidation.validate(form)) return;
			const button = form.find('button[type="submit"], button:not([type])').last();
			const finish = () => button.removeClass("loading disabled").prop("disabled", false);
			this.ajax.request({
				url: "/profil/save",
				method: "POST",
				data: form.serialize(),
				beforeSend: () => button.addClass("loading disabled").prop("disabled", true),
				success: (res) => {
					const data = res.data || {};
					Object.assign(window.app.user, data);
					$("#card_nama").text(data.nama || "-");
					$("#card_tahun").text(data.tahun || "-");
					$(".dash_header .label").text(data.tahun || "");
					if (!this.pendingPhoto) {
						finish();
						return;
					}
					const photo = new FormData();
					photo.append("photo", this.pendingPhoto);
					this.ajax.request({
						url: "/profil/upload-photo",
						method: "POST",
						data: photo,
						processData: false,
						contentType: false,
						success: (photoRes) => {
							const finalUrl = `${photoRes.data.url}${photoRes.data.url.includes("?") ? "&" : "?"}v=${Date.now()}`;
							$("#preview_photo").attr("src", finalUrl);
							$("img[data-user-avatar]").attr("src", finalUrl);
							if (this.pendingPhotoPreview) URL.revokeObjectURL(this.pendingPhotoPreview);
							this.pendingPhoto = null;
							this.pendingPhotoPreview = null;
							$("#profilePhotoInput").val("");
						},
						complete: finish,
					});
				},
				error: finish,
			});
		});
	}

	// ======================================================
	// WATCH PHOTO CHANGE
	// ======================================================

	bindPhotoWatcher() {
		$("#chooseProfilePhoto")
			.off("click.profile")
			.on("click.profile", () => $("#profilePhotoInput").trigger("click"));
		$("#profilePhotoInput")
			.off("change.profile")
			.on("change.profile", (e) => {
				const file = e.target.files[0];
				if (!file) return;
				if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 3 * 1024 * 1024) {
					FormFeedback.error($('form[name="profil"]'), { message: "Foto harus JPG, PNG, atau WebP dan maksimal 3 MB" });
					e.target.value = "";
					return;
				}
				if (this.pendingPhotoPreview) URL.revokeObjectURL(this.pendingPhotoPreview);
				this.pendingPhoto = file;
				this.pendingPhotoPreview = URL.createObjectURL(file);
				$("#preview_photo").attr("src", this.pendingPhotoPreview);
			});
	}

	bindPeriodSelector() {
		this.ajax.request({
			url: "/profil/periods",
			method: "GET",
			success: (res) => {
				const data = res.data || {},
					period = $("#planningPeriod"),
					year = $("#budgetYear"),
					periods = data.periods || [],
					periodMenu = period.find(".menu"),
					yearMenu = year.find(".menu");
				period.dropdown("destroy");
				year.dropdown("destroy");
				periodMenu.empty();
				yearMenu.empty();
				periods.forEach((p) =>
					periodMenu.append(
						$("<div>", {
							class: "item",
							"data-value": String(p.id),
							text: `${data.scope} ${p.periode_mulai}–${p.periode_selesai}${p.keterangan ? " · " + p.keterangan : ""}`,
						}),
					),
				);
				const selected = (data.periods || []).find((p) => Number(p.id) === Number(data.selected_period_id)) ||
					(data.periods || []).find((p) => Number(data.selected_year) >= Number(p.periode_mulai) && Number(data.selected_year) <= Number(p.periode_selesai));
				const fillYears = (periodId, preferredYear = null) => {
					const chosen = periods.find((p) => Number(p.id) === Number(periodId)),
						start = Number(chosen?.periode_mulai),
						end = Number(chosen?.periode_selesai);
					year.dropdown("clear");
					yearMenu.empty();
					if (start && end) for (let value = start; value <= end; value++) {
						yearMenu.append($("<div>", { class: "item", "data-value": String(value), text: String(value) }));
					}
					year.dropdown("refresh");
					if (preferredYear && preferredYear >= start && preferredYear <= end) year.dropdown("set selected", String(preferredYear));
				};
				year.dropdown({
					action: "activate",
					onShow: () => { period.dropdown("hide"); return true; },
					onChange: (value) => $("#card_tahun").text(value || "-"),
				});
				period.dropdown({
					action: "activate",
					onShow: () => { year.dropdown("hide"); return true; },
					onChange: (value) => fillYears(value),
				});
				if (selected) {
					period.dropdown("set selected", String(selected.id));
					fillYears(selected.id, Number(data.selected_year));
				}
			},
		});
	}

	destroy() {
		$(document).off("upload:success");
		if (this.pendingPhotoPreview) URL.revokeObjectURL(this.pendingPhotoPreview);
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
					const field = form.find(`[name="${k}"]`);
					if (!field.length) return;
					const dropdown = field.closest(".ui.dropdown");
					if (dropdown.length) dropdown.dropdown("set selected", String(data[k] ?? ""));
					else field.val(data[k]);
				});

				form.find("input,textarea,select").first().trigger("change");
			},
		});
	}
}
