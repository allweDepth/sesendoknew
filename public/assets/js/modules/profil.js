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

	bindSubmit() {
		const form = $('form[name="profil"]');
		form.off("submit.profile").on("submit.profile", (e) => {
			e.preventDefault();
			if (window.FormValidation && !FormValidation.validate(form)) return;
			const button = form.find('button[type="submit"], button:not([type])').last();
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
				},
				complete: () => button.removeClass("loading disabled").prop("disabled", false),
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
				const preview = URL.createObjectURL(file);
				$("#preview_photo").attr("src", preview);
				const data = new FormData();
				data.append("photo", file);
				this.ajax.request({
					url: "/profil/upload-photo",
					method: "POST",
					data,
					processData: false,
					contentType: false,
					beforeSend: () => $("#chooseProfilePhoto").addClass("loading"),
					success: (res) => {
						const finalUrl = `${res.data.url}${res.data.url.includes("?") ? "&" : "?"}v=${Date.now()}`;
						$("#preview_photo").attr("src", finalUrl);
						$("img[data-user-avatar]").attr("src", finalUrl);
						URL.revokeObjectURL(preview);
					},
					error: () => URL.revokeObjectURL(preview),
					complete: () => {
						$("#chooseProfilePhoto").removeClass("loading");
						e.target.value = "";
					},
				});
			});
	}

	bindPeriodSelector() {
		this.ajax.request({
			url: "/profil/periods",
			method: "GET",
			success: (res) => {
				const data = res.data || {},
					period = $("#planningPeriod"),
					year = $("#budgetYear");
				period.empty().append('<option value="">Pilih rentang</option>');
				(data.periods || []).forEach((p) =>
					period.append(
						`<option value="${p.id}" data-start="${p.periode_mulai}" data-end="${p.periode_selesai}">${data.scope} ${p.periode_mulai}–${p.periode_selesai}${p.keterangan ? " · " + p.keterangan : ""}</option>`,
					),
				);
				period.dropdown("refresh");
				const selected = (data.periods || []).find(
					(p) =>
						Number(data.selected_year) >= Number(p.periode_mulai) &&
						Number(data.selected_year) <= Number(p.periode_selesai),
				);
				if (selected) period.dropdown("set selected", String(selected.id));
				const fill = () => {
					const option = period.find("option:selected"),
						start = Number(option.data("start")),
						end = Number(option.data("end"));
					year.empty().append('<option value="">Pilih tahun</option>');
					if (start && end) for (let y = start; y <= end; y++) year.append(`<option value="${y}">${y}</option>`);
					year.dropdown("refresh");
					if (data.selected_year >= start && data.selected_year <= end)
						year.dropdown("set selected", String(data.selected_year));
				};
				period.off("change.period").on("change.period", fill);
				fill();
				year.off("change.period").on("change.period", () => {
					if (!period.val() || !year.val()) return;
					this.ajax.request({
						url: "/profil/select-period",
						method: "POST",
						data: { periode_id: period.val(), tahun: year.val() },
						success: () => {
							$("#card_tahun").text(year.val());
							$(".dash_header .label").text(year.val());
						},
					});
				});
			},
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
					const field = form.find(`[name="${k}"]`);
					if (!field.length) return;
					const dropdown = field.closest(".ui.dropdown");
					if (dropdown.length) dropdown.dropdown("set selected", String(data[k] ?? ""));
					else field.val(data[k]);
				});

				form.trigger("change");
			},
		});
	}
}
