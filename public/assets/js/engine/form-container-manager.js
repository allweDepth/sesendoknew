/**
 * ============================================================
 * FORM CONTAINER MANAGER
 * ============================================================
 * Bertugas:
 * - Menampilkan / menyembunyikan form
 * - Inject form ke container
 * - Tidak memproses submit
 */

class FormContainerManager {

    constructor(config = {}) {

        this.container = config.container;
        this.isVisible = false;
    }

    /**
     * INIT
     */
    init() {

        this.bindEvents();
    }

    /**
     * BIND EVENT SUKSES FORM
     */
    bindEvents() {

        $(document).on("form:success", () => {

            this.hide();

        });

    }

    /**
     * SHOW CONTAINER
     */
    show(html = "") {

        $(this.container).html(html);
        $(this.container).fadeIn();
        this.isVisible = true;
    }

    /**
     * HIDE CONTAINER
     */
    hide() {

        $(this.container).fadeOut();
        this.isVisible = false;
    }

    /**
     * DESTROY
     */
    destroy() {

        $(document).off("form:success");

        this.isVisible = false;
    }

}