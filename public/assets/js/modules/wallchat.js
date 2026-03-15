/**
 * ============================================================
 * WALLCHAT MODULE
 * ============================================================
 * Modul khusus tanpa table
 */

class WallchatModule {
	constructor() {
		this.state = window.app.state;
		this.ajax = window.app.ajax;

		this.mainContainer = "#main-content";
	}

	init() {
		this.bindEvents();
		this.initDropdown();
	}

	bindEvents() {
		$(document).off("submit", "#formPost");

		$(document).on("submit", "#formPost", (e) => {
			e.preventDefault();

			const content = $('#formPost textarea[name="content"]').val();
			const token = $("#csrf_token").val();
			this.ajax.request({
				data: {
					action: "add",
					tbl: "wallchat",
					content: content,
					type: "status",
				},

				success: () => {
					$("#formPost textarea").val("");

					this.reloadFeed();
				},
			});
		});
		// komentar feed
		$(document).off("submit", ".formComment");

		$(document).on("submit", ".formComment", (e) => {
			e.preventDefault();

			const form = $(e.currentTarget);

			const feedId = form.data("id");

			const content = form.find('input[name="content"]').val();

			this.ajax.request({
				data: {
					action: "add",
					tbl: "wallchat",
					parent_id: feedId,
					content: content,
					type: "comment",
				},

				success: () => {
					form.find("input").val("");
				},
			});
		});
		// kirim pesan pribadi
		$(document).off("submit", "#formPrivateMessage");

		$(document).on("submit", "#formPrivateMessage", (e) => {
			e.preventDefault();

			const receiver = $('input[name="receiver_id"]').val();
			const content = $('#formPrivateMessage textarea[name="content"]').val();

			this.ajax.request({
				data: {
					action: "add",
					tbl: "wallchat",
					receiver_id: receiver,
					content: content,
					type: "private",
				},

				success: () => {
					$("#modalPrivateMessage").modal("hide");

					$("#formPrivateMessage textarea").val("");
				},
			});
		});
		// delete feed
		$(document).off("click", ".btnDeleteFeed");

		$(document).on("click", ".btnDeleteFeed", (e) => {
			const id = $(e.currentTarget).data("id");

			if (!confirm("Hapus status ini?")) return;

			this.ajax.request({
				data: {
					action: "delete",
					tbl: "wallchat",
					id: id,
				},

				success: () => {
					this.reloadFeed();
				},
			});
		});
	}
	reloadFeed() {
		if (!$("#feedContainer").length) return; // FIX SPA guard

		fetch("/wallchat/feed", {
			headers: { "X-Requested-With": "XMLHttpRequest" },
		})
			.then((res) => res.text())
			.then((html) => {
				$("#feedContainer").html(html);
			});
	}
	initDropdown() {
		$(".ui.dropdown").dropdown();

		$("#btnPrivateMessage").on("click", () => {
			$("#modalPrivateMessage").modal("show");
		});
	}
	destroy() {
		// lepas semua event wallchat
		$(document).off("submit", "#formPost");
		$(document).off("submit", ".formComment");
		$(document).off("submit", "#formPrivateMessage");
	}
}
