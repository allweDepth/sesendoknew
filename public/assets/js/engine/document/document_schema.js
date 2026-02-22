const DocumentSchema = {

	sk: {
		sections: [
			{ key: "menimbang", label: "MENIMBANG", type: "list" },
			{ key: "mengingat", label: "MENGINGAT", type: "list" },
			{ key: "menetapkan", label: "MENETAPKAN", type: "list" },
			{ key: "tembusan", label: "TEMBUSAN", type: "list" }
		]
	},

	surat_internal: {
		sections: [
			{ key: "isi", label: "ISI SURAT", type: "textarea" }
		]
	}

};