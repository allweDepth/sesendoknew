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
	// UI MASSAGE
	// ==================================================
	static message(prop = {}) {
		const {
			label = "",
			header = "",
			html = "",
			type = "info", // info | warning | error | success
			icon = null, // ex: "info circle"
			size = "", // small | large
			compact = false,
			floating = false,
			shadow = false,
			list = [], // array of string
			dismissible = false,
			subtle = false, // soft style
			className = "",
		} = prop;

		const iconHtml = icon ? `<i class="${icon} icon"></i>` : "";

		const headerHtml = header ? `<div class="header">${header}</div>` : "";

		const listHtml = list.length
			? `<ul class="list">
            ${list.map((item) => `<li>${item}</li>`).join("")}
           </ul>`
			: "";

		const subtleClass = subtle ? "basic" : "";
		const compactClass = compact ? "compact" : "";
		const floatingClass = floating ? "floating" : "";
		const shadowStyle = shadow
			? "style='box-shadow:0 4px 12px rgba(0,0,0,0.08);'"
			: "";

		const dismissBtn = dismissible
			? `<i class="close icon message-close"></i>`
			: "";

		return `
        <div class="field ${className}">
            ${label ? `<label>${label}</label>` : ""}

            <div class="ui ${size} ${type} ${subtleClass} 
                        ${compactClass} ${floatingClass} 
                        message"
                 ${shadowStyle}>

                ${dismissBtn}
                ${iconHtml}

                <div class="content">
                    ${headerHtml}
                    ${html}
                    ${listHtml}
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
		/* =========================================
	INIT SEMUA DROPDOWN FOMANTIC
	========================================= */

		$(".ui.dropdown").dropdown();

		/* =========================================
	INIT CHECKBOX FOMANTIC
	========================================= */

		$(".ui.checkbox").checkbox();

		/* =========================================
	INIT SEARCH MODULE
	komponen:
	- tag: search
	class: ds-search
	========================================= */

		UIComponents.initSearch();

		/* =========================================
	INIT LOOKUP DROPDOWN SERVER
	komponen:
	- tag: lookupDropdown
	class: lookup-dropdown
	========================================= */

		UIComponents.initLookupDropdown();
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
	// ==================================================
	// ENTERPRISE ALERT
	// ==================================================
	static alert(prop = {}) {
		const { variant = "info", title = "", message = "", icon = null } = prop;

		return `
        <div class="field">
            <div class="ui ${variant} message">
                ${icon ? `<i class="${icon} icon"></i>` : ""}
                <div class="content">
                    ${title ? `<div class="header">${title}</div>` : ""}
                    ${message}
                </div>
            </div>
        </div>
    `;
	}
	// ==================================================
	// ENTERPRISE PROGRESS
	// ==================================================
	static progress(prop = {}) {
		const {
			percent = 0,
			label = "Processing...",
			size = "small",
			color = "blue",
		} = prop;

		return `
        <div class="ui ${size} ${color} progress ds-progress" data-percent="${percent}">
            <div class="bar" style="width:${percent}%"></div>
            <div class="label">${label}</div>
        </div>
    `;
	}
	// ======================================================
	// ALIAS FIELD (AGAR COMPATIBLE DENGAN UI CONFIG)
	// ======================================================

	static field(label, name, type = "text") {
		// gunakan input yang sudah ada
		return UIComponents.input(label, name, type);
	}

	static fieldTextarea(label, name) {
		// gunakan textarea yang sudah ada
		return UIComponents.textarea(label, name);
	}

	static fieldDropdown(label, name, options = []) {
		// gunakan dropdown yang sudah ada
		return UIComponents.dropdown(label, name, options);
	}
	// ======================================================
	// DROPDOWN SEARCH (FOMANTIC UI)
	// ======================================================

	static searchDropdown(label, name) {
		/* =====================================================
	COMPONENT DROPDOWN SEARCH
	===================================================== */

		return `
	<div class="field">

		<label>${label}</label>

		<div class="ui fluid search selection dropdown">

			<input type="hidden" name="${name}">

			<i class="dropdown icon"></i>

			<div class="default text">Cari ${label}</div>

			<div class="menu"></div>

		</div>

	</div>
	`;
	}
	// ======================================================
	// FOMANTIC SEARCH MODULE
	// ======================================================

	static search(label, name, source) {
		return `
	<div class="field">

		<label>${label}</label>

		<div class="ui search ds-search"
		     data-source="${source}"
		     data-name="${name}">

			<div class="ui icon input">

				<input class="prompt" type="text" placeholder="Cari...">

				<i class="search icon"></i>

			</div>

			<input type="hidden" name="${name}">

			<div class="results"></div>

		</div>

	</div>
	`;
	}
	// ======================================================
	// INIT FOMANTIC SEARCH
	// ======================================================

	static initSearch() {
		$(".ds-search").each(function () {
			const el = $(this);

			const source = el.data("source");

			const name = el.data("name");

			el.search({
				apiSettings: {
					method: "POST",

					url: "/dynamic",

					beforeSend(settings) {
						settings.data = {
							action: "dropdown",

							tbl: source,

							cari: settings.urlData.query,

							limit: $("#countRow").val() || 20,

							req: window.app?.state?.req || null,
						};

						return settings;
					},

					onResponse(res) {
						return {
							results: res.data.map((row) => ({
								title: row.text || row.uraian,

								value: row.value || row.id,
							})),
						};
					},
				},

				onSelect(result) {
					el.find(`input[name="${name}"]`).val(result.value);

					const form = el.closest("form");

					form.find('[name="uraian_label"]').val(result.title);
				},
			});
		});
	}

	/* =========================================================
LOOKUP DROPDOWN (SERVER REQUEST)
---------------------------------------------------------
Digunakan untuk lookup database besar

contoh:
- akun
- master_biaya
- rekanan
- asn
========================================================= */

	static lookupDropdown(label, name, source) {
		const html = `
    
    <div class="field">

        <label>${label}</label>

        <div class="ui fluid search selection dropdown lookup-dropdown"
             data-source="${source}">

            <input type="hidden" name="${name}">

            <i class="dropdown icon"></i>

            <div class="default text">Cari ${label}</div>

        </div>

    </div>

    `;

		return html;
	}
	/* =========================================================
INIT LOOKUP DROPDOWN
========================================================= */

	static initLookupDropdown() {
		$(".lookup-dropdown").dropdown({
			minCharacters: 2,

			apiSettings: {
				method: "POST",

				url: "/dynamic",

				beforeSend(settings) {
					const source = $(settings.element).data("source");

					settings.data = {
						action: "dropdown",

						tbl: source,

						cari: settings.urlData.query,

						limit: $("#countRow").val() || 20,

						req: window.app?.state?.req || null,
					};

					return settings;
				},

				onResponse(res) {
					if (!res.success) return { success: false, results: [] };

					return {
						success: true,

						results: res.data.map((row) => ({
							name: row.text || row.uraian,

							value: row.value || row.id,
						})),
					};
				},
			},
		});
	}
}
// REGISTER ALL COMPONENTS
UIComponentRegistry.register("search", (p) =>
	UIComponents.search(p.label, p.name, p.source),
);
UIComponentRegistry.register("fieldMessage", (p) => UIComponents.message(p));
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
// ======================================================
// REGISTER ENTERPRISE COMPONENTS
// ======================================================

UIComponentRegistry.register("alert", (p) => UIComponents.alert(p));

UIComponentRegistry.register("progress", (p) => UIComponents.progress(p));
