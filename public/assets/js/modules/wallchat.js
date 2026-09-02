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

	async init() {
		this.bindEvents();
		try {
			if (window.E2EMessage) {
				await window.E2EMessage.init();
				await window.E2EMessage.decryptPage();
			}
		} catch (e) {
			console.warn("E2E:", e.message);
		}
		this.initDropdown();
		$(document)
			.off("click.wallComposer", "#openComposer")
			.on("click.wallComposer", "#openComposer", () =>
				$("#formPost").slideToggle(180).find("textarea").trigger("focus"),
			);
		$(document)
			.off("click.wallViews", "#btnOpenInbox, #btnBackToWall, #btnComposePrivate")
			.on("click.wallViews", "#btnOpenInbox", () => {
				$("#wallView").hide();
				$("#inboxView").fadeIn(160);
				window.scrollTo({ top: 0, behavior: "smooth" });
			})
			.on("click.wallViews", "#btnBackToWall", () => {
				$("#inboxView").hide();
				$("#wallView").fadeIn(160);
				window.scrollTo({ top: 0, behavior: "smooth" });
			})
			.on("click.wallViews", "#btnComposePrivate", () => $("#modalPrivateMessage").modal("show"));
	}

	bindEvents() {
		$(document).off("submit", "#formPost");

		$(document).on("submit", "#formPost", (e) => {
			e.preventDefault();

			const content = $('#formPost textarea[name="content"]').val();
			const data = new FormData(e.currentTarget);
			window.Ajax.request({
				url: "/wallchat/store",
				method: "POST",
				data,
				processData: false,
				contentType: false,

				success: () => {
					e.currentTarget.reset();
					$("#formPost").slideUp(150);

					this.reloadFeed();
				},
			});
		});
		$(document)
			.off("click", ".btnEditFeed")
			.on("click", ".btnEditFeed", (e) => {
				const b = $(e.currentTarget);
				$("#editPostForm [name=id]").val(b.data("id"));
				$("#editPostForm [name=content]").val(b.attr("data-content"));
				$("#editPostForm [name=theme]").dropdown("set selected", b.data("theme") || "default");
				$("#editPostModal").modal("show");
			});
		$(document)
			.off("click", "#savePostEdit")
			.on("click", "#savePostEdit", () => {
				const form = $("#editPostForm");
				if (window.FormValidation && !FormValidation.validate(form)) return;
				window.Ajax.request({
					url: "/wallchat/update",
					method: "POST",
					data: form.serialize(),
					success: () => {
						$("#editPostModal").modal("hide");
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

			window.Ajax.request({
				url: "/wallchat/comment",
				method: "POST",
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

		$(document).on("submit", "#formPrivateMessage", async (e) => {
			e.preventDefault();

			const formData = new FormData(e.currentTarget),
				receiver = formData.get("receiver_id"),
				plain = String(formData.get("content") || "");
			try {
				if (!window.E2EMessage) throw new Error("Web Crypto tidak tersedia");
				formData.set("e2e_payload", await window.E2EMessage.encrypt(plain, receiver));
				formData.set("content", "");
			} catch (err) {
				window.Toast?.error?.(err.message);
				return;
			}

			window.Ajax.request({
				url: "/wallchat/private",
				method: "POST",
				data: formData,
				processData: false,
				contentType: false,

				success: () => {
					$("#modalPrivateMessage").modal("hide");

					$("#formPrivateMessage textarea").val("");
				},
			});
		});
		$(document)
			.off("click", ".btnReadPrivate")
			.on("click", ".btnReadPrivate", (e) =>
				window.Ajax.request({
					url: "/wallchat/private/read",
					method: "POST",
					data: { id: $(e.currentTarget).data("id") },
					success: () => $(e.currentTarget).closest(".private-message-card").fadeOut(),
				}),
			);
		$(document)
			.off("click", ".btnDeletePrivate")
			.on("click", ".btnDeletePrivate", (e) =>
				window.Ajax.request({
					url: "/wallchat/private/delete",
					method: "POST",
					data: { id: $(e.currentTarget).data("id") },
					success: () => $(e.currentTarget).closest(".private-message-card").fadeOut(),
				}),
			);
		// delete feed
		$(document).off("click", ".btnDeleteFeed");

		$(document).on("click", ".btnDeleteFeed", (e) => {
			const id = $(e.currentTarget).data("id");

			if (!confirm("Hapus status ini?")) return;

			window.Ajax.request({
				url: "/wallchat/delete",
				method: "POST",
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
	}
	destroy() {
		// lepas semua event wallchat
		$(document).off("submit", "#formPost");
		$(document).off("submit", ".formComment");
		$(document).off("submit", "#formPrivateMessage");
	}
}
