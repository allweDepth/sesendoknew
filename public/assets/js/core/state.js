/**
 * ======================================================
 * GLOBAL APPLICATION STATE
 * ======================================================
 * Menyimpan data global SPA
 * Tidak boleh berisi DOM manipulation
 */

class AppState {
	constructor() {
		this.module = null; // modul aktif

		this.tbl = null; // tabel aktif

		this.activeRow = null; // row aktif

		this.req = null; // request tambahan (mapping dll)
	}

	/**
	 * Set module aktif
	 */
	setModule(moduleName) {
		this.module = moduleName;
	}

	/**
	 * Set tabel aktif
	 */
	setTable(tableName) {
		this.tbl = tableName;
	}

	/**
	 * Set row aktif
	 */
	setActiveRow(id) {
		this.activeRow = id;
	}
}
