/* ==========================================
   GLOBAL CONFIG
========================================== */

const AppConfig = {
	baseUrl: window.BASEURL || "/",
	apiUrl: window.BASEURL || "/",
	defaultMethod: "POST",
};

/* ==========================================
   CORE AJAX ENGINE (Referensi index.js)
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
   GENERIC CRUD (Mengikuti pola index.js)
========================================== */

class Crud {
	constructor(tbl) {
		this.tbl = tbl;
		this.url = AppConfig.apiUrl;
		this.ajax = new AjaxEngine(this.url);
	}

	create(data = {}) {
		return this.ajax.request({
			data: {
				jenis: "insert",
				tbl: this.tbl,
				...data,
			},
		});
	}

	read(params = {}) {
		return this.ajax.request({
			data: {
				jenis: "get_data",
				tbl: this.tbl,
				...params,
			},
		});
	}

	update(data = {}) {
		return this.ajax.request({
			data: {
				jenis: "update",
				tbl: this.tbl,
				...data,
			},
		});
	}

	delete(id) {
		return this.ajax.request({
			data: {
				jenis: "delete",
				tbl: this.tbl,
				id: id,
			},
		});
	}
}

/* ==========================================
   SECURITY MODULE (CRYPTO-JS)
========================================== */

const Security = {
	salt: "SESENDOK_2026_SECURE",

	hashPassword(password) {
		return CryptoJS.SHA256(password + this.salt).toString();
	},

	generateToken(data) {
		return CryptoJS.SHA256(data + Date.now()).toString();
	},
};

/* ==========================================
   AUTH MODULE (LOGIN & REGISTER PASSPORT)
========================================== */

class Auth {
	constructor() {
		this.ajax = new AjaxEngine(AppConfig.apiUrl);
	}

	login(formSelector) {
		let form = $(formSelector);
		let username = form.find('[name="username"]').val();
		let password = form.find('[name="password"]').val();

		let hashed = Security.hashPassword(password);

		return this.ajax.request({
			data: {
				jenis: "login",
				username: username,
				password: hashed,
			},
			success: function (res) {
				if (res.status === "success") {
					localStorage.setItem("auth_token", Security.generateToken(username));

					window.location.href = AppConfig.baseUrl + "dashboard";
				} else {
					$(".ui.message.error").show();
				}
			},
		});
	}

	register(formSelector) {
		let form = $(formSelector);
		let data = form.serializeArray();
		let obj = {};

		data.forEach((field) => {
			obj[field.name] = field.value;
		});

		obj.password = Security.hashPassword(obj.password);

		return this.ajax.request({
			data: {
				jenis: "register",
				...obj,
			},
			success: function (res) {
				if (res.status === "success") {
					window.location.href = AppConfig.baseUrl + "login";
				}
			},
		});
	}

	logout() {
		localStorage.removeItem("auth_token");
		window.location.href = AppConfig.baseUrl + "login";
	}
}

/* ==========================================
   AUTO INIT EVENTS
========================================== */
$(document).ready(function () {
	const auth = new Auth();

	// LOGIN
	$("body").on("submit", 'form[name="form_login"]', function (e) {
		e.preventDefault();
		auth.login(this);
	});

	// REGISTER
	$("body").on("submit", 'form[name="form_register"]', function (e) {
		e.preventDefault();
		auth.register(this);
	});
	/* =========================
       SIDEBAR
    ========================== */

	// $(".ui.sidebar").sidebar({
	// 	context: $(".ui.pushable"),
	// 	transition: "push",
	// });
	//sidebar toggle
	$(".ui.sidebar")
		.sidebar({
			context: $(".bottom.pushable"),
		})
		.sidebar("attach events", ".menu .item.nabiila")
		.sidebar("setting", "transition", "push");
	// $("#sidebar-toggle").on("click", function () {
	// 	$(".ui.sidebar").sidebar("toggle");
	// });

	/* =========================
       DROPDOWN & ACCORDION
    ========================== */

	$(".ui.dropdown").dropdown();

	$(".ui.accordion").accordion({
		exclusive: false,
	});

	/* =========================
       TOAST GLOBAL
    ========================== */

	window.showToast = function (type, message) {
		let color = "info";

		if (type === "success") color = "green";
		if (type === "error") color = "red";
		if (type === "warning") color = "orange";

		$("body").toast({
			class: color,
			message: message,
			position: "top right",
			displayTime: 3000,
			showProgress: "bottom",
		});
	};

	/* =========================
       REFERENSI - UPDATE (AJAX)
       (Delegated event supaya tetap jalan setelah reload partial)
    ========================== */

	$(document).on("blur", ".editable", function () {
		let el = $(this);

		$.post(
			"/referensi/update",
			{
				tbl: el.data("tbl"),
				id: el.data("id"),
				data: { nama: el.text() },
			},
			function (res) {
				showToast("success", "Data berhasil diperbarui");
			},
		).fail(function () {
			showToast("error", "Gagal memperbarui data");
		});
	});

	/* =========================
       REFERENSI - DELETE (AJAX)
    ========================== */

	$(document).on("click", ".btnDelete", function () {
		let el = $(this);

		if (!confirm("Yakin ingin menghapus data?")) return;

		$.post(
			"/referensi/delete",
			{
				tbl: el.data("tbl"),
				id: el.data("id"),
			},
			function () {
				showToast("success", "Data berhasil dihapus");

				// hapus row tanpa reload full
				el.closest("tr").remove();
			},
		).fail(function () {
			showToast("error", "Gagal menghapus data");
		});
	});
	/* =========================
   FLYOUT GLOBAL HANDLER
========================= */

	$(document).on("click", '[name="flyout"]', function (e) {
		e.preventDefault();

		let btn = $(this);
		let tbl = btn.attr("tbl");
		let mode = btn.attr("jns") || "add";

		// Set header
		$("#content_flyout").text(
			mode === "add" ? "Tambah Data " + tbl : "Edit Data " + tbl,
		);

		$("#icon_flyout").attr("class", "folder icon");

		// Load form via AJAX
		$.get(
			"/referensi/form",
			{
				tbl: tbl,
				mode: mode,
				id: btn.attr("id_row") || null,
			},
			function (html) {
				$("#form_flyout").html(html);

				$('[name="flyout"]')
					.flyout({
						transition: "overlay",
					})
					.flyout("show");
			},
		);
	});

	/* CLOSE */

	//====================================
	//=========== flyout =================
	//====================================
	$(".ui.flyout")
		.flyout({
			closable: false,
			context: $(".bottom.pushable"),
			onShow: function () {
				$("#biilainayah").addClass("disabled");
				// loaderHide();
				// console.log('onShow flyout');
			},
			onHide: function (choice) {
				// 		//console.log(choice);
				$("#biilainayah").removeClass("disabled");
				let form = $(".ui.flyout form");
				form.form("clear");
				removeRulesForm(form);
				// 		// //inisialize kembali agar tidak error di console
				var reinitForm = new FormGlobal(form);
				reinitForm.run();
			},
			onApprove: function (elemen) {
				$(elemen).closest("div.flyout").find("form").form("submit");
				return false;
			},
		})
		.flyout("attach events", '[name="flyout"]');

	/* SUBMIT */

	$(document).on("click", ".btnFlyoutSubmit", function () {
		let formData = $("#form_flyout").serialize();

		$.post("/referensi/store", formData, function (res) {
			showToast("success", "Data berhasil disimpan");
			$(".flyout").flyout("hide");
			location.reload();
		}).fail(function () {
			showToast("error", "Gagal menyimpan data");
		});
	});
});
