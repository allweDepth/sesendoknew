/* ==========================================
   GLOBAL CONFIG
========================================== */

const AppConfig = {
	baseUrl: window.BASEURL || "/",
	apiUrl: window.BASEURL || "/",
	defaultMethod: "POST",
};

/* ==========================================
   GLOBAL STATE (UNIVERSAL)
========================================== */

const AppState = {
	halaman: 1,
	rows: 10,
	jenis: "",
	tbl: "",
	cari: "",
	currentMenu: "",
};

/* ==========================================
   CORE AJAX ENGINE
========================================== */

class AjaxEngine {
	constructor(url = "") {
		this.url = url;
	}

	request({
		url = this.url,
		method = AppConfig.defaultMethod,
		data = {},
		beforeSend = null,
		success = null,
		error = null,
		complete = null,
	}) {
		return $.ajax({
			type: method,
			url: url,
			data: data,
			dataType: "json",
			beforeSend: function () {
				if (beforeSend) beforeSend();
			},
			success: function (res) {
				if (success) success(res);
			},
			error: function (err) {
				console.error("AJAX ERROR:", err);
				if (error) error(err);
			},
			complete: function () {
				if (complete) complete();
			},
		});
	}
}

/* ==========================================
   UNIVERSAL TABLE MODULE
========================================== */

class TableManager {
	constructor() {
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
	}

	load(jenis, tbl) {
		if (AppState.currentMenu !== tbl) {
			AppState.halaman = 1;
		}

		AppState.jenis = jenis;
		AppState.tbl = tbl;
		AppState.currentMenu = tbl;

		this.fetch();
	}

	fetch() {
		this.ajax.request({
			data: {
				jenis: AppState.jenis,
				tbl: AppState.tbl,
				halaman: AppState.halaman,
				rows: AppState.rows,
				cari: AppState.cari,
			},
			success: (res) => {
				if (!res || res.success !== true) {
					console.warn(res?.message || "Response tidak valid");
					this.renderTable([]);
					return;
				}

				// FIX DI SINI
				let rows = Array.isArray(res.data) ? res.data : [];
				let meta = res.meta || {};

				this.renderTable(rows);

				this.renderPagination({
					total: meta.total || 0,
					limit: meta.limit || AppState.rows,
					page: meta.page || AppState.halaman,
				});
			},
		});
	}

	renderTable(rows) {
		if (!Array.isArray(rows)) {
			rows = [];
		}

		let html = "";

		rows.forEach((row) => {
			html += "<tr>";

			Object.values(row).forEach((val) => {
				html += `<td>${val ?? ""}</td>`;
			});

			html += "</tr>";
		});

		$('tbody[name="tabel_referensi"]').html(html);
	}

	renderPagination(meta) {
		if (!meta || !meta.total) {
			$('div[name="pagination_referensi"]').html("");
			return;
		}

		let currentPage = meta.page || 1;
		let limit = meta.limit || 10;
		let totalPage = Math.ceil(meta.total / limit);

		if (totalPage <= 1) {
			$('div[name="pagination_referensi"]').html("");
			return;
		}

		let html = `<div class="ui pagination menu">`;

		// ===== FIRST & PREV (TIDAK MUNCUL JIKA HALAMAN 1) =====
		if (currentPage > 1) {
			html += `
			<a class="item" data-page="1">
				<i class="angle double left chevron icon"></i>
			</a>
		`;

			html += `
			<a class="item" data-page="${currentPage - 1}">
				<i class="angle left icon"></i>
			</a>
		`;
		}

		// ===== PAGE NUMBERS (maks 3 sekitar current) =====
		let start = Math.max(1, currentPage - 1);
		let end = Math.min(totalPage, currentPage + 1);

		if (currentPage === 1) {
			end = Math.min(3, totalPage);
		}

		if (currentPage === totalPage) {
			start = Math.max(totalPage - 2, 1);
		}

		for (let i = start; i <= end; i++) {
			let active = i === currentPage ? "active" : "";
			html += `
			<a class="item ${active}" data-page="${i}">
				${i}
			</a>
		`;
		}

		// ===== NEXT & LAST (TIDAK MUNCUL JIKA HALAMAN TERAKHIR) =====
		if (currentPage < totalPage) {
			html += `
			<a class="item" data-page="${currentPage + 1}">
				<i class="angle right icon"></i>
			</a>
		`;

			html += `
			<a class="item" data-page="${totalPage}">
				<i class="angle double right chevron icon"></i>
			</a>
		`;
		}

		html += `</div>`;

		$('div[name="pagination_referensi"]').html(html);
	}
}

/* ==========================================
   GLOBAL PAGINATION FUNCTION
========================================== */

function changePage(page) {
	AppState.halaman = page;
	tableManager.fetch();
}

/* ==========================================
   AUTH MODULE
========================================== */

class Auth {
	constructor() {
		this.ajax = new AjaxEngine(AppConfig.apiUrl);
	}

	login(formSelector) {
		let form = $(formSelector);
		let username = form.find('[name="username"]').val();
		let password = form.find('[name="password"]').val();

		return this.ajax.request({
			data: {
				jenis: "login",
				username,
				password,
			},
			success: function (res) {
				if (res.success) {
					window.location.href = AppConfig.baseUrl + "dashboard";
				} else {
					$(".ui.message.error").show();
				}
			},
		});
	}
}

/* ==========================================
   INIT
========================================== */

let tableManager;

$(document).ready(function () {
	tableManager = new TableManager();

	const $context = $("#mainContext");

	const $sidebar = $context.children(".ui.sidebar");
	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	const currentPath = window.location.pathname.replace(/^\/+/g, "");

	if (tblFromUrl && currentPath) {
		tableManager.load(currentPath, tblFromUrl);
	}
	$sidebar.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		$sidebar.sidebar("toggle");
	});

	const $flyout = $context.children(".ui.flyout");

	$flyout.flyout({
		context: $context,
		transition: "push",
		closable: false,
	});

	/* =========================
	   MENU CLICK LOAD TABLE
	========================= */

	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();

		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");

		tableManager.load(jenis, tbl);
	});

	/* =========================
	   DROPDOWN COUNT ROW
	========================= */

	$("#countRow").on("change", function () {
		AppState.rows = parseInt($(this).val());
		AppState.halaman = 1;
		tableManager.fetch();
	});

	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });
	$(document).on(
		"click",
		'div[name="pagination_referensi"] .item',
		function () {
			let page = parseInt($(this).data("page"));
			if (!page) return;

			AppState.halaman = page;
			tableManager.fetch();
		},
	);

	/* =========================
   SEARCH
========================= */
	let searchTimer;

	$("#cari_data").on("input", function () {
		clearTimeout(searchTimer);

		searchTimer = setTimeout(() => {
			AppState.cari = $(this).val().trim();
			AppState.halaman = 1;
			tableManager.fetch();
		}, 700);
	});
	// $(".ui.search").search({
	// 	source: [],
	// 	searchOnFocus: false,
	// 	onSearchQuery: function (query) {
	// 		AppState.cari = query;
	// 		AppState.halaman = 1;
	// 		tableManager.fetch();
	// 	},
	// });
});
