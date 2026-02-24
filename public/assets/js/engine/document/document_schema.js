const DocumentSchema = {
	sk: {
		sections: [
			{
				key: "menimbang",
				label: "Menimbang",
				required: true,
				multiple: true,
			},
			{
				key: "mengingat",
				label: "Mengingat",
				required: true,
				multiple: true,
			},
			{
				key: "memutuskan",
				label: "Memutuskan",
				required: true,
				multiple: false,
			},
		],
	},

	surat_internal: {
		sections: [{ key: "isi", label: "ISI SURAT", type: "textarea" }],
	},
};
