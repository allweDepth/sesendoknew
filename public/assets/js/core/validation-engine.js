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
        Object.keys(schema).forEach(fieldName => {

            const rules = schema[fieldName];

            const field = $(`${formSelector} [name="${fieldName}"]`);
            const value = field.val();

            // Bersihkan error sebelumnya
            field.closest('.field').removeClass('error');

            // REQUIRED
            if (rules.required && !value) {
                field.closest('.field').addClass('error');
                isValid = false;
            }

            // MIN LENGTH
            if (rules.minLength && value.length < rules.minLength) {
                field.closest('.field').addClass('error');
                isValid = false;
            }

            // REGEX PATTERN
            if (rules.pattern && !rules.pattern.test(value)) {
                field.closest('.field').addClass('error');
                isValid = false;
            }

        });

        return isValid;
    }

}

window.ValidationEngine = ValidationEngine;