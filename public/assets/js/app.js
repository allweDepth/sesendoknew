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
				if (!res.success) {
					console.warn(res.message);
					return;
				}

				this.renderTable(res.data.rows || []);
				this.renderPagination(res.meta.total || 0, res.meta.limit || 10);
			},
		});
	}

	renderTable(rows) {
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

	renderPagination(total, limit) {
		let totalPage = Math.ceil(total / limit);
		let html = "";

		for (let i = 1; i <= totalPage; i++) {
			let active = i === AppState.halaman ? "active" : "";

			html += `
			<a class="item ${active}" data-page="${i}">
				${i}
			</a>
		`;
		}

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
});
