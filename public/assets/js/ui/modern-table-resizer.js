class ModernTableResizer {
	constructor(root = "#main-content") {
		this.root = document.querySelector(root);
		this.drag = null;
		this.raf = null;
	}

	init() {
		if (!this.root) return;
		this.enhanceAll();
		this.observer = new MutationObserver(() => {
			cancelAnimationFrame(this.raf);
			this.raf = requestAnimationFrame(() => this.enhanceAll());
		});
		this.observer.observe(this.root, { childList: true, subtree: true });
		document.addEventListener("pointermove", event => this.resize(event));
		document.addEventListener("pointerup", () => this.finish());
	}

	enhanceAll() {
		this.root.querySelectorAll("table").forEach((table, index) => {
			if (table.closest(".ui.form, .document-preview, .doc-editor") || !table.tHead || !table.tBodies.length) return;
			table.classList.add("modern-data-table", "resizable-data-table");
			if (!table.dataset.resizeKey) table.dataset.resizeKey = table.dataset.managedTable || table.dataset.table || table.id || `table-${index}`;
			const saved = this.readWidths(table);
			Array.from(table.tHead.rows[table.tHead.rows.length - 1]?.cells || []).forEach((header, columnIndex) => {
				const actionColumn = this.headerText(header) === "aksi";
				header.classList.toggle("table-action-column", actionColumn);
				if (actionColumn) Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => row.cells[columnIndex]?.classList.add("table-action-column")));
				if (!header.querySelector(":scope > .column-resize-handle")) {
					const handle = document.createElement("span");
					handle.className = "column-resize-handle";
					handle.title = "Seret untuk mengubah lebar; klik ganda untuk otomatis";
					handle.addEventListener("pointerdown", event => this.startColumn(event, table, columnIndex));
					handle.addEventListener("dblclick", event => { event.preventDefault(); event.stopPropagation(); this.autoColumn(table, columnIndex); });
					header.appendChild(handle);
				}
				if (saved[columnIndex]) this.setColumnWidth(table, columnIndex, saved[columnIndex]);
			});
			Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => this.enhanceRow(table, row)));
			this.fitActionColumn(table, saved);
		});
	}

	enhanceRow(table, row) {
		if (!row.cells.length || row.querySelector(".row-resize-handle")) return;
		const cell = row.cells[row.cells.length - 1];
		cell.classList.add("row-resize-anchor");
		const handle = document.createElement("span");
		handle.className = "row-resize-handle";
		handle.title = "Seret untuk mengubah tinggi; klik ganda untuk otomatis";
		handle.addEventListener("pointerdown", event => this.startRow(event, row));
		handle.addEventListener("dblclick", event => { event.preventDefault(); event.stopPropagation(); row.style.height = "auto"; });
		cell.appendChild(handle);
	}

	startColumn(event, table, index) {
		event.preventDefault(); event.stopPropagation();
		const header = table.tHead.rows[table.tHead.rows.length - 1].cells[index];
		this.drag = { type: "column", table, index, start: event.clientX, size: header.getBoundingClientRect().width };
		document.body.classList.add("resizing-table-column");
		event.currentTarget.setPointerCapture?.(event.pointerId);
	}

	startRow(event, row) {
		event.preventDefault(); event.stopPropagation();
		this.drag = { type: "row", row, start: event.clientY, size: row.getBoundingClientRect().height };
		document.body.classList.add("resizing-table-row");
		event.currentTarget.setPointerCapture?.(event.pointerId);
	}

	resize(event) {
		if (!this.drag) return;
		if (this.drag.type === "column") this.setColumnWidth(this.drag.table, this.drag.index, Math.max(56, this.drag.size + event.clientX - this.drag.start));
		else this.drag.row.style.height = `${Math.max(34, this.drag.size + event.clientY - this.drag.start)}px`;
	}

	finish() {
		if (!this.drag) return;
		if (this.drag.type === "column") this.saveWidths(this.drag.table);
		this.drag = null;
		document.body.classList.remove("resizing-table-column", "resizing-table-row");
	}

	autoColumn(table, index) {
		const cells = [table.tHead.rows[table.tHead.rows.length - 1].cells[index], ...Array.from(table.tBodies).flatMap(body => Array.from(body.rows).map(row => row.cells[index]).filter(Boolean))];
		const width = Math.min(480, Math.max(56, ...cells.map(cell => {
			const style = getComputedStyle(cell), canvas = this.canvas || (this.canvas = document.createElement("canvas")), context = canvas.getContext("2d");
			context.font = style.font;
			return Math.ceil(context.measureText(cell.innerText.trim()).width) + parseFloat(style.paddingLeft) + parseFloat(style.paddingRight) + 28;
		})));
		this.setColumnWidth(table, index, width);
		this.saveWidths(table);
	}

	setColumnWidth(table, index, width) {
		const value = `${Math.round(width)}px`;
		Array.from(table.rows).forEach(row => { if (row.cells[index]) Object.assign(row.cells[index].style, { width: value, minWidth: value, maxWidth: value }); });
	}

	fitActionColumn(table, saved) {
		const headers = Array.from(table.tHead.rows[table.tHead.rows.length - 1]?.cells || []);
		headers.forEach((header, index) => {
			if (!header.classList.contains("table-action-column") || saved[index]) return;
			const buttons = Array.from(table.tBodies).flatMap(body => Array.from(body.rows).map(row => row.cells[index])).filter(Boolean);
			const width = Math.min(240, Math.max(58, ...buttons.map(cell => cell.scrollWidth + 2)));
			this.setColumnWidth(table, index, width);
		});
	}

	headerText(header) { return Array.from(header.childNodes).filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent).join(" ").trim().toLocaleLowerCase("id"); }
	storageKey(table) { return `sesendok:table-width:${location.pathname}:${table.dataset.resizeKey}`; }
	readWidths(table) { try { return JSON.parse(localStorage.getItem(this.storageKey(table)) || "{}"); } catch (_) { return {}; } }
	saveWidths(table) {
		const widths = {};
		Array.from(table.tHead.rows[table.tHead.rows.length - 1]?.cells || []).forEach((cell, index) => widths[index] = Math.round(cell.getBoundingClientRect().width));
		try { localStorage.setItem(this.storageKey(table), JSON.stringify(widths)); } catch (_) {}
	}
}

window.ModernTableResizer = ModernTableResizer;
