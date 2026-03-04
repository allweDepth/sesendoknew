/* =========================================================
   CORE AJAX ENGINE
   ---------------------------------------------------------
   Wrapper universal untuk semua request AJAX di sistem.
   Semua komunikasi ke backend WAJIB lewat class ini.

   Tujuan:
   - Menyatukan pola request
   - Auto-handle toast global
   - Menjaga konsistensi response JSON
   - Menghindari duplikasi $.ajax di banyak file
========================================================= */

class AjaxEngine {
	/**
	 * Constructor
	 * @param {string} url - URL default (opsional)
	 */
	constructor(url = "") {
		this.url = url;
	}

	/**
	 * Method utama untuk melakukan AJAX request
	 *
	 * Parameter berbentuk object agar fleksibel dan scalable.
	 * Tidak ada perubahan logika dari versi asli.
	 */
	request({
		url = this.url, // Jika tidak dikirim, gunakan URL dari constructor
		method = AppConfig.defaultMethod, // Default method dari konfigurasi global
		data = {}, // Data yang dikirim ke server
		beforeSend = null, // Callback sebelum request dikirim
		success = null, // Callback saat request berhasil
		error = null, // Callback saat request gagal
		complete = null, // Callback setelah request selesai
		processData = true, // Apakah data diproses otomatis oleh jQuery
		contentType = "application/x-www-form-urlencoded; charset=UTF-8", // Tipe konten default
	}) {
		/**
		 * Menggunakan jQuery $.ajax
		 * Tidak diubah ke fetch agar tetap kompatibel
		 * dengan seluruh sistem yang sudah ada.
		 */
		return $.ajax({
			headers: {
				"X-CSRF-TOKEN": window.CSRF_TOKEN,
			},

			type: method, // GET / POST / PUT / DELETE
			url: url, // Endpoint tujuan
			data: data, // Data payload
			dataType: "json", // Sistem mengharapkan response JSON
			processData: processData,
			contentType: contentType,

			/**
			 * Sebelum request dikirim
			 */
			beforeSend: function () {
				if (beforeSend) beforeSend();
			},

			/**
			 * Jika request berhasil (HTTP 200)
			 */
			success: function (res) {
				/**
				 * =====================================================
				 * SESSION EXPIRED HANDLER
				 * -----------------------------------------------------
				 * Jika backend mengirimkan response:
				 * {
				 *   success: false,
				 *   expired: true,
				 *   message: "Session habis. Silakan login ulang."
				 * }
				 *
				 * Maka sistem akan:
				 * 1. Menampilkan pesan toast
				 * 2. Mengarahkan user kembali ke halaman utama
				 *
				 * Ini penting untuk arsitektur SPA karena:
				 * - Redirect dari PHP tidak bekerja pada AJAX request
				 * - Browser tidak akan berpindah halaman tanpa JS
				 *
				 * Handler ini dibuat global agar:
				 * - Tidak perlu ditangani di setiap module
				 * - Semua request AJAX otomatis aman
				 * =====================================================
				 */
				if (res && res.expired === true) {
					Toast.show({
						success: false,
						message: res.message || "Session habis. Silakan login ulang.",
					});

					// Beri sedikit delay agar toast terlihat oleh user
					setTimeout(function () {
						window.location.href = "/";
					}, 800);

					return; // hentikan proses success berikutnya
				}

				/**
				 * 🔥 AUTO TOAST GLOBAL
				 * -----------------------------------------------------
				 * Jika backend mengirimkan properti "success",
				 * maka otomatis tampilkan notifikasi.
				 *
				 * Format backend yang diharapkan:
				 * {
				 *   success: true/false,
				 *   message: "Pesan"
				 * }
				 *
				 * Ini menjaga konsistensi UI di seluruh sistem
				 * tanpa perlu menulis Toast di setiap module.
				 */
				if (res && typeof res.success !== "undefined") {
					Toast.show({
						success: res.success,
						message: res.message || "",
					});
				}

				/**
				 * Callback success tambahan
				 * -----------------------------------------------------
				 * Jika request memerlukan handler khusus,
				 * maka callback dari module akan dijalankan di sini.
				 */
				if (success) success(res);
			},

			/**
			 * Jika terjadi error (HTTP 4xx / 5xx)
			 */
			error: function (xhr) {
				let response = xhr.responseJSON;

				// Jika server mengirim JSON error
				if (response) {
					Toast.show({
						success: false,
						message: response.message || "Terjadi kesalahan",
					});

					// Jalankan callback error tambahan
					if (error) error(response);
				} else {
					// Jika server tidak mengirim JSON (error murni)
					ToastEngine.show({
						success: false,
						message: "Server error",
					});
				}
			},

			/**
			 * Selalu dijalankan setelah request selesai
			 * baik sukses maupun gagal
			 */
			complete: function () {
				if (complete) complete();
			},
		});
	}
}
