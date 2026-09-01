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
		if (!form.find('.ui.error.message').length) form.append('<div class="ui error message" role="alert"></div>');
		if (!form.find('.ui.success.message').length) form.append('<div class="ui success message" role="status"></div>');
		return form;
	}

	static error(form, response = {}) {
		form = this.ensure(form).removeClass('success').addClass('error');
		if (!form.length) return;
		form.find('.ui.success.message').hide().empty();
		form.find('.field').removeClass('error');
		const messages = [], errors = response.errors || {};
		if (Array.isArray(errors)) errors.forEach(value => value && messages.push(String(value)));
		else Object.entries(errors).forEach(([name, values]) => {
			(Array.isArray(values) ? values : [values]).forEach(value => value && messages.push(String(value)));
			form.find(`[name="${CSS.escape(name)}"]`).closest('.field').addClass('error');
		});
		if (!messages.length) messages.push(response.message || 'Data tidak dapat diproses. Periksa kembali isian formulir.');
		const escape = value => $('<div>').text(value).html();
		form.find('.ui.error.message').html(`<div class="header">Validasi gagal</div><ul class="list">${messages.map(message => `<li>${escape(message)}</li>`).join('')}</ul>`).show();
	}

	static success(form, message = 'Data berhasil disimpan') {
		form = this.ensure(form).removeClass('error').addClass('success');
		if (!form.length) return;
		form.find('.field').removeClass('error');
		form.find('.ui.error.message').hide().empty();
		form.find('.ui.success.message').html(`<div class="header"><i class="check circle icon"></i> Berhasil</div><p>${$('<div>').text(message).html()}</p>`).show();
	}
}

window.FormFeedback = FormFeedback;

// Simpan formulir pemicu terakhir; AjaxEngine memakai ini untuk CRUD khusus.
document.addEventListener('submit', event => {
	if (event.target?.matches?.('form.ui.form')) {
		window.__lastSubmittedForm = event.target;
		window.__lastSubmittedFormAt = Date.now();
	}
}, true);

$(function () {
	$('form.ui.form').each(function () { FormFeedback.ensure(this); });
});
