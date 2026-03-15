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

	// renderLayout() {
	// 	if ($("#chat-box").length) return;
	// 	const html = `
	//           <div class="ui segment">
	//               <h3 class="ui header">Wallchat</h3>
	//               <div id="chat-box"></div>
	//               <form id="chat-form" class="ui form">
	//                   <div class="field">
	//                       <input type="text" name="message" placeholder="Ketik pesan...">
	//                   </div>
	//                   <button class="ui primary button">Kirim</button>
	//               </form>
	//           </div>
	//       `;

	// 	if ($("#chat-box").length) return;

	// 	$(this.mainContainer).append(html);
	// }

	bindEvents() {
		$(document).off("submit", "#formPost");

		$(document).on("submit", "#formPost", (e) => {
			e.preventDefault();

			const content = $('#formPost textarea[name="content"]').val();
			const token = $("#csrf_token").val();
			fetch("/wallchat/store", {
				// kirim ke controller
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "content=" + encodeURIComponent(content) + "&csrf_token=" + encodeURIComponent(token),
			})
				.then((res) => res.json())
				.then(() => {
					$("#formPost textarea").val("");

					this.reloadFeed();
				});
		});
		// komentar feed
		$(document).off("submit", ".formComment");

		$(document).on("submit", ".formComment", (e) => {
			e.preventDefault();

			const form = $(e.currentTarget);

			const feedId = form.data("id");

			const content = form.find('input[name="content"]').val();

			fetch("/wallchat/comment", {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "feed_id=" + encodeURIComponent(feedId) + "&content=" + encodeURIComponent(content),
			})
				.then((res) => res.json())
				.then(() => {
					form.find("input").val("");
				});
		});
		// kirim pesan pribadi
		$(document).off("submit", "#formPrivateMessage");

		$(document).on("submit", "#formPrivateMessage", (e) => {
			e.preventDefault();

			const receiver = $('input[name="receiver_id"]').val();
			const content = $('#formPrivateMessage textarea[name="content"]').val();

			fetch("/wallchat/privateMessage", {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "receiver_id=" + encodeURIComponent(receiver) + "&content=" + encodeURIComponent(content),
			})
				.then((res) => res.json())
				.then(() => {
					$("#modalPrivateMessage").modal("hide");

					$("#formPrivateMessage textarea").val("");
				});
		});
		// delete feed
		$(document).off("click", ".btnDeleteFeed");

		$(document).on("click", ".btnDeleteFeed", (e) => {
			const id = $(e.currentTarget).data("id");

			if (!confirm("Hapus status ini?")) return;

			fetch("/wallchat/delete", {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "id=" + encodeURIComponent(id),
			})
				.then((res) => res.json())
				.then(() => {
					this.reloadFeed();
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
