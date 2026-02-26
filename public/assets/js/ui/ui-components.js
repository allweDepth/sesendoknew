// ======================================================
// UI COMPONENT ENGINE (GLOBAL REUSABLE)
// ======================================================

class UIComponents {

    // ==================================================
    // BASIC INPUT
    // ==================================================

    static input(label, name, type = "text") {
        return `
        <div class="field">
            <label>${label}</label>
            <input type="${type}" name="${name}">
        </div>
        `;
    }

    // ==================================================
    // TEXTAREA
    // ==================================================

    static textarea(label, name) {
        return `
        <div class="field">
            <label>${label}</label>
            <textarea name="${name}"></textarea>
        </div>
        `;
    }

    // ==================================================
    // TOGGLE CHECKBOX
    // ==================================================

    static toggle(label, name) {
        return `
        <div class="field">
            <div class="ui toggle checkbox">
                <input type="checkbox" name="${name}">
                <label>${label}</label>
            </div>
        </div>
        `;
    }

    // ==================================================
    // DROPDOWN STATIC
    // ==================================================

    static dropdown(label, name, options = []) {

        let items = options.map(o =>
            `<div class="item" data-value="${o.value}">${o.text}</div>`
        ).join("");

        return `
        <div class="field">
            <label>${label}</label>
            <div class="ui fluid selection dropdown">
                <input type="hidden" name="${name}">
                <i class="dropdown icon"></i>
                <div class="default text">Pilih</div>
                <div class="menu">
                    ${items}
                </div>
            </div>
        </div>
        `;
    }

    // ==================================================
    // SINGLE CALENDAR
    // ==================================================

    static calendar(label, name) {

        return `
        <div class="field">
            <label>${label}</label>
            <div class="ui calendar calendar_${name}">
                <div class="ui input left icon">
                    <i class="calendar icon"></i>
                    <input type="text" name="${name}">
                </div>
            </div>
        </div>
        `;
    }

    // ==================================================
    // RANGE CALENDAR
    // ==================================================

    static rangeCalendar(name, label) {

    return `
    <div class="eight wide computer eight wide tablet sixteen wide mobile column">

        <div class="field">
            <label>${label}</label>

            <div class="two fields">

                <div class="field">
                    <div class="ui calendar start_${name}">
                        <div class="ui input left icon">
                            <i class="calendar icon"></i>
                            <input type="text"
                                   name="awal_${name}">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="ui calendar end_${name}">
                        <div class="ui input left icon">
                            <i class="calendar icon"></i>
                            <input type="text"
                                   name="akhir_${name}">
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
    `;
}

    // ==================================================
    // INIT ALL FOMANTIC
    // ==================================================

    static initAll() {

        $('.ui.dropdown').dropdown();
        $('.ui.checkbox').checkbox();

        $('.ui.calendar').calendar({
            type: "datetime"
        });
    }

    static initRange(names = []) {

        names.forEach(name => {

            const start = $(`.start_${name}`);
            const end   = $(`.end_${name}`);

            start.calendar({
                type: "datetime",
                endCalendar: end
            });

            end.calendar({
                type: "datetime",
                startCalendar: start
            });
        });
    }
}
// REGISTER ALL COMPONENTS

UIComponentRegistry.register("input",
    (p) => UIComponents.input(p.label, p.name, p.type || "text")
);

UIComponentRegistry.register("textarea",
    (p) => UIComponents.textarea(p.label, p.name)
);

UIComponentRegistry.register("toggle",
    (p) => UIComponents.toggle(p.label, p.name)
);

UIComponentRegistry.register("calendar",
    (p) => UIComponents.calendar(p.label, p.name)
);

UIComponentRegistry.register("rangeCalendar",
    (p) => UIComponents.rangeCalendar(p.name, p.label)
);

UIComponentRegistry.register("dropdown",
    (p) => UIComponents.dropdown(p.label, p.name, p.options || [])
);