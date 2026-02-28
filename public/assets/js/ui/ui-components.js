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
		$(".ui.dropdown").dropdown();
		$(".ui.checkbox").checkbox();

		// $(".ui.calendar").calendar();
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
	// ==================================================
	// GROUP (FIELD WRAPPER)
	// ==================================================
	static group(prop = {}, childrenHTML = "") {
		const { className = "fields" } = prop;

		return `
		<div class="${className}">
			${childrenHTML}
		</div>
	`;
	}
	// ==================================================
	// SECTION DIVIDER
	// ==================================================
	static divider(prop = {}) {
		const { icon = "", text = "" } = prop;

		return `
		<h4 class="ui horizontal divider header inverted">
			${icon ? `<i class="${icon} icon inverted"></i>` : ""}
			${text}
		</h4>
	`;
	}
	// ==================================================
	// FILE INPUT ENTERPRISE
	// ==================================================
	static fileInput(prop = {}) {
		const { label = "", name = "" } = prop;

		return `
		<div class="field">
			<label>${label}</label>
			<div class="ui fluid right action left icon input inverted">
				<i class="folder open yellow icon inverted"></i>
				<input type="text" placeholder="Pilih File..." readonly name_bayang="${name}">
				<input hidden type="file" name="${name}" accept=".jpg,.jpeg,.png,.pdf,.docx">
				<button class="ui red icon button inverted btn-del-file" type="button">
					<i class="erase icon inverted"></i>
				</button>
			</div>
		</div>
	`;
	}
	// ==================================================
	// DOCUMENT TABLE ENTERPRISE
	// ==================================================
	static documentTable(prop = {}) {
		const { name = "", title = null, columns = ["URAIAN", "JENIS"] } = prop;

		const headerColumns = columns.map((col) => `<th>${col}</th>`).join("");

		const titleColumn = title ? `<th>${title}</th>` : headerColumns;

		return `
		<table class="ui stackable celled structured table inverted document-table"
			   data-name="${name}">

			<thead>
				<tr>
					${title ? `<th>${title}</th>` : headerColumns}
					<th class="collapsing">JENIS</th>
					<th class="collapsing">
						<button class="ui green icon mini button inverted btn-add-row"
								data-table="${name}">
							<i class="plus icon inverted"></i>
						</button>
					</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td contenteditable="true"></td>
					<td class="collapsing">
						<select class="ui mini dropdown inverted jenis-selector">
							<option value="p">Paragraf</option>
							<option value="list">List</option>
							<option value="number">Numbered</option>
							<option value="colon">Titik Dua</option>
						</select>
					</td>
					<td class="collapsing">
						<button class="ui icon mini red button inverted btn-del-row">
							<i class="trash icon"></i>
						</button>
					</td>
				</tr>
			</tbody>

		</table>
	`;
	}

	// ==================================================
// DROPDOWN BUTTON (ENTERPRISE)
// ==================================================
static dropdownButton(prop = {}) {

	const {
		icon = "wrench",
		size = "mini",
		variant = "basic",
		inverted = true,
		direction = "upward",
		options = []
	} = prop;

	const invertedClass = inverted ? "inverted" : "";

	const items = options.map(opt => `
		<div class="item"
			 data-value="${opt.value || ""}"
			 ${opt.action ? `data-action="${opt.action}"` : ""}>
			${opt.icon ? `<i class="${opt.icon} icon ${invertedClass}"></i>` : ""}
			${opt.label}
		</div>
	`).join("");

	return `
		<div class="ui icon ${variant} ${size} buttons ${invertedClass}">
			<div class="ui floating dropdown icon button ${invertedClass} ${direction}">
				<i class="${icon} icon ${invertedClass}"></i>
				<div class="menu">
					${items}
				</div>
			</div>
		</div>
	`;
}
}
// REGISTER ALL COMPONENTS
// ======================================================
// REGISTER ENTERPRISE COMPONENTS
// ======================================================

UIComponentRegistry.register("group", (p, childrenHTML) =>
	UIComponents.group(p, childrenHTML),
);

UIComponentRegistry.register("divider", (p) => UIComponents.divider(p));

UIComponentRegistry.register("fileInput", (p) => UIComponents.fileInput(p));

UIComponentRegistry.register("dropdownButton", (p) =>
	UIComponents.dropdownButton(p)
);

UIComponentRegistry.register("documentTable", (p) =>
	UIComponents.documentTable(p),
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
