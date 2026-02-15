/* ==========================================
   GLOBAL CONFIG
========================================== */

const AppConfig = {
	baseUrl: window.BASEURL || "/",
	apiUrl: window.BASEURL || "/",
	defaultMethod: "POST",
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
				if (res.status === "success") {
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

$(document).ready(function () {
	const $context = $("#mainContext");

	/* =========================
       SIDEBAR (CUSTOM CONTEXT)
    ========================== */

	const $sidebar = $context.children(".ui.sidebar");

	$sidebar.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		$sidebar.sidebar("toggle");
	});

	/* =========================
       FLYOUT (CUSTOM CONTEXT)
    ========================== */

	const $flyout = $context.children(".ui.flyout");

	$flyout.flyout({
		context: $context,
		transition: "push",
		closable: false,
	});

	/* =========================
       OPEN FLYOUT BUTTON
    ========================== */

	$(document).on("click", '[name="flyout"]', function (e) {
		e.preventDefault();

		let btn = $(this);
		let tbl = btn.attr("tbl") || "";

		$("#content_flyout").text("Tambah Data " + tbl);

		$.get("/referensi/form", { tbl: tbl }, function (html) {
			$("#form_flyout").html(html);
			$flyout.flyout("show");
		});
	});

	/* =========================
       CLOSE FLYOUT
    ========================== */

	$(document).on("click", ".close.icon", function () {
		$flyout.flyout("hide");
	});

	/* =========================
       DROPDOWN & ACCORDION
    ========================== */

	$(".ui.dropdown").dropdown();

	$(".ui.accordion").accordion({
		exclusive: false,
	});
	/* =========================
   AUTO HEIGHT FLYOUT
========================= */

	function adjustFlyoutHeight() {
		const navbarHeight = $(".ui.top.attached.menu").outerHeight() || 0;
		const viewportHeight = $(window).height();

		$(".ui.flyout").css({
			height: viewportHeight - navbarHeight + "px",
		});
	}

	// run on load
	adjustFlyoutHeight();

	// run on resize
	$(window).on("resize", function () {
		adjustFlyoutHeight();
	});
});
