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
		let items = options
			.map((o) => `<div class="item" data-value="${o.value}">${o.text}</div>`)
			.join("");

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

	static rangeCalendar(nameStart, nameEnd, label, calendarType = "datetime") {
		return `
    <div data-calendar-type="${calendarType}"
         data-range-start="${nameStart}"
         data-range-end="${nameEnd}">

        <div class="field">
            <label>${label}</label>

            <div class="two fields">

                <div class="field">
                    <div class="ui calendar start_${nameStart}">
                        <div class="ui input left icon">
                            <i class="calendar icon"></i>
                            <input type="text" name="${nameStart}">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="ui calendar end_${nameEnd}">
                        <div class="ui input left icon">
                            <i class="calendar icon"></i>
                            <input type="text" name="${nameEnd}">
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
		$(".ui.dropdown").dropdown();
		$(".ui.checkbox").checkbox();

		$(".ui.calendar").calendar();
	}

	static initRange(elements = []) {
		elements.forEach((e) => {
			const nameStart = e.prop.nameStart;
			const nameEnd = e.prop.nameEnd;
			const type = e.prop.calendarType || "datetime";

			const start = $(`.start_${nameStart}`);
			const end = $(`.end_${nameEnd}`);

			start.calendar({
				type: type,
				endCalendar: end,
			});

			end.calendar({
				type: type,
				startCalendar: start,
			});
		});
	}
}
// REGISTER ALL COMPONENTS

UIComponentRegistry.register("input", (p) =>
	UIComponents.input(p.label, p.name, p.type || "text"),
);

UIComponentRegistry.register("textarea", (p) =>
	UIComponents.textarea(p.label, p.name),
);

UIComponentRegistry.register("toggle", (p) =>
	UIComponents.toggle(p.label, p.name),
);

UIComponentRegistry.register("calendar", (p) =>
	UIComponents.calendar(p.label, p.name),
);

UIComponentRegistry.register("rangeCalendar", (p) =>
	UIComponents.rangeCalendar(
		p.nameStart,
		p.nameEnd,
		p.label,
		p.calendarType || "datetime",
	),
);

UIComponentRegistry.register("dropdown", (p) =>
	UIComponents.dropdown(p.label, p.name, p.options || []),
);
