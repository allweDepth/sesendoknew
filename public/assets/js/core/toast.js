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

    static show({ success = true, message = "" }) {

        if (!message) return;

        let colorClass = success ? "positive" : "negative";

        let html = `
            <div class="ui ${colorClass} message toast-global">
                <i class="close icon"></i>
                <div class="header">
                    ${success ? "Berhasil" : "Gagal"}
                </div>
                <p>${message}</p>
            </div>
        `;

        $("#toastContainer").append(html);

        $(".toast-global .close")
            .off("click")
            .on("click", function () {
                $(this).closest(".toast-global").remove();
            });

        setTimeout(function () {
            $(".toast-global").fadeOut(300, function () {
                $(this).remove();
            });
        }, 4000);
    }
}

// 🔥 WAJIB TAMBAH INI
window.ToastEngine = ToastEngine;