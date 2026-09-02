/**
 * ============================================================
 * VALIDATION ENGINE
 * ============================================================
 * - Required
 * - Min Length
 * - Pattern
 * - Bisa dikembangkan ke custom rule
 */

class ValidationEngine {
	/**
	 * Validasi berdasarkan schema
	 * @param {string} formSelector - selector form
	 * @param {object} schema - schema validation dari UIConfig
	 */
	static validate(formSelector, schema = {}) {
		let isValid = true;

		// Loop semua field yang punya aturan
		Object.keys(schema).forEach((fieldName) => {
			const rules = schema[fieldName];

			const input = $(`${formSelector} [name="${fieldName}"]`);

			// ======================================================
			// GUARD: FIELD TIDAK DITEMUKAN
			// ======================================================
			if (!input.length) return;

			// ======================================================
			// HANDLE DROPDOWN & KOMPONEN KHUSUS
			// ======================================================
			let field = input;

			if (input.closest(".ui.dropdown").length) {
				field = input.closest(".ui.dropdown");
			}

			// ======================================================
			// AMBIL CONTAINER FIELD YANG VALID
			// ======================================================
			const fieldContainer = field.closest(".field");

			// ======================================================
			// AMBIL VALUE YANG BENAR
			// ======================================================
			const value = input.val() ?? "";

			// ======================================================
			// CLEAR ERROR
			// ======================================================
			fieldContainer.removeClass("error");

			// REQUIRED
			if (rules.required && !value) {
				fieldContainer.addClass("error"); // FIX
				isValid = false;
			}

			// MIN LENGTH
			if (rules.minLength && value.length < rules.minLength) {
				fieldContainer.addClass("error"); // FIX
				isValid = false;
			}

			// REGEX PATTERN
			if (rules.pattern && !rules.pattern.test(value)) {
				fieldContainer.addClass("error"); // FIX
				isValid = false;
			}
		});

		return isValid;
	}
}

window.ValidationEngine = ValidationEngine;

/**
 * Umpan balik formulir universal untuk CRUD dinamis dan formulir modul khusus.
 * Toast tetap dipakai sebagai notifikasi global, sedangkan pesan ini menetap
 * di dalam formulir agar pengguna mengetahui field/operasi yang bermasalah.
 */
class FormFeedback {
	static ensure(form) {
		form = $(form);
		if (!form.length) return form;
		let zone = form.children(".form-feedback-zone");
		if (!zone.length) {
			zone = $('<div class="form-feedback-zone" aria-live="polite"></div>').prependTo(form);
		}
		if (!zone.find(".ui.error.message").length) zone.append('<div class="ui error message" role="alert"></div>');
		if (!zone.find(".ui.success.message").length) zone.append('<div class="ui success message" role="status"></div>');
		return form;
	}

	static error(form, response = {}) {
		form = this.ensure(form).removeClass("success").addClass("error");
		if (!form.length) return;
		form.find(".ui.success.message").hide().empty();
		form.find(".field").removeClass("error");
		const messages = [],
			errors = response.errors || {};
		if (Array.isArray(errors)) errors.forEach((value) => value && messages.push(String(value)));
		else
			Object.entries(errors).forEach(([name, values]) => {
				(Array.isArray(values) ? values : [values]).forEach((value) => value && messages.push(String(value)));
				form
					.find(`[name="${CSS.escape(name)}"]`)
					.closest(".field")
					.addClass("error");
			});
		if (!messages.length)
			messages.push(response.message || "Data tidak dapat diproses. Periksa kembali isian formulir.");
		const escape = (value) => $("<div>").text(value).html();
		form
			.find(".ui.error.message")
			.html(
				`<div class="header">Validasi gagal</div><ul class="list">${messages.map((message) => `<li>${escape(message)}</li>`).join("")}</ul>`,
			)
			.show();
	}

	static success(form, message = "Data berhasil disimpan") {
		form = this.ensure(form).removeClass("error").addClass("success");
		if (!form.length) return;
		form.find(".field").removeClass("error");
		form.find(".ui.error.message").hide().empty();
		form
			.find(".ui.success.message")
			.html(
				`<div class="header"><i class="check circle icon"></i> Berhasil</div><p>${$("<div>").text(message).html()}</p>`,
			)
			.show();
	}
}

window.FormFeedback = FormFeedback;

