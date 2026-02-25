/* =========================================================
   CORE AJAX ENGINE
   ---------------------------------------------------------
   → Wrapper universal untuk semua AJAX request
   → Semua komunikasi backend lewat sini
   → Auto Toast global (jika res.success ada)
   ---------------------------------------------------------
   TIDAK mengubah behavior asli.
========================================================= */

class AjaxEngine {

	constructor(url = "") {
		this.url = url;
	}

	request(options = {}) {

		const {
			url = this.url,
			method = AppConfig.defaultMethod,
			data = {},
			beforeSend = null,
			success = null,
			error = null,
			complete = null,
			processData = true,
			contentType = "application/x-www-form-urlencoded; charset=UTF-8",
		} = options;

		return $.ajax({

			type: method,
			url: url,
			data: data,
			dataType: "json",
			processData: processData,
			contentType: contentType,

			/* --------------------------------------------------
			   BEFORE SEND
			-------------------------------------------------- */
			beforeSend: () => {
				if (typeof beforeSend === "function") {
					beforeSend();
				}
			},

			/* --------------------------------------------------
			   SUCCESS HANDLER
			-------------------------------------------------- */
			success: (res) => {

				// 🔥 AUTO TOAST GLOBAL (hanya jika res.success ada)
				if (
					res &&
					typeof res.success !== "undefined" &&
					typeof ToastEngine !== "undefined"
				) {
					ToastEngine.show({
						success: res.success,
						message: res.message || "",
					});
				}

				if (typeof success === "function") {
					success(res);
				}
			},

			/* --------------------------------------------------
			   ERROR HANDLER
			-------------------------------------------------- */
			error: (xhr) => {

				let response = xhr.responseJSON;

				if (
					response &&
					typeof ToastEngine !== "undefined"
				) {
					ToastEngine.show({
						success: false,
						message:
							response.message || "Terjadi kesalahan",
					});

					if (typeof error === "function") {
						error(response);
					}
				} else {

					if (typeof ToastEngine !== "undefined") {
						ToastEngine.show({
							success: false,
							message: "Server error",
						});
					}

					if (typeof error === "function") {
						error(xhr);
					}
				}
			},

			/* --------------------------------------------------
			   COMPLETE
			-------------------------------------------------- */
			complete: () => {
				if (typeof complete === "function") {
					complete();
				}
			},
		});
	}
}