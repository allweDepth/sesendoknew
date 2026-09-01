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
		const createdAt = Date.now();
		setTimeout(() => $("#toastContainer .ui.toast").filter(function(){return !$(this).attr("data-toast-created");}).attr("data-toast-created", String(createdAt)), 0);
		setTimeout(() => Toast.cleanup(true, createdAt), 3600);
	}

	static cleanup(forceExpired = false, createdAt = 0) {
		const container = $("#toastContainer");
		if (forceExpired) container.find(".ui.toast").each(function(){const born=Number($(this).attr("data-toast-created")||0);if(!born||born<=createdAt)$(this).remove();});
		container.find(".toast-box").each(function () {
			if (!$(this).find(".ui.toast").length) $(this).remove();
		});
		if (!container.find(".ui.toast").length) container.empty().hide();
		else container.show();
	}
}

window.Toast = Toast;
