// lihat file , apa tidak ada definini const AppState = {
// 	halaman: 1, // Halaman aktif saat ini
// 	rows: 10, // Jumlah data per halaman
// 	module: "", // Module aktif (referensi, renstra, dll)
// 	action: "", // add / edit / detail  <-- TAMBAH INI
// 	tbl: "", // Tabel aktif
// 	cari: "", // Keyword pencarian
// 	currentMenu: "", // Tracking menu sebelumnya
// 	serverSources: [], // 🔥 @note daftar dropdown yang boleh fetch server
// 	primaryKey: "id",
// 	page: "", // untuk antisipasi seperti 1 halaman mempunyai banyak menu
// };
$(document).ready(function () {

    window.app = new App();
    window.app.init();

    const $sidebarUtama = $(".sidebarutama");
    const $context = $("#mainContext");

    $sidebarUtama.sidebar({
        context: $context,
        transition: "push"
    });

    $("#toggleSidebar").on("click", function () {
        $sidebarUtama.sidebar("toggle");
    });

    $('.ui.accordion').accordion();
    $('.ui.dropdown').dropdown();
    $('.ui.sticky').sticky({
        context: $context
    });
// ===============================
// AUTO INIT MODULE BERDASARKAN URL
// ===============================
const path = window.location.pathname;
$(document).on("click", "#btnLogout", function (e) {

	e.preventDefault();

	DialogEngine.show({
		title: "Konfirmasi Logout",
		message: "Yakin ingin keluar dari sistem?",
		icon: "sign out alternate red",
		approveText: "Ya, Logout",
		cancelText: "Batal",

		onApprove: () => {

			// Return Promise agar loading state bekerja
			return new Promise((resolve) => {

				// Jika logout via server route
				window.location.href = "/logout";

				resolve();
			});
		}
	});
});
});