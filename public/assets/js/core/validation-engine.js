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
