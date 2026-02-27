class ValidationEngine {

    static validate(formSelector, schema = {}) {

        let valid = true;

        Object.keys(schema).forEach(name => {

            const rules = schema[name];
            const field = $(`${formSelector} [name="${name}"]`);
            const value = field.val();

            field.closest('.field').removeClass('error');

            if (rules.required && !value) {
                field.closest('.field').addClass('error');
                valid = false;
            }

            if (rules.minLength && value.length < rules.minLength) {
                field.closest('.field').addClass('error');
                valid = false;
            }

            if (rules.pattern && !rules.pattern.test(value)) {
                field.closest('.field').addClass('error');
                valid = false;
            }

        });

        return valid;
    }
}

window.ValidationEngine = ValidationEngine;