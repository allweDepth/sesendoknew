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

	static reset(form) {
		form = $(form);
		form.removeClass("error success").removeData("fieldFeedbackErrors").removeData("formFeedbackGeneral");
		form.find(".field").removeClass("error");
		form.find("[aria-invalid]").removeAttr("aria-invalid");
		form.find(".prompt.label").remove();
		form.find("[data-feedback-describedby]").each(function () {
			const input = $(this), prompt = input.attr("data-feedback-describedby");
			const ids = (input.attr("aria-describedby") || "").split(/\s+/).filter(id => id && id !== prompt);
			if (ids.length) input.attr("aria-describedby", ids.join(" ")); else input.removeAttr("aria-describedby");
			input.removeAttr("data-feedback-describedby");
		});
		form.find(".form-feedback-zone .message").hide().empty();
		return form;
	}

	static renderField(form, name, messages) {
		const inputs = form.find("[name]").filter(function () { return this.name === name; });
		const fields = inputs.closest(".field").addClass("error");
		inputs.attr("aria-invalid", "true");
		fields.each(function () {
			const field = $(this);
			field.children(".prompt.label").remove();
			const prompt = $('<div class="ui basic red pointing prompt label" role="alert"></div>');
			const id = `field-error-${++FormFeedback.promptId}`;
			prompt.attr("id", id).text(messages.join(" ")).appendTo(field);
			inputs.each(function () {
				const input = $(this), ids = new Set((input.attr("aria-describedby") || "").split(/\s+/).filter(Boolean));
				ids.add(id);
				input.attr("aria-describedby", [...ids].join(" ")).attr("data-feedback-describedby", id);
			});
		});
	}

	static summary(form) {
		const errors = form.data("fieldFeedbackErrors") || {};
		const messages = [...Object.values(errors).flat(), ...(form.data("formFeedbackGeneral") || [])];
		const box = form.children(".form-feedback-zone").find(".ui.error.message").empty();
		if (!messages.length) {
			if (!form.find(".field.error").length) form.removeClass("error");
			box.hide(); return;
		}
		form.addClass("error");
		$('<div class="header">Periksa kembali isian formulir</div>').appendTo(box);
		const list = $('<ul class="list"></ul>').appendTo(box);
		messages.forEach(message => $('<li>').text(message).appendTo(list));
		box.show();
	}

	static error(form, response = {}) {
		form = this.reset(this.ensure(form)).addClass("error");
		if (!form.length) return;
		const errors = {}, general = [], source = response.errors || {};
		if (Array.isArray(source)) general.push(...source.filter(Boolean).map(String));
		else Object.entries(source).forEach(([name, values]) => {
			const messages = (Array.isArray(values) ? values : [values]).filter(Boolean).map(String);
			if (!messages.length) return;
			errors[name] = messages;
			this.renderField(form, name, messages);
		});
		if (!Object.keys(errors).length && !general.length)
			general.push(response.message || "Data tidak dapat diproses. Silakan coba kembali.");
		form.data("fieldFeedbackErrors", errors).data("formFeedbackGeneral", general);
		this.summary(form);
	}

	static clearField(input) {
		input = $(input);
		const form = input.closest("form.ui.form"), name = input.attr("name");
		const errors = form.data("fieldFeedbackErrors");
		if (!errors || !Object.hasOwn(errors, name)) return;
		const current = FormValidation.collect(form)[name];
		if (current?.length) return;
		const field = input.closest(".field");
		field.removeClass("error").children(".prompt.label").remove();
		form.find("[name]").filter(function () { return this.name === name; }).each(function () {
			const item = $(this), prompt = item.attr("data-feedback-describedby");
			const ids = (item.attr("aria-describedby") || "").split(/\s+/).filter(id => id && id !== prompt);
			item.removeAttr("aria-invalid data-feedback-describedby");
			if (ids.length) item.attr("aria-describedby", ids.join(" ")); else item.removeAttr("aria-describedby");
		});
		delete errors[name];
		this.summary(form);
	}

	static success(form, message = "Data berhasil disimpan") {
		form = this.reset(this.ensure(form)).addClass("success");
		if (!form.length) return;
		const box = form.children(".form-feedback-zone").find(".ui.success.message").empty();
		$('<div class="header"><i class="check circle icon"></i> Berhasil</div>').appendTo(box);
		$('<p>').text(message).appendTo(box);
		box.show();
	}

}

FormFeedback.promptId = 0;
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
			if (!name || seen.has(name) || input.prop("disabled") || (input.attr("type") === "hidden" && !input.closest(".ui.dropdown").length)) return;
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
		FormFeedback.reset(form);
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

// Delegation also covers forms created later inside sidebars and modals.
$(document).on("input.formFeedback change.formFeedback", "form.ui.form [name]", function () {
	FormFeedback.clearField(this);
});
