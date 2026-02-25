/**
 * ======================================================
 * APP INIT
 * ======================================================
 * File ini hanya bertugas:
 * - Menunggu DOM siap
 * - Menginisialisasi App
 * - Tidak boleh ada logic bisnis
 */

document.addEventListener("DOMContentLoaded", () => {

    // Buat instance aplikasi utama
    const app = new App();

    // Jalankan aplikasi
    app.init();

});