/*
|--------------------------------------------------------------------------
| MODULE : ANGGARAN
|--------------------------------------------------------------------------
| Modul ini mengontrol workflow halaman penganggaran
|
| Tahapan UI:
|
| 1. Sub Kegiatan
| 2. Rincian Belanja
|
| Engine yang digunakan:
|
| Table      : table-manager.js
| Form       : form-engine.js
| Flyout     : flyout-controller.js
|
| Modul ini TIDAK mengelola CRUD langsung.
| Semua CRUD dilakukan oleh dynamic engine.
|--------------------------------------------------------------------------
*/

class AnggaranModule {
	/*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    | Dipanggil otomatis oleh SPA Router
    | ketika container memiliki atribut:
    |
    | data-module="anggaran"
    |--------------------------------------------------------------------------
    */

	constructor(container) {
		/* simpan container utama modul */
		this.container = container;

		/*
        |--------------------------------------------------------------------------
        | Ambil nama tabel aktif
        |--------------------------------------------------------------------------
        |
        | Nilai berasal dari controller:
        |
        | renja_neo
        | renja_perubahan_neo
        | rka_neo
        | rka_perubahan_neo
        | dpa_neo
        | dppa_neo
        |
        */

		this.table = container.dataset.table;

		/*
        |--------------------------------------------------------------------------
        | Tahap dokumen
        |--------------------------------------------------------------------------
        |
        | renja
        | renja_p
        | rka
        | rka_p
        | dpa
        | dppa
        |
        */

		this.tahap = container.dataset.tahap;

		/* jalankan inisialisasi modul */
		this.init();
	}

	/*
    |--------------------------------------------------------------------------
    | INIT MODULE
    |--------------------------------------------------------------------------
    | Dipanggil pertama kali saat modul dimuat
    |--------------------------------------------------------------------------
    */

	init() {
		/* pasang event listener */
		this.bindEvents();

		/* inisialisasi tabel */
		this.initTable();
	}

	/*
    |--------------------------------------------------------------------------
    | INIT TABLE
    |--------------------------------------------------------------------------
    | Mengaktifkan table-manager.js
    | jika tabel Sub Kegiatan ditemukan
    |--------------------------------------------------------------------------
    */

	initTable() {
		/* cari tabel sub kegiatan di container */
		const table = this.container.querySelector("#tableSubKegiatan");

		/* jika tidak ada tabel maka keluar */
		if (!table) return;

		/*
        |--------------------------------------------------------------------------
        | aktifkan table manager
        |--------------------------------------------------------------------------
        | table-manager akan otomatis:
        |
        | load data
        | paging
        | sorting
        | filter
        |
        */

		if (typeof TableManager !== "undefined") {
			new TableManager(table);
		}
	}

	/*
    |--------------------------------------------------------------------------
    | BIND EVENTS
    |--------------------------------------------------------------------------
    | Semua interaksi user dipasang di sini
    |--------------------------------------------------------------------------
    */

	bindEvents() {
		const self = this;

		/*
        |--------------------------------------------------------------------------
        | EVENT : Klik baris SUB KEGIATAN
        |--------------------------------------------------------------------------
        | User memilih sub kegiatan
        | lalu membuka halaman rincian belanja
        |--------------------------------------------------------------------------
        */

		$(document).on("click", "#tableSubKegiatan tbody tr", function (e) {
			/*
            |--------------------------------------------------------------------------
            | Hindari jika klik pada tombol aksi
            |--------------------------------------------------------------------------
            */

			if ($(e.target).closest("button").length) return;

			/* ambil kode sub kegiatan dari atribut data */
			let sub = $(this).data("sub");

			/* jika tidak ada kode sub maka keluar */
			if (!sub) return;

			/* buka halaman rincian */
			self.openRincian(sub);
		});

		/*
        |--------------------------------------------------------------------------
        | EVENT : Tombol kembali
        |--------------------------------------------------------------------------
        | dari rincian ke sub kegiatan
        |--------------------------------------------------------------------------
        */

		$(document).on("click", "#btnBackSubKegiatan", function () {
			self.backToSub();
		});
	}

	/*
    |--------------------------------------------------------------------------
    | OPEN RINCIAN BELANJA
    |--------------------------------------------------------------------------
    | Memuat halaman rincian belanja
    | berdasarkan sub kegiatan
    |--------------------------------------------------------------------------
    */

	openRincian(sub) {
		const self = this;

		/*
        |--------------------------------------------------------------------------
        | request view rincian dari server
        |--------------------------------------------------------------------------
        */

		$.get(
			"/anggaran/rincian",
			{
				/* tabel aktif */
				table: this.table,

				/* kode sub kegiatan */
				sub: sub,

				/* tahap dokumen */
				tahap: this.tahap,
			},
			function (html) {
				/*
            |--------------------------------------------------------------------------
            | ganti isi container dengan halaman rincian
            |--------------------------------------------------------------------------
            */

				$("#anggaranContainer").html(html);
			},
		);
	}

	/*
    |--------------------------------------------------------------------------
    | BACK TO SUB KEGIATAN
    |--------------------------------------------------------------------------
    | kembali ke halaman utama
    |--------------------------------------------------------------------------
    */

	backToSub() {
		const self = this;

		/*
        |--------------------------------------------------------------------------
        | ambil kembali view sub kegiatan
        |--------------------------------------------------------------------------
        */

		$.get(
			"/anggaran/sub_kegiatan",
			{
				/* tabel aktif */
				table: this.table,

				/* tahap dokumen */
				tahap: this.tahap,
			},
			function (html) {
				/*
            |--------------------------------------------------------------------------
            | tampilkan kembali halaman sub kegiatan
            |--------------------------------------------------------------------------
            */

				$("#anggaranContainer").html(html);

				/*
            |--------------------------------------------------------------------------
            | inisialisasi ulang table manager
            |--------------------------------------------------------------------------
            */

				self.initTable();
			},
		);
	}
}
