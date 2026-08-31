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
			window.Ajax.request({url:"/wallchat/store",method:"POST",
				data: {
					content: content,
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

			window.Ajax.request({url:"/wallchat/comment",method:"POST",
				data: {
					feed_id: feedId,
					content: content,
				},

					success: () => {
					form.find("input").val("");
					this.reloadFeed();
				},
			});
		});
		// kirim pesan pribadi
		$(document).off("submit", "#formPrivateMessage");

		$(document).on("submit", "#formPrivateMessage", (e) => {
			e.preventDefault();

			const formData = new FormData(e.currentTarget);

			window.Ajax.request({url:"/wallchat/private",method:"POST",
				data: formData, processData:false, contentType:false,

				success: () => {
					$("#modalPrivateMessage").modal("hide");

					$("#formPrivateMessage textarea").val("");
				},
			});
		});
		$(document).off("click", ".btnReadPrivate").on("click", ".btnReadPrivate", e=>window.Ajax.request({url:"/wallchat/private/read",method:"POST",data:{id:$(e.currentTarget).data("id")},success:()=>$(e.currentTarget).closest(".private-message-card").fadeOut()}));
		$(document).off("click", ".btnDeletePrivate").on("click", ".btnDeletePrivate", e=>window.Ajax.request({url:"/wallchat/private/delete",method:"POST",data:{id:$(e.currentTarget).data("id")},success:()=>$(e.currentTarget).closest(".private-message-card").fadeOut()}));
		// delete feed
		$(document).off("click", ".btnDeleteFeed");

		$(document).on("click", ".btnDeleteFeed", (e) => {
			const id = $(e.currentTarget).data("id");

			if (!confirm("Hapus status ini?")) return;

			window.Ajax.request({url:"/wallchat/delete",method:"POST",
				data: {
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

		fetch(window.appUrl ? window.appUrl("/wallchat/feed") : "/wallchat/feed", {
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
