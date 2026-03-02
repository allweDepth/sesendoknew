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
				 * 🔥 AUTO TOAST GLOBAL
				 * Jika backend mengirimkan properti "success",
				 * maka otomatis tampilkan notifikasi.
				 *
				 * Format backend yang diharapkan:
				 * {
				 *   success: true/false,
				 *   message: "Pesan"
				 * }
				 */
				if (res && typeof res.success !== "undefined") {
					Toast.show({
						success: res.success,
						message: res.message || "",
					});
				}

				// Jalankan callback success tambahan (jika ada)
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
