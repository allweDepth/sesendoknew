<?php
return [
  'super_admin'=>['label'=>'Super Admin','scope'=>'semua wilayah','actions'=>['view','add','edit','delete','approve','manage_users','manage_roles']],
  'admin_wilayah'=>['label'=>'Admin Wilayah','scope'=>'satu wilayah','actions'=>['view','add','edit','delete','approve','manage_users']],
  'admin_opd'=>['label'=>'Admin OPD','scope'=>'satu OPD dan tahun','actions'=>['view','add','edit','delete','approve','manage_users','manage_roles']],
  'kepala_opd'=>['label'=>'Kepala OPD / Kepala Dinas','scope'=>'satu OPD dan tahun','actions'=>['view','add','edit','delete','approve','manage_users','manage_roles']],
  'pa_kpa'=>['label'=>'PA/KPA','scope'=>'satu OPD dan tahun','actions'=>['view','add','edit','delete','approve']],
  'ppk'=>['label'=>'Pejabat Pembuat Komitmen','scope'=>'subkegiatan yang ditugaskan','actions'=>['view','add','edit','approve_contract']],
  'pptk'=>['label'=>'PPTK','scope'=>'subkegiatan yang ditugaskan','actions'=>['view','add','edit']],
  'ppk_skpd'=>['label'=>'Pejabat Penatausahaan Keuangan','scope'=>'satu OPD dan tahun','actions'=>['view','add','edit','approve_finance']],
  'bendahara'=>['label'=>'Bendahara','scope'=>'satu OPD dan tahun','actions'=>['view','add','edit','spj']],
  'pejabat_pengadaan'=>['label'=>'Pejabat Pengadaan','scope'=>'paket yang ditugaskan','actions'=>['view','add','edit']],
  'staf_opd'=>['label'=>'Staf OPD','scope'=>'subkegiatan yang ditugaskan','actions'=>['view','add','edit']],
  'viewer'=>['label'=>'Viewer','scope'=>'data yang ditugaskan','actions'=>['view']],
];
