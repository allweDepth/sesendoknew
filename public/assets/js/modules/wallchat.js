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

			fetch("/wallchat/store", {
				// kirim ke controller
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: "content=" + encodeURIComponent(content),
			})
				.then((res) => res.json())
				.then(() => {
					$("#formPost textarea").val("");

					this.reloadFeed();
				});
		});
	}
	reloadFeed() {
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
		$(document).off("submit", "#formPost"); // selector benar

		$(this.mainContainer).empty();
	}
}
