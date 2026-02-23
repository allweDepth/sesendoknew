/* =========================================================
	 CORE AJAX ENGINE
	 ---------------------------------------------------------
	 → Wrapper universal untuk semua AJAX request
	 → Semua komunikasi backend lewat sini
========================================================= */

class AjaxEngine {
	constructor(url = "") {
		this.url = url;
	}

	request({
		url = this.url,
		method = AppConfig.defaultMethod,
		data = {},
		beforeSend = null,
		success = null,
		error = null,
		complete = null,
		processData = true,
		contentType = "application/x-www-form-urlencoded; charset=UTF-8",
	}) {
		return $.ajax({
			type: method,
			url: url,
			data: data,
			dataType: "json",
			processData: processData,
			contentType: contentType,
			beforeSend: function () {
				if (beforeSend) beforeSend();
			},
			success: function (res) {
				// 🔥 AUTO TOAST GLOBAL
				if (res && typeof res.success !== "undefined") {
					ToastEngine.show({
						success: res.success,
						message: res.message || "",
					});
				}

				if (success) success(res);
			},
			error: function (xhr) {
				let response = xhr.responseJSON;

				if (response) {
					ToastEngine.show({
						success: false,
						message: response.message || "Terjadi kesalahan",
					});

					if (error) error(response);
				} else {
					ToastEngine.show({
						success: false,
						message: "Server error",
					});
				}
			},
			complete: function () {
				if (complete) complete();
			},
		});
	}
}