/* =========================================================
   ROLE ENGINE
========================================================= */

const RoleConfig = {
	admin_wilayah: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
	super_admin: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
	admin_opd: {
		canAdd: true,
		canEdit: true,
		canDelete: false,
		canUpload: true,
	},
	viewer: {
		canAdd: false,
		canEdit: false,
		canDelete: false,
		canUpload: false,
	},
	kepala_opd: { canAdd:true, canEdit:true, canDelete:true, canUpload:true, canManageUsers:true, canApprove:true },
	pa_kpa: { canAdd:true, canEdit:true, canDelete:true, canUpload:true, canApprove:true },
	ppk: { canAdd:true, canEdit:true, canDelete:false, canUpload:true, canApprove:true },
	pptk: { canAdd:true, canEdit:true, canDelete:false, canUpload:true },
	ppk_skpd: { canAdd:true, canEdit:true, canDelete:false, canUpload:true, canApprove:true },
	bendahara: { canAdd:true, canEdit:true, canDelete:false, canUpload:true },
	pejabat_pengadaan: { canAdd:true, canEdit:true, canDelete:false, canUpload:true },
	staf_opd: { canAdd:true, canEdit:true, canDelete:false, canUpload:true },
};
