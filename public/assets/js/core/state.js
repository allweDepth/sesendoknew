/* =========================================================
	 GLOBAL STATE (UNIVERSAL)
	 ---------------------------------------------------------
	 → Menyimpan state aktif tabel & pagination
	 → Dipakai oleh TableManager dan modul lain
========================================================= */
const AppState = {

	halaman: 1, 
	// 🔹 Nomor halaman aktif untuk pagination.
	// Dipakai saat fetch() untuk kirim parameter halaman ke backend.
	// ⚠️ Kalau nanti kita rapikan, sebaiknya masuk ke object pagination.

	rows: 10, 
	// 🔹 Jumlah data per halaman.
	// Diubah saat user ganti dropdown countRow.
	// ⚠️ Sama seperti halaman, ini idealnya digabung ke dalam object pagination.

	module: null, 
	// 🔹 Module aktif saat ini.
	// Contoh: "referensi", "renstra", "standar_harga".
	// Menentukan UIConfig mana yang dipakai dan endpoint dynamic mana yang dipanggil.
	// ✅ Ini adalah salah satu core state paling penting.

	action: null, 
	// 🔹 Mode aksi saat ini.
	// Nilai bisa: "add", "edit", "detail", "import_xlsx".
	// Dipakai oleh FormContainerManager saat save dan open form.
	// ✅ Wajib ada untuk membedakan mode CRUD.

	tbl: null, 
	// 🔹 Tabel aktif di dalam module.
	// Contoh: "urusan", "renstra_neo".
	// Backend akan menerima ini sebagai parameter "tbl".
	// ✅ Core state kedua setelah module.

	cari: "", 
	// 🔹 Keyword pencarian.
	// Dipakai saat fetch() untuk filter server-side.
	// ⚠️ Secara arsitektur lebih bersih jika digabung ke pagination object.

	currentMenu: null, 
	// 🔹 Menyimpan menu terakhir yang aktif.
	// Biasanya dipakai untuk reset halaman saat pindah tabel.
	// ⚠️ Sebenarnya redundant dengan tbl.
	// Kalau load() sudah cek perubahan module/tbl, ini bisa dihapus.

	serverSources: [], 
	// 🔹 Daftar dropdown yang boleh fetch dari server.
	// Diisi saat form dibuka (berdasarkan UIConfig).
	// Digunakan oleh loadDropdowns() agar tidak semua dropdown auto-fetch.
	// ✅ Penting untuk kontrol dropdown dinamis.

	primaryKey: "id",
	// 🔹 Nama primary key tabel dari backend.
	// Bisa berubah tergantung meta.primary_key yang dikirim server.
	// Dipakai saat edit/delete untuk ambil id baris.
	// ✅ Wajib untuk fleksibilitas multi-table.

	page: "", 
	// 🔹 Menyimpan path halaman saat ini (window.location.pathname).
	// Dipakai untuk fallback UIConfig jika module tidak cocok.
	// ⚠️ Redundant dengan module.
	// Kalau arsitektur sudah stabil, ini bisa dihapus.

	set(key, value) {
        this[key] = value;
    },
	// 🔹 Helper untuk set state secara dinamis.
	// Contoh: AppState.set("module", "referensi")
	// ⚠️ Tidak terlalu diperlukan karena JS sudah fleksibel.
	// Berguna jika ingin logging perubahan state di masa depan.

    get(key) {
        return this[key];
    }
	// 🔹 Helper untuk ambil state secara dinamis.
	// Contoh: AppState.get("tbl")
	// ⚠️ Sama seperti set(), ini opsional.
};
AppState.role = window.USER_ROLE || "viewer";
// 🔹 Role user yang sedang login.
// Diambil dari variable global yang diinject PHP.
// Contoh: super_admin, admin_opd, viewer.
// Dipakai untuk menentukan tombol aksi (edit/delete) di ActionConfig.
// ✅ Wajib untuk kontrol hak akses frontend.
