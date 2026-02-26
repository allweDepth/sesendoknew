/**
 * ============================================================
 * FORM ENGINE
 * ============================================================
 * Bertanggung jawab:
 * - Load data edit
 * - Submit form
 * - Tidak mengatur container
 */

class FormEngine {

    constructor(config = {}) {

        this.state = config.state;
        this.ajax = config.ajax;
        this.formSelector = config.formSelector;

        this.isInitialized = false;
    }

    /**
     * INIT FORM
     */
    init() {

        this.isInitialized = true;

        this.bindEvents();

    }

    

    /**
     * LOAD DATA EDIT
     */
    loadData(id) {

        this.ajax.request({

            data: {
                module: this.state.module,
                action: "edit",
                tbl: this.state.tbl,
                id_row: id
            },

            success: (res) => {

                this.populateForm(res.data);

            }

        });

    }

    /**
     * ISI FORM DENGAN DATA
     */
    populateForm(data) {

        Object.keys(data).forEach(key => {

            const field = $(`${this.formSelector} [name="${key}"]`);

            switch (field.length > 0) {

                case true:
                    field.val(data[key]);
                    break;

                default:
                    break;
            }

        });

    }

    /**
     * BIND SUBMIT
     */
    bindEvents() {

        $(document).on("submit", this.formSelector, (e) => {

            e.preventDefault();

            this.submit();

        });

    }

    /**
     * SUBMIT FORM
     */
    submit() {

        const formData = $(this.formSelector).serialize();

        this.ajax.request({

            data: formData,

            success: () => {

                $(document).trigger("form:success");

            }

        });

    }

    /**
     * DESTROY
     */
    destroy() {

        $(document).off("submit", this.formSelector);
        $(document).off("table:edit");

        this.isInitialized = false;
    }

}