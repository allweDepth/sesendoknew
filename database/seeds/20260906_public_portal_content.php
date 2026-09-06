<?php
require_once __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();$actor='SEED_PUBLIC_PORTAL_20260906';$wilayah='76.01';$opd='1.03.0.00.0.00.01.0000';
$items=[
 ['berita','Pemantapan Program Infrastruktur PUPR 2026','Perencanaan','Pemerintah Kabupaten Pasangkayu memantapkan prioritas pembangunan infrastruktur tahun 2026 melalui penyelarasan target Renstra, Renja, dan DPA.','chart line'],
 ['berita','Koordinasi Penanganan Jalan Kabupaten','Bina Marga','Tim teknis melakukan koordinasi penanganan ruas jalan prioritas untuk meningkatkan keselamatan dan konektivitas masyarakat.','road'],
 ['berita','Evaluasi Kinerja Infrastruktur Triwulan','SAKIP','Evaluasi triwulanan menghubungkan kemajuan fisik, penyerapan anggaran, kendala, dan tindak lanjut setiap subkegiatan.','tasks'],
 ['data_teknis','Dashboard Kondisi Jalan dan Jembatan','Data Bina Marga','Data teknis membantu pembaca memahami kondisi, target, lokasi, serta perkembangan penanganan jalan dan jembatan kabupaten.','road'],
 ['data_teknis','Data Layanan Air Minum dan Sanitasi','Data Cipta Karya','Ringkasan cakupan layanan air minum dan rumah tinggal bersanitasi disajikan untuk mendukung perencanaan berbasis bukti.','tint'],
 ['data_teknis','Informasi Tata Ruang Kabupaten','Data Tata Ruang','Informasi pemanfaatan ruang, kawasan strategis, dan ruang terbuka hijau menjadi rujukan pembangunan yang tertib dan berkelanjutan.','map'],
 ['organisasi','Profil Dinas Pekerjaan Umum dan Penataan Ruang','Profil OPD','Dinas PUPR menyelenggarakan urusan pekerjaan umum dan penataan ruang melalui unit kerja yang saling terkoordinasi.','building'],
 ['organisasi','Bidang Bina Marga dan Sumber Daya Air','Unit Teknis','Unit teknis menangani konektivitas jalan, jembatan, jaringan irigasi, pengendalian banjir, dan konservasi sumber daya air.','sitemap'],
 ['organisasi','Bidang Cipta Karya dan Penataan Ruang','Unit Teknis','Unit ini mendukung layanan dasar permukiman sekaligus memastikan pemanfaatan ruang sesuai rencana tata ruang daerah.','city'],
 ['pelayanan','Informasi Persetujuan Kesesuaian Kegiatan Ruang','Layanan Tata Ruang','Masyarakat dapat memperoleh informasi persyaratan, alur konsultasi, dan tindak lanjut pelayanan tata ruang.','map marked alternate'],
 ['pelayanan','Layanan Informasi Infrastruktur Daerah','Informasi Publik','Permintaan informasi terkait program, lokasi kegiatan, dan data pembangunan ditangani melalui kanal pelayanan resmi.','info circle'],
 ['pelayanan','Pengaduan Jalan, Drainase, dan Infrastruktur','Pengaduan','Sampaikan lokasi, uraian kondisi, dan dokumentasi pendukung agar laporan dapat diverifikasi serta diteruskan ke unit teknis.','bullhorn']
];
$db->begin();try{$db->query('DELETE FROM halaman_berita WHERE username_insert=?',[$actor]);foreach($items as $i=>$x){[$type,$title,$category,$body,$icon]=$x;$slug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')).'-'.($i+1);$html='<div class="ui icon message"><i class="'.$icon.' icon"></i><div class="content"><h3>'.$title.'</h3><p>'.$body.'</p></div></div><p>'.$body.' Konten contoh ini dapat diperbarui melalui menu Berita oleh pengelola yang berwenang.</p>';$db->insert('halaman_berita',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'judul'=>$title,'slug'=>$slug,'jenis_halaman'=>$type,'aktif'=>1,'konten'=>$html,'gambar'=>'/assets/img/umum/bg.jpeg','keterangan'=>$category,'username_insert'=>$actor,'is_deleted'=>0]);}$db->commit();echo "PUBLIC PORTAL CONTENT SEEDED: ".count($items).PHP_EOL;}catch(Throwable $e){$db->rollback();throw $e;}
