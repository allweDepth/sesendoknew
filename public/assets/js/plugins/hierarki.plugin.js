(function () {

    if (typeof FormContainerManager === "undefined") return;

    FormContainerManager.registerPlugin(
        "referensi.*",
        function ({ container, state }) {

            if (state.jenis !== "referensi") return;

            const map = {
                bidang: {
                    parentField: "kode_urusan",
                    parentTable: "urusan"
                },
                program: {
                    parentField: "kode_bidang",
                    parentTable: "bidang"
                },
                kegiatan: {
                    parentField: "kode_program",
                    parentTable: "program"
                },
                sub_kegiatan: {
                    parentField: "kode_kegiatan",
                    parentTable: "kegiatan"
                }
            };

            const config = map[state.tbl];

            if (!config) return;

            container.on("change", `[name="${config.parentField}"]`, function () {

                const parentValue = $(this).val();

                loadDropdown(
                    config.parentField,
                    config.parentTable,
                    { kode: parentValue }
                );
            });

        }
    );

})();
