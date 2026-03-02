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
		});
	}
}

window.Toast = Toast;