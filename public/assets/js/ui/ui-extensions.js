class UIExtensions {
	// =========================================
	// FORMAT CURRENCY
	// =========================================
	static currency(selector) {
		$(document).on("input", selector, function () {
			let value = this.value.replace(/\D/g, "");

			this.value = new Intl.NumberFormat("id-ID").format(value);
		});
	}

	// =========================================
	// RENDER AUTO NOMOR FIELD
	// =========================================
	static renderAutoNumber(opts) {
		return `
        <div class="field">
            <label>${opts.label}</label>
            <input type="text"
                   name="${opts.name}"
                   value="${opts.value || ""}"
                   readonly>
        </div>
        `;
	}
}

window.UIExtensions = UIExtensions;
