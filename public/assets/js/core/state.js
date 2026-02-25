/**
 * ======================================================
 * GLOBAL APPLICATION STATE
 * ======================================================
 * Menyimpan data global SPA
 * Tidak boleh berisi DOM manipulation
 */

class AppState {

    constructor() {

        // Module aktif
        this.module = null;

        // Tabel aktif
        this.tbl = null;

        // Row aktif
        this.activeRow = null;

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