const WallchatModule = {
	init() {
		this.bindPost();
		this.bindComment();
		this.bindPrivateMessage();
	},

	bindPost() {
		$(document).on("submit", "#formPost", function (e) {
			e.preventDefault();
			$.post(
				"/wallchat/store",
				$(this).serialize(),
				function (res) {
					if (res.status) location.reload();
				},
				"json",
			);
		});
	},

	bindComment() {
		$(document).on("submit", ".formComment", function (e) {
			e.preventDefault();

			let parent_id = $(this).data("id");
			let content = $(this).find('input[name="content"]').val();

			$.post(
				"/wallchat/comment",
				{
					parent_id,
					content,
				},
				function (res) {
					if (res.status) location.reload();
				},
				"json",
			);
		});
	},

	bindPrivateMessage() {
		$(document).on("click", "#btnPrivateMessage", function () {
			$("#modalPrivateMessage").modal("show");
		});

		$(document).on("submit", "#formPrivateMessage", function (e) {
			e.preventDefault();

			$.post(
				"/wallchat/private",
				$(this).serialize(),
				function (res) {
					if (res.status) {
						$("#modalPrivateMessage").modal("hide");
					}
				},
				"json",
			);
		});
	},
};
/* =========================================
	   WALLCHAT MODULE (TETAP UTUH)
	========================================= */

$(document).on("submit", "#formPost", function (e) {
	e.preventDefault();
	$.post(
		"/wallchat/store",
		$(this).serialize(),
		function (res) {
			if (res.status) location.reload();
		},
		"json",
	);
});

$(document).on("submit", ".formComment", function (e) {
	e.preventDefault();

	let parent_id = $(this).data("id");
	let content = $(this).find('input[name="content"]').val();

	$.post(
		"/wallchat/comment",
		{
			parent_id: parent_id,
			content: content,
		},
		function (res) {
			if (res.status) location.reload();
		},
		"json",
	);
});

$(document).on("click", "#btnPrivateMessage", function () {
	$("#modalPrivateMessage").modal("show");
});

$(document).on("submit", "#formPrivateMessage", function (e) {
	e.preventDefault();

	$.post(
		"/wallchat/private",
		$(this).serialize(),
		function (res) {
			if (res.status) {
				$("#modalPrivateMessage").modal("hide");
				DialogEngine.show({
					title: "Hapus Data",
					message: "Data yang dihapus tidak dapat dikembalikan.",
					icon: "trash alternate outline red",
					approveText: "Hapus",
					onApprove: () => {
						return new Promise((resolve, reject) => {
							$.post(
								AppConfig.apiUrl + "dynamic",
								{
									module: AppState.module,
									action: "delete",
									tbl: tbl,
									id_row: id,
								},
								function (res) {
									if (res.success) {
										tableManager.fetch();
										resolve();
									} else {
										reject();
									}
								},
								"json",
							);
						});
					},
				});
			}
		},
		"json",
	);
});
