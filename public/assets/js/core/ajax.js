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
		timeout = 30000,
	}) {
		const recentSubmittedForm = Date.now() - Number(window.__lastSubmittedFormAt || 0) < 1000
			? window.__lastSubmittedForm : null;
		const activeForm = $(document.activeElement).closest('form.ui.form')[0] || recentSubmittedForm || null;
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
			timeout: timeout,

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
						message: AjaxEngine.formatMessage(res),
					});
				}

				// Beberapa handler legacy mengembalikan HTTP 200 untuk kegagalan.
				// Jangan pernah menjalankan callback sukses CRUD dalam kondisi ini.
				if (res && res.success === false) {
					if (window.FormFeedback && activeForm) FormFeedback.error(activeForm, res);
					if (error) error(res, null);
					return;
				}
				if (res && res.success === true && window.FormFeedback && activeForm) {
					FormFeedback.success(activeForm, res.message || 'Data berhasil diproses');
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
			error: function (xhr, textStatus, errorThrown) {
				let response = xhr.responseJSON;

				// Jika server mengirim JSON error
				if (response) {
					if (response.expired === true || xhr.status === 401) {
						$('body').addClass('session-expired');
						window.location.replace(window.appUrl ? window.appUrl('/') : '/');
						return;
					}
					if (window.FormFeedback && activeForm) FormFeedback.error(activeForm, response);
					Toast.show({
						success: false,
						message: AjaxEngine.formatMessage(response),
					});

					// Jalankan callback error tambahan
					if (error) error(response, xhr);
				} else {
					// Jika server tidak mengirim JSON (error murni)
					const message = textStatus === "timeout"
						? "Permintaan melewati batas 30 detik. Periksa koneksi atau query yang terlalu berat."
						: `Server gagal merespons (${xhr.status || "jaringan"}): ${errorThrown || textStatus || "respons bukan JSON"}`;
					Toast.show({
						success: false,
						message,
					});
					if (window.FormFeedback && activeForm) FormFeedback.error(activeForm, { message });
					if (error) error({ success: false, message, errors: {} }, xhr);
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

	static formatMessage(response) {
		if (!response) return "Terjadi kesalahan yang tidak diketahui";
		const details = [];
		const errors = response.errors;
		if (Array.isArray(errors)) {
			errors.forEach(value => value && details.push(String(value)));
		} else if (errors && typeof errors === "object") {
			Object.entries(errors).forEach(([field, value]) => {
				const values = Array.isArray(value) ? value : [value];
				values.forEach(item => item && details.push(`${field}: ${item}`));
			});
		}
		const base = response.message || (response.success === false ? "Operasi gagal" : "Berhasil");
		return details.length ? `${base} — ${details.join("; ")}` : base;
	}
}
