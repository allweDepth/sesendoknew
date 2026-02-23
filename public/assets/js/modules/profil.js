const ProfilModule = {

    load() {
        $.post("/profil/load", function (res) {
            if (res.status === "success") {
                let d = res.data;

                for (let key in d) {
                    $('[name="' + key + '"]').val(d[key]);
                }

                $("#card_nama").text(d.nama);
                $("#card_type").text(d.type_user);
                $("#card_tahun").text(d.tahun);
                $("#card_login").text(d.tgl_login ?? "-");

                if (d.photo) {
                    $("#preview_photo").attr("src", "uploads/" + d.photo);
                }

                $(".ui.dropdown").dropdown("refresh");
            }
        }, "json");
    }
};