/**
 * Validasi Fomantic universal untuk seluruh form CRUD, termasuk form modul
 * khusus yang tidak dibuat oleh FormEngine. Rule diambil dari atribut HTML
 * aktual (required/minlength/maxlength/pattern/type/min/max) dan class
 * `.required.field`, sehingga tidak membutuhkan konfigurasi duplikat.
 */
class FormValidation {
	static fieldLabel(input) {
		const field = $(input).closest(".field");
		const label = field.find("label").first().clone().children().remove().end().text().trim();
		return (
			label ||
			String($(input).attr("name") || "Field")
				.replace(/_/g, " ")
				.replace(/\b\w/g, (c) => c.toUpperCase())
		);
	}

	static value(input) {
		const el = $(input);
		if (el.is(":checkbox,:radio")) {
			const name = el.attr("name");
			return name
				? el
						.closest("form")
						.find(`[name="${CSS.escape(name)}"]:checked`)
						.val() || ""
				: el.prop("checked")
					? el.val()
					: "";
		}
		return String(el.val() ?? "").trim();
	}

	static collect(form) {
		form = $(form);
		const errors = {};
		const seen = new Set();
		form.find("input[name], select[name], textarea[name]").each(function () {
			const input = $(this),
				name = input.attr("name");
			if (!name || seen.has(name) || input.prop("disabled") || input.attr("type") === "hidden") return;
			seen.add(name);
			const field = input.closest(".field");
			const label = FormValidation.fieldLabel(input);
			const value = FormValidation.value(input);
			const required = input.prop("required") || field.hasClass("required");
			const messages = [];

			if (required && value === "") messages.push(`${label} wajib diisi`);
			if (value !== "") {
				const minLength = Number(input.attr("minlength") || 0);
				const maxLength = Number(input.attr("maxlength") || 0);
				if (minLength && value.length < minLength) messages.push(`${label} minimal ${minLength} karakter`);
				if (maxLength && value.length > maxLength) messages.push(`${label} maksimal ${maxLength} karakter`);
				if (input.attr("type") === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
					messages.push(`${label} tidak valid`);
				const pattern = input.attr("pattern");
				if (pattern) {
					try {
						if (!new RegExp(`^(?:${pattern})$`).test(value))
							messages.push(`${label} memiliki format yang tidak sesuai`);
					} catch (_) {}
				}
				if (["number", "range"].includes(input.attr("type"))) {
					const number = Number(value),
						min = input.attr("min"),
						max = input.attr("max");
					if (!Number.isFinite(number)) messages.push(`${label} harus berupa angka`);
					else {
						if (min !== undefined && min !== null && min !== "" && number < Number(min))
							messages.push(`${label} minimal ${min}`);
						if (max !== undefined && max !== null && max !== "" && number > Number(max))
							messages.push(`${label} maksimal ${max}`);
					}
				}
			}
			if (messages.length) errors[name] = messages;
		});
		return errors;
	}

	static validate(form, options = {}) {
		form = $(form);
		if (!form.length) return false;
		window.__lastSubmittedForm = form[0];
		window.__lastSubmittedFormAt = Date.now();
		FormFeedback.ensure(form);
		const errors = this.collect(form);
		if (Object.keys(errors).length) {
			FormFeedback.error(form, { success: false, message: "Periksa kembali isian formulir.", errors });
			const firstMessage = Object.values(errors).flat().find(Boolean) || "Periksa kembali isian formulir.";
			if (window.Toast) Toast.error(`Form belum valid — ${firstMessage}`);
			const first = form.find(".field.error").first();
			if (options.focus !== false && first.length) first[0].scrollIntoView({ behavior: "smooth", block: "center" });
			return false;
		}
		form.removeClass("error");
		form.find(".field").removeClass("error");
		form.find(".ui.error.message").hide().empty();
		return true;
	}
}
window.FormValidation = FormValidation;

// Simpan formulir pemicu terakhir; AjaxEngine memakai ini untuk CRUD khusus.
document.addEventListener(
	"submit",
	(event) => {
		if (event.target?.matches?.("form.ui.form")) {
			window.__lastSubmittedForm = event.target;
			window.__lastSubmittedFormAt = Date.now();
			if (window.FormValidation && !FormValidation.validate(event.target)) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		}
	},
	true,
);

$(function () {
	$("form.ui.form").each(function () {
		FormFeedback.ensure(this);
	});
});
