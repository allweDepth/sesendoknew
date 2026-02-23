/* =========================================================
	 MODAL KONFIRMASI
	 ---------------------------------------------------------
	 → Konfirmasi data di hapus
========================================================= */

const DialogEngine = {
	show({
		title = "Konfirmasi",
		message = "Yakin?",
		approveText = "Ya",
		cancelText = "Batal",
		icon = null,
		onApprove = null,
	}) {
		return new Promise((resolve, reject) => {
			const $modal = $("#globalDialog");
			const $approveBtn = $modal.find(".approve");
			const $cancelBtn = $modal.find(".cancel");

			// ============================
			// SET CONTENT
			// ============================
			$("#dialogTitle").html(
				icon ? `<i class="${icon} icon"></i> ${title}` : title,
			);

			$("#dialogMessage").html(message);

			$approveBtn.find(".btn-text").text(approveText);
			$cancelBtn.text(cancelText);

			// Reset state
			$approveBtn.removeClass("loading disabled");
			$cancelBtn.removeClass("disabled");

			// ============================
			// INIT MODAL
			// ============================
			$modal
				.modal({
					closable: false,
					transition: "fade up",
					duration: 200,

					onApprove: function () {
						// Auto loading state
						$approveBtn.addClass("loading disabled");
						$cancelBtn.addClass("disabled");

						// Jika async function
						if (typeof onApprove === "function") {
							let result = onApprove();

							if (result instanceof Promise) {
								result
									.then(() => {
										$modal.modal("hide");
										resolve(true);
									})
									.catch(() => {
										$approveBtn.removeClass("loading disabled");
										$cancelBtn.removeClass("disabled");
									});
							} else {
								$modal.modal("hide");
								resolve(true);
							}
						} else {
							$modal.modal("hide");
							resolve(true);
						}

						return false; // prevent auto close
					},

					onDeny: function () {
						reject(false);
					},

					onHidden: function () {
						$approveBtn.removeClass("loading disabled");
						$cancelBtn.removeClass("disabled");
					},
				})
				.modal("show");
		});
	},
};