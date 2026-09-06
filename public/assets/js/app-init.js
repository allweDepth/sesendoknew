$(document).ready(function () {
	// ===============================
	// APP INIT
	// ===============================
	window.app = new App();
	window.app.init();
	window.moduleMutationPolicy = function (tbl, req) {
		const role = window.app?.user?.type_user || "viewer";
		const path = window.location.pathname.replace(window.APP_BASE_PATH || "", "");
		const result = (allowed) => ({ add: allowed, edit: allowed, delete: false, import: allowed });
		if (path.startsWith("/standar_harga")) return result(role === "tapd");
		if (path.startsWith("/mapping")) return result(role === "tapd");
		if (path.startsWith("/referensi")) {
			if (tbl === "organisasi") return result(role === "admin_wilayah");
			if (tbl === "wilayah") return result(role === "super_admin");
			if (["rekening_kegiatan", "satuan", "aset", "akun", "sumber_dana"].includes(tbl)) return result(role === "tapd");
			return result(false);
		}
		return null;
	};

	const $context = $("#mainContext");

	// ===============================
	// SIDEBAR KIRI (STABIL)
	// ===============================
	const $sidebarUtama = $(".sidebarutama");

	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		// =====================================
		// FIX 1: tutup sidebar kanan jika terbuka
		// =====================================
		if ($sidebarKanan.hasClass("visible")) {
			// cek state fomantic
			$sidebarKanan.sidebar("hide"); // tutup flyout kanan
		}

		// =====================================
		// FIX 2: toggle sidebar kiri
		// =====================================
		$sidebarUtama.sidebar("toggle");
	});

	// ===============================
	// 🔥 SIDEBAR KANAN (INIT SEKALI SAJA)
	// ===============================
	const $sidebarKanan = $(".sidebarkanan");

	$sidebarKanan.sidebar({
		context: $context,
		transition: "push",
		dimPage: true,
		closable: true,
	});
	$(".ui.dropdown").dropdown();
	const scopePicker = $("#regionalScopePicker .dropdown");
	if (scopePicker.length) {
		window.Ajax.request({url:"/scope/opds",method:"GET",success:(res)=>{
			const data=res.data||{},menu=scopePicker.find(".menu").empty();
			menu.append('<div class="item" data-value="0">Seluruh OPD</div>');
			(data.rows||[]).forEach(row=>menu.append($("<div>",{class:"item","data-value":row.kode,text:`${row.kd_wilayah} — ${row.kode} — ${row.uraian}`})));
			scopePicker.dropdown({onChange:(value)=>{
				if(String(value)===String(data.selected_opd||"0"))return;
				window.Ajax.request({url:"/scope/select",method:"POST",data:{kd_opd:value||"0"},success:()=>window.location.reload()});
			}}).dropdown("set selected",data.selected_opd||"0");
		}});
	}

	// Toolbar atas berlaku pada tabel aktif. TableManager menangani tabel server;
	// fallback ini menyaring tabel/list khusus yang dirender oleh modul sendiri.
	const filterActiveUi = () => {
		if (window.tableManager && window.TableManager?.instances?.[window.tableManager.tbl]) return;
		const query = String($('#cari_data').val() || '').toLowerCase().trim();
		const rawLimit = $('#countRow').dropdown('get value') || '5';
		const limit = rawLimit === 'all' ? Number.MAX_SAFE_INTEGER : (parseInt(rawLimit, 10) || 5);
		$('#main-content table:visible tbody').each(function () {
			let shown = 0;
			$(this).children('tr').each(function () {
				const match = !query || $(this).text().toLowerCase().includes(query);
				const visible = match && shown < limit;
				$(this).toggle(visible);
				if (visible) shown++;
			});
		});
		$('#main-content .budget-group, #main-content [data-global-search-item]').each(function (index) {
			const match = !query || $(this).text().toLowerCase().includes(query);
			$(this).toggle(match && index < limit);
		});
	};
	let toolbarTimer;
	$(document).off('.globalTableToolbar')
		.on('input.globalTableToolbar', '#cari_data', () => { clearTimeout(toolbarTimer); toolbarTimer = setTimeout(filterActiveUi, 180); })
		.on('click.globalTableToolbar', '.cari_data .search.icon', filterActiveUi);
	$('#countRow').dropdown({ onChange: () => setTimeout(filterActiveUi, 0) });

	// Tema pengguna persisten dan tetap aktif setelah navigasi SPA/refresh.
	const applyTheme = (dark) => {
		$("body").toggleClass("dark-mode", dark);
		const toggle = $("#darkToggle");
		toggle.find("span").text(dark ? "Mode Terang" : "Mode Gelap");
		toggle.find("i").toggleClass("moon", !dark).toggleClass("sun", dark);
		toggle.attr("aria-pressed", dark ? "true" : "false");
	};
	let savedDark = false;
	try { savedDark = localStorage.getItem("sesendok-theme") === "dark"; } catch (_) {}
	applyTheme(savedDark);
	$(document).off("click.userTheme keydown.userTheme", "#darkToggle")
		.on("click.userTheme keydown.userTheme", "#darkToggle", function (e) {
			if (e.type === "keydown" && !["Enter", " "].includes(e.key)) return;
			e.preventDefault();
			const dark = !$("body").hasClass("dark-mode");applyTheme(dark);
			try { localStorage.setItem("sesendok-theme", dark ? "dark" : "light"); } catch (_) {}
			$("#userMenu").dropdown("hide");
		});
	$(".ui.sticky").sticky({
		context: $context,
	});

	// ===============================
	// LOGOUT
	// ===============================
	$(document).on("click", "#btnLogout", function (e) {
		e.preventDefault();

		DialogEngine.show({
			title: "Konfirmasi Logout",
			message: "Yakin ingin keluar dari sistem?",
			icon: "sign out alternate red",
			approveText: "Ya, Logout",
			cancelText: "Batal",
			onApprove: () => {
				return new Promise((resolve) => {
					window.location.href = window.appUrl ? window.appUrl("/logout") : "/logout";
					resolve();
				});
			},
		});
	});

	$(document).on("click", ".message-close", function () {
		$(this).closest(".message").transition("fade");
	});
	// ===============================
	// FLYOUT CONTROLLER
	// ===============================
	window.Flyout = new FlyoutController();
	// =====================================
	// SIMPLE ACCORDION TANPA FOMANTIC
	// =====================================
	// =====================================
	// SIMPLE ACCORDION (FIX FINAL STABIL)
	// =====================================
	$(".sidebarutama > .item > .title")
		.off("click")
		.on("click", function () {
			const $title = $(this);
			const $item = $title.parent(); // item induk
			const $content = $item.children(".content");

			const $allItem = $(".sidebarutama > .item");

			// tutup semua selain ini
			$allItem.not($item).children(".content").slideUp(150);
			$allItem.not($item).children(".title").removeClass("active");

			// toggle current
			$content.stop(true, true).slideToggle(150);
			$title.toggleClass("active");
		});
});
