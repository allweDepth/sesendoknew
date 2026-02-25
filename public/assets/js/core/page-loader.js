/* =========================================================
   PAGE LOADER (MODULE PATTERN)
   ---------------------------------------------------------
   Bertugas menentukan tabel mana yang harus dimuat
   berdasarkan URL aktif.

   Pola:
   - Ambil path dari browser
   - Tentukan module
   - Tentukan tbl aktif
   - Panggil tableManager.load()

   Tidak diubah ke class.
   Tidak mengubah logika.
========================================================= */

const PageLoader = (function () {

	function loadFromUrl() {

		const currentPath = window.location.pathname.replace(/^\/+/g, "");

		AppState.page = currentPath;

		const params = new URLSearchParams(window.location.search);

		const segments = currentPath.split("/");

		const moduleName = segments[0];

		const moduleConfig = UIConfig[moduleName];

		if (!moduleConfig) return;

		let tbl = params.get("tbl") || Object.keys(moduleConfig)[0];

		if (!tbl) return;

		if (typeof tableManager !== "undefined") {
			// 🔥 PERBAIKAN DI SINI
			tableManager.load(moduleName, tbl);
		}
	}

	return {
		loadFromUrl
	};

})();