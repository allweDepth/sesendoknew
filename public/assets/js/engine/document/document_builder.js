class DocumentBuilder {

    constructor(container, type) {
        this.container = container;
        this.type = type;
        this.schema = DocumentSchema[type];
    }

    render() {

        if (!this.schema) {
            this.container.append(`
                <div class="ui warning message">
                    Schema tidak ditemukan untuk tipe ini
                </div>
            `);
            return;
        }

        this.schema.sections.forEach(section => {

            this.container.append(`
                <h4 class="ui horizontal divider header">
                    ${section.label}
                </h4>
            `);

            this.renderSection(section);

        });

        this.bindEvents();
    }

    renderSection(section) {

        switch(section.type) {

            case "list":
                this.container.append(`
                    <div class="ui fluid action input section-${section.key}">
                        <input type="text" placeholder="Uraian">
                        <button class="ui green icon button btn-add"
                                data-section="${section.key}">
                            <i class="plus icon"></i>
                        </button>
                    </div>
                    <div class="list-${section.key}"></div>
                `);
                break;

            case "numbered":
                this.container.append(`
                    <div class="list-${section.key}"></div>
                    <button class="ui blue mini button btn-add-numbered"
                            data-section="${section.key}">
                        + Tambah
                    </button>
                `);
                break;

            case "textarea":
                this.container.append(`
                    <div class="field">
                        <textarea name="${section.key}" rows="6"></textarea>
                    </div>
                `);
                break;

            case "table":
                this.container.append(`
                    <div class="ui message">
                        (Engine Table akan dibuat berikutnya)
                    </div>
                `);
                break;
        }
    }

    bindEvents() {

        const self = this;

        // ===============================
        // LIST TYPE
        // ===============================
        this.container.on("click", ".btn-add", function(){

            let key = $(this).data("section");
            let input = self.container.find(`.section-${key} input`);
            let value = input.val();

            if(!value) return;

            self.container.find(`.list-${key}`).append(`
                <div class="ui segment small item">
                    <input type="hidden" name="${key}[]" value="${value}">
                    ${value}
                </div>
            `);

            input.val("");
        });

        // ===============================
        // NUMBERED TYPE
        // ===============================
        this.container.on("click", ".btn-add-numbered", function(){

            let key = $(this).data("section");
            let index = self.container.find(`.list-${key} .item`).length + 1;

            self.container.find(`.list-${key}`).append(`
                <div class="ui segment small item">
                    <label>${index}.</label>
                    <textarea name="${key}[]" rows="2"></textarea>
                </div>
            `);
        });

    }
}