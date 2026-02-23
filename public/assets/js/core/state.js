/* =========================================================
	 GLOBAL STATE (UNIVERSAL)
	 ---------------------------------------------------------
	 → Menyimpan state aktif tabel & pagination
	 → Dipakai oleh TableManager dan modul lain
========================================================= */

const AppState = {
	halaman: 1, // Halaman aktif saat ini
	rows: 10, // Jumlah data per halaman
	module: "", // Module aktif (referensi, renstra, dll)
	action: "", // add / edit / detail  <-- TAMBAH INI
	tbl: "", // Tabel aktif
	cari: "", // Keyword pencarian
	currentMenu: "", // Tracking menu sebelumnya
	serverSources: [], // 🔥 @note daftar dropdown yang boleh fetch server
	primaryKey: "id",
	page: "", // untuk antisipasi seperti 1 halaman mempunyai banyak menu
};
AppState.role = window.USER_ROLE || "viewer";