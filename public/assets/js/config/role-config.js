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
};
