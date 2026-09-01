class Toast {
	static success(message) {
		this.show({ success: true, message });
	}

	static error(message) {
		this.show({ success: false, message });
	}

	static show({ success = true, message = "" }) {
		if (!message) return;

		let maxToast = 5;
		let existing = $("#toastContainer .ui.toast");

		if (existing.length >= maxToast) {
			existing.first().remove();
		}

		$("#toastContainer").toast({
			message: message,
			class: success ? "success" : "error",
			displayTime: 3000,
			showProgress: "bottom",
			context: "#toastContainer",
			position: "top right",
			onHidden: () => Toast.cleanup(),
		});
		setTimeout(() => Toast.cleanup(), 3400);
	}

	static cleanup() {
		const container = $("#toastContainer");
		container.find(".toast-box").each(function () {
			if (!$(this).find(".ui.toast").length) $(this).remove();
		});
		if (!container.find(".ui.toast").length) container.empty().hide();
		else container.show();
	}
}

window.Toast = Toast;
