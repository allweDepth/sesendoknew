window.UIConfig = window.UIConfig || {};

UIConfig.pengaturan = {

    wilayah: {
        elements: [
            {
                tag: "fields",
                prop: {
                    children: [
                        {
                            tag: "fieldCalendar",
                            prop: {
                                label: "Awal Renja",
                                name: "awal_renja"
                            }
                        },
                        {
                            tag: "fieldCalendar",
                            prop: {
                                label: "Akhir Renja",
                                name: "akhir_renja"
                            }
                        },
                        {
                            tag: "fieldCalendar",
                            prop: {
                                label: "Awal DPPA",
                                name: "awal_dppa"
                            }
                        },
                        {
                            tag: "fieldCalendar",
                            prop: {
                                label: "Akhir DPPA",
                                name: "akhir_dppa"
                            }
                        }
                    ]
                }
            }
        ]
    }

};