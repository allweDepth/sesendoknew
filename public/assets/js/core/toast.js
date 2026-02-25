/* =========================================================
   TOAST ENGINE
   ---------------------------------------------------------
   Bertugas menampilkan notifikasi global (success / error)
   berdasarkan response backend atau event manual.

   Dipanggil otomatis oleh AjaxEngine jika response
   mengandung properti:
   {
      success: true/false,
      message: "..."
   }

   Tidak ada perubahan logika dari file asli.
========================================================= */

class ToastEngine {

	/**
	 * Method utama untuk menampilkan notifikasi
	 *
	 * @param {Object} options
	 * @param {Boolean} options.success - status berhasil / gagal
	 * @param {String} options.message - pesan yang ditampilkan
	 */
	static show({ success = true, message = "" }) {

		// Jika tidak ada pesan, tidak perlu tampilkan apa pun
		if (!message) return;

		/**
		 * Tentukan warna berdasarkan status
		 * true  -> hijau
		 * false -> merah
		 */
		let colorClass = success ? "positive" : "negative";

		/**
		 * Struktur HTML toast menggunakan UI framework
		 * (tetap mempertahankan struktur lama)
		 */
		let html = `
			<div class="ui ${colorClass} message toast-global">
				<i class="close icon"></i>
				<div class="header">
					${success ? "Berhasil" : "Gagal"}
				</div>
				<p>${message}</p>
			</div>
		`;

		/**
		 * Tambahkan ke body
		 * Tidak diubah agar kompatibel
		 */
		$("body").append(html);

		/**
		 * Aktifkan tombol close (x)
		 */
		$(".toast-global .close")
			.off("click")
			.on("click", function () {
				$(this).closest(".toast-global").remove();
			});

		/**
		 * Auto remove setelah 4 detik
		 */
		setTimeout(function () {
			$(".toast-global").fadeOut(300, function () {
				$(this).remove();
			});
		}, 4000);
	}
}