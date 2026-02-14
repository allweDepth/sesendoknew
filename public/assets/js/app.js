$(document).ready(function () {
	/* =========================
       SIDEBAR
    ========================== */

	$(".ui.sidebar").sidebar({
		context: $(".ui.pushable"),
		transition: "push",
	});

	$("#sidebar-toggle").on("click", function () {
		$(".ui.sidebar").sidebar("toggle");
	});

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

				$(".flyout")
					.sidebar({
						transition: "overlay",
					})
					.sidebar("show");
			},
		);
	});

	/* CLOSE */

	$(document).on("click", ".btnFlyoutClose, .flyout", function () {
		$(".flyout").sidebar("hide");
	});

	/* SUBMIT */

	$(document).on("click", ".btnFlyoutSubmit", function () {
		let formData = $("#form_flyout").serialize();

		$.post("/referensi/store", formData, function (res) {
			showToast("success", "Data berhasil disimpan");
			$(".flyout").sidebar("hide");
			location.reload();
		}).fail(function () {
			showToast("error", "Gagal menyimpan data");
		});
	});
});
