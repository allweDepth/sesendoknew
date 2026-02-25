class TableManager {
	constructor(config = {}) {
		this.state = config.state;
		this.ajax = config.ajax;

		this.tbody = config.tbody;
		this.pagination = config.pagination;

		this.currentPage = 1;
		this.limit = config.limit || 10;

		this.totalRows = 0;
		this.totalPages = 0;

		this.sortBy = null;
		this.sortDir = "asc";

		this.searchQuery = "";
		this.searchTimeout = null;

		this.data = [];
	}

	/**
	 * INIT
	 */
	init() {
		this.bindEvents();
		this.fetchData();
	}

	/**
	 * FETCH DATA
	 */
	fetchData() {
		this.renderLoader();

		this.ajax.request({
			url: "/" + this.state.module + "/load", // 🔥 INI WAJIB
			method: "POST",

			data: {
				module: this.state.module,
				action: "list",
				tbl: this.state.tbl,
				page: this.currentPage,
				limit: this.limit,
				search: this.searchQuery,
				sort_by: this.sortBy,
				sort_dir: this.sortDir,
			},

			success: (res) => {
				this.data = res.data || [];

				this.handlePagination(res.pagination || {});

				this.renderBody();
				this.renderPagination();
			},
		});
	}

	/**
	 * HANDLE PAGINATION
	 */
	handlePagination(pagination) {
		this.totalRows = pagination.total || 0;
		this.currentPage = pagination.page || 1;
		this.limit = pagination.limit || this.limit;

		this.totalPages = Math.ceil(this.totalRows / this.limit);
	}

	/**
	 * RENDER LOADER
	 */
	renderLoader() {
		$(this.tbody).html(`
			<tr>
				<td colspan="100%">
					<div class="ui active inline loader"></div>
				</td>
			</tr>
		`);
	}

	/**
	 * RENDER BODY ONLY
	 */
	renderBody() {
		switch (this.data.length > 0) {
			case true:
				let html = "";

				this.data.forEach((row) => {
					html += `<tr data-id="${row.id}">`;

					Object.values(row).forEach((val) => {
						html += `<td>${val ?? ""}</td>`;
					});

					html += `
						<td class="collapsing">
							<div class="ui mini basic icon buttons">
								<button class="ui button" data-action="edit">
									<i class="edit icon"></i>
								</button>
								<button class="ui button" data-action="delete">
									<i class="trash icon"></i>
								</button>
							</div>
						</td>
					`;

					html += `</tr>`;
				});

				$(this.tbody).html(html);
				break;

			default:
				$(this.tbody).html(`
					<tr>
						<td colspan="100%" class="center aligned">
							Tidak ada data
						</td>
					</tr>
				`);
				break;
		}
	}

	/**
	 * RENDER PAGINATION ONLY
	 */
	renderPagination() {
		switch (this.totalPages > 1) {
			case false:
				$(this.pagination).html("");
				return;
		}

		let html = `<div class="ui pagination menu">`;

		for (let i = 1; i <= this.totalPages; i++) {
			html += `
				<a class="item ${i === this.currentPage ? "active" : ""}"
				   data-page="${i}">
				   ${i}
				</a>
			`;
		}

		html += `</div>`;

		$(this.pagination).html(html);
	}

	/**
	 * BIND EVENTS
	 */
	bindEvents() {
		$(document).on("click", `${this.pagination} [data-page]`, (e) => {
			const page = parseInt($(e.currentTarget).data("page"));

			this.changePage(page);
		});

		$(document).on("click", `${this.tbody} [data-action]`, (e) => {
			const action = $(e.currentTarget).data("action");
			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id);
		});
	}

	/**
	 * CHANGE PAGE
	 */
	changePage(page) {
		switch (true) {
			case page < 1:
			case page > this.totalPages:
				return;
			default:
				this.currentPage = page;
				this.fetchData();
				break;
		}
	}

	/**
	 * HANDLE ACTION
	 */
	handleAction(action, id) {
		switch (action) {
			case "edit":
				$(document).trigger("table:edit", id);
				break;

			case "delete":
				this.deleteRow(id);
				break;
		}
	}

	/**
	 * DELETE
	 */
	deleteRow(id) {
		this.ajax.request({
			url: "/" + this.state.module + "/load", // 🔥 WAJIB
			method: "POST",

			data: {
				module: this.state.module,
				action: "delete",
				tbl: this.state.tbl,
				id_row: id,
			},

			success: () => {
				this.fetchData();
			},
		});
	}

	/**
	 * DESTROY
	 */
	destroy() {
		$(document).off("click", `${this.pagination} [data-page]`);
		$(document).off("click", `${this.tbody} [data-action]`);

		$(this.tbody).empty();
		$(this.pagination).empty();
	}
}
