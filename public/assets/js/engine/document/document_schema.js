const DocumentSchema = {

    sk: {
        sections: [
            { key: "menimbang", label: "MENIMBANG", type: "list" },
            { key: "mengingat", label: "MENGINGAT", type: "list" },
            { key: "menetapkan", label: "MENETAPKAN", type: "numbered" },
            { key: "asn", label: "ASN DITUGASKAN", type: "table" },
            { key: "tembusan", label: "TEMBUSAN", type: "list" }
        ]
    },

    surat_internal: {
        sections: [
            { key: "isi", label: "ISI SURAT", type: "textarea" }
        ]
    },

    akta: {
        sections: [
            { key: "pihak", label: "PIHAK TERLIBAT", type: "textarea" },
            { key: "isi", label: "ISI PERJANJIAN", type: "textarea" }
        ]
    },

    laporan: {
        sections: [
            { key: "isi", label: "ISI LAPORAN", type: "textarea" }
        ]
    }

};