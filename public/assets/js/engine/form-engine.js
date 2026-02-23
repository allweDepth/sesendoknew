/* =========================================================
	 FORM ENGINE PRO - FIELD VARIATIONS (FOMANTIC STYLE) @note FormEngine
========================================================= */

class FormEngine {
	/* ============================
		 Render Form ke Container
	============================ */
	static render(container, elements = []) {
		let html = this.build(elements);
		$(container).html(html);
		this.init();
	}
	/* ============================
		 Build All Elements
	============================ */
	static build(elements = []) {
		let html = "";
		elements.forEach((el) => {
			if (el.prop?.role) {
				if (!el.prop.role.includes(AppState.role)) return;
			}
			html += this.element(el);
		});

		return html;
	}

	/* ============================
		 Build Single Element
	============================ */
	static element(el) {
		const { tag, prop = {} } = el;

		switch (tag) {
			case "fieldAction":
				return this.fieldWrapper(this.inputAction(prop), prop);
			/* ===== SINGLE FIELD ===== */
			case "fieldCalendar":
				return this.fieldWrapper(this.calendar(prop), prop);
			case "cardProfile":
				return this.cardProfile(prop);

			case "field":
				return this.fieldWrapper(this.input(prop), prop);

			case "fieldTextarea":
				return this.fieldWrapper(
					`<textarea name="${prop.name}" ${prop.atribut || ""}></textarea>`,
					prop,
				);

			case "fieldDropdown":
				return this.fieldWrapper(this.dropdown(prop), prop);

			case "fieldCheckbox":
				return `
					<div class="field ${prop.classField || ""}">
						<div class="ui checkbox">
							<input type="checkbox" name="${prop.name}" ${prop.atribut || ""}>
							<label>${prop.label || ""}</label>
						</div>
					</div>
				`;
			case "fieldFile":
				return this.fieldWrapper(
					`
        <div class="ui file input">
            <input type="file"
                   name="${prop.name}"
                   accept="${prop.accept || ""}">
        </div>
    `,
					prop,
				);

			/* ===== GROUP FIELDS ===== */
			case "fields":
				return `
					<div class="fields ${prop.classGroup || ""}">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== INLINE FIELDS ===== */
			case "inlineFields":
				return `
					<div class="inline fields">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== DIVIDER ===== */
			case "divider":
				return `
					<h4 class="ui dividing header">
						${prop.label || ""}
					</h4>
				`;

			default:
				return "";
		}
	}
	//Kalender
	static calendar(prop) {
		let attrs = "";

		if (prop.atribut) {
			attrs += prop.atribut;
		}

		return `
    <div class="ui calendar" ${attrs}>
        <div class="ui input left icon ${prop.classInput || ""}">
            <i class="calendar icon"></i>
            <input 
                type="text"
                name="${prop.name}"
                data-type="${prop.calendarType || "date"}"
                placeholder="${prop.placeholder || "Pilih Tanggal"}"
                ${prop.readonly ? "readonly" : ""}
            >
        </div>
    </div>
    `;
	}
	//CARD
	static cardProfile(prop) {
		let defaultImage = AppConfig.baseUrl + "assets/img/avatar/default.jpeg";

		let imagePath = prop?.image?.trim() ? prop.image : defaultImage;

		return `
	<div class="ui special fluid card">

		<div class="content">
			<div class="right floated meta">${prop.meta || ""}</div>
			<img class="ui avatar image"
			     src="${defaultImage}"
			     onerror="imgsrc(this)">
			${prop.title || ""}
		</div>

		<div class="blurring dimmable image">
			<div class="ui dimmer">
				<div class="content">
					<div class="center">
						<button class="ui inverted icon button"
							name="direct"
							type="button"
							jns="upload"
							tbl="${prop.table}"
							id_row="${prop.id_row || ""}"
							dok="${prop.dokumen}"
							accept="${prop.accept}">
							<i class="file icon"></i>
							Upload File
						</button>
					</div>
				</div>
			</div>
			<img src="${defaultImage}" onerror="imgsrc(this)">
		</div>

		<div class="content">
			<span class="right floated">
				<i class="heart outline like icon"></i> likes
			</span>
			<i class="comment icon"></i> comments
		</div>

	</div>
	`;
	}
	/* ============================
		 Field Wrapper
	============================ */
	static fieldWrapper(inner, prop) {
		return `
			<div class="field 
				${prop.classField || ""} 
				${prop.width || ""}">
				
				${prop.label ? `<label>${prop.label}</label>` : ""}
				${inner}
			</div>
		`;
	}

	/* ============================
		 Basic Input
	============================ */
	static input(prop) {
		// icon input
		if (prop.icon) {
			return `
				<div class="ui icon input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<i class="${prop.icon} icon"></i>
				</div>
			`;
		}

		// labeled input
		if (prop.labelInput) {
			return `
				<div class="ui labeled input ${prop.classInput || ""}">
					<div class="ui label">${prop.labelInput}</div>
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
				</div>
			`;
		}

		// action input
		if (prop.action) {
			return `
				<div class="ui action input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<button class="ui button">${prop.action}</button>
				</div>
			`;
		}

		// default input
		return `
			<input type="${prop.type || "text"}"
				   name="${prop.name}"
				   ${prop.atribut || ""}>
		`;
	}
	static inputAction(prop) {
		return `
        <div class="ui action input ${prop.classInput || ""}">
            <input type="text"
                   name="${prop.name}"
                   placeholder="${prop.placeholder || ""}">
            <button class="ui ${prop.button?.class || ""} icon button"
                ${Object.entries(prop.button?.attr || {})
									.map(([k, v]) => `${k}="${v}"`)
									.join(" ")}>
                <i class="${prop.button?.icon || "search"} icon"></i>
            </button>
        </div>
    `;
	}
	/* ============================
		 Dropdown
	============================ */
	static dropdown(prop) {
		let staticOptions = "";

		if (prop.options && prop.options.length) { // @audit
			prop.options.forEach((opt) => {
				staticOptions += `
				<div class="item${prop.class || ""}" data-value="${opt.value}">
					${opt.text}
				</div>
			`;
			});
		}

		// ===============================
		// BUILD CLASS DINAMIS
		// ===============================
		let classes = "ui";

		if (prop.search) classes += " search";
		if (prop.multiple) classes += " multiple";
		if (prop.clearable) classes += " clearable";
		if (prop.disabled) classes += " disabled";

		classes += " selection dropdown";

		return `
		<div class="${classes} ${prop.classInput || ""}"
			data-source="${prop.source || ""}"
			data-parent="${prop.parent || ""}">
			 
			<input type="hidden" name="${prop.name}">
			<i class="dropdown icon"></i>
			<div class="default text">${prop.placeholder || "Pilih"}</div>
			<div class="menu">
				${staticOptions}
			</div>
		</div>
	`;
	}

	/* ============================
		 Init Fomantic Components
	============================ */
	static init() {
		// ==============================
		// DROPDOWN
		// ==============================
		$(".ui.dropdown").dropdown();

		// ==============================
		// CHECKBOX
		// ==============================
		$(".ui.checkbox").checkbox();

		// ==============================
		// DIMMER
		// ==============================
		$(".ui.card .image").dimmer({
			on: "hover",
		});

		// ==============================
		// CALENDAR DEFAULT INIT
		// ==============================
		$(".ui.calendar").each(function () {
			let $calendar = $(this);
			let type = $calendar.find("input").attr("data-type");

			$calendar.calendar({
				type: type || "date",
				formatter: {
					date: function (date) {
						if (!date) return "";

						// jika type year → tampilkan tahun saja
						if (type === "year") {
							return date.getFullYear();
						}

						return date.toLocaleDateString();
					},
				},
			});
		});

		// ==============================
		// RANGE CALENDAR AUTO LINK
		// ==============================
		let groups = {};

		$(".ui.calendar[data-group]").each(function () {
			let $calendar = $(this);
			let group = $calendar.data("group");
			let range = $calendar.data("range"); // start / end

			if (!groups[group]) {
				groups[group] = {};
			}

			groups[group][range] = $calendar;
		});

		Object.keys(groups).forEach((group) => {
			let start = groups[group]["start"];
			let end = groups[group]["end"];

			if (start && end) {
				let startType = start.find("input").data("type") || "date";
				let endType = end.find("input").data("type") || "date";

				start.calendar("destroy");
				end.calendar("destroy");

				start.calendar({
					type: startType,
					endCalendar: end,
					formatter: {
						date: function (date) {
							if (!date) return "";
							return startType === "year"
								? date.getFullYear()
								: date.toLocaleDateString();
						},
					},
				});

				end.calendar({
					type: endType,
					startCalendar: start,
					formatter: {
						date: function (date) {
							if (!date) return "";
							return endType === "year"
								? date.getFullYear()
								: date.toLocaleDateString();
						},
					},
				});
			}
		});
	}
}