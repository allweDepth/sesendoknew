/* =========================================================
   GLOBAL TOAST ENGINE
========================================================= */

const ToastEngine = {
	show({ success = true, message = "" }) {
		if (!message) return;

		let maxToast = 5;

		// 🔥 batasi maksimal 3 toast
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
	},
};