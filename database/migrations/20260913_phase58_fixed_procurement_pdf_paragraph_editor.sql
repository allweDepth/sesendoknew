-- Format yang dapat diedit disimpan sebagai data paragraf, bukan HTML.
UPDATE master_dokumen_pengadaan_bagian_neo b
JOIN master_dokumen_pengadaan_neo m ON m.id=b.master_id
SET b.is_deleted=1,b.tgl_update=NOW(),b.username_update='migration_phase58'
WHERE m.kode IN ('SURAT_PERJANJIAN','SSKK','SSUK') AND b.kode_bagian<>'ISI';

UPDATE master_dokumen_pengadaan_bagian_neo b
JOIN master_dokumen_pengadaan_neo m ON m.id=b.master_id
SET b.judul='Paragraf Surat Perjanjian',b.petunjuk_edit='Sunting setiap paragraf dengan toolbar Tata Naskah. HTML tidak digunakan.',
    b.format_data=JSON_OBJECT('editor','paragraphs','html_input',FALSE),
    b.isi_template=CAST(JSON_ARRAY(
      JSON_OBJECT('type','numbered','align','justify','text','Undang-Undang Nomor 2 Tahun 2017 tentang Jasa Konstruksi beserta perubahannya;'),
      JSON_OBJECT('type','numbered','align','justify','text','Kitab Undang-Undang Hukum Perdata, khususnya Buku III tentang perikatan;'),
      JSON_OBJECT('type','numbered','align','justify','text','Peraturan Presiden tentang Pengadaan Barang/Jasa Pemerintah beserta perubahannya;'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','PARA PIHAK MENERANGKAN TERLEBIH DAHULU BAHWA:'),
      JSON_OBJECT('type','alpha','align','justify','text','telah dilakukan proses pemilihan Penyedia sesuai Dokumen Pemilihan;'),
      JSON_OBJECT('type','alpha','align','justify','text','Pengguna Jasa telah menunjuk Penyedia melalui SPPBJ untuk melaksanakan {{nama_paket}};'),
      JSON_OBJECT('type','alpha','align','justify','text','Penyedia memiliki keahlian, tenaga, dan sumber daya untuk menyelesaikan pekerjaan;'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','Pasal 1 - ISTILAH DAN UNGKAPAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Istilah dan ungkapan dalam Surat Perjanjian ini mempunyai arti sebagaimana tercantum dalam lampiran Kontrak.'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','Pasal 2 - RUANG LINGKUP PEKERJAAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Ruang lingkup pekerjaan adalah {{nama_paket}}.'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','Pasal 3 - HARGA KONTRAK, SUMBER PEMBIAYAAN DAN PEMBAYARAN'),
      JSON_OBJECT('type','numbered','align','justify','text','Nilai Kontrak termasuk pajak adalah Rp{{nilai_kontrak}}.'),
      JSON_OBJECT('type','numbered','align','justify','text','Kontrak ini dibiayai dari {{sumber_dana}}.'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','Pasal 4 - DOKUMEN KONTRAK'),
      JSON_OBJECT('type','paragraph','align','justify','text','Surat Perjanjian, penawaran, daftar kuantitas dan harga, SSKK, SSUK, spesifikasi/KAK, gambar, jadwal, jaminan, berita acara, dan adendum merupakan satu kesatuan Kontrak.'),
      JSON_OBJECT('type','paragraph','align','center','style',JSON_ARRAY('bold'),'text','Pasal 5 - MASA KONTRAK'),
      JSON_OBJECT('type','paragraph','align','justify','text','Masa pelaksanaan dihitung sejak tanggal mulai kerja dalam SPMK sampai penyerahan pekerjaan sesuai kontrak.')
    ) AS CHAR),b.tgl_update=NOW(),b.username_update='migration_phase58',b.is_deleted=0
WHERE m.kode='SURAT_PERJANJIAN' AND b.kode_bagian='ISI';

UPDATE master_dokumen_pengadaan_bagian_neo b
JOIN master_dokumen_pengadaan_neo m ON m.id=b.master_id
SET b.judul='Butir Syarat Khusus Kontrak',b.petunjuk_edit='Format setiap baris: nomor pasal | ketentuan | data. Atur gaya paragraf dengan toolbar.',
    b.format_data=JSON_OBJECT('editor','paragraphs','layout','sskk_three_columns','html_input',FALSE),
    b.isi_template=CAST(JSON_ARRAY(
      JSON_OBJECT('type','paragraph','align','justify','text','4.1 & 4.2 | Korespondensi | Alamat para pihak mengikuti identitas Pengguna Jasa dan Penyedia dalam kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','4.2 & 5.1 | Wakil Sah Para Pihak | Wakil sah ditetapkan berdasarkan surat keputusan atau akta yang berlaku.'),
      JSON_OBJECT('type','paragraph','align','justify','text','6.3.b & 6.3.c | Pencairan Jaminan | Jaminan dicairkan dan disetorkan pada Kas Daerah sesuai ketentuan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','27.1 | Masa Pelaksanaan | Masa pelaksanaan dihitung sejak tanggal mulai kerja dalam SPMK.'),
      JSON_OBJECT('type','paragraph','align','justify','text','33.8 | Masa Pemeliharaan | Masa pemeliharaan dihitung sejak penyerahan pertama pekerjaan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','33.19 | Rencana Keselamatan Konstruksi | Penyedia wajib melaksanakan dan memutakhirkan RKK sesuai tingkat risiko serta kondisi lapangan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','33.22 | Rapat Persiapan Pelaksanaan Kontrak | Para pihak membahas organisasi kerja, jadwal, metode, mutu, keselamatan, dan tata cara pengendalian sebelum pekerjaan dimulai.'),
      JSON_OBJECT('type','paragraph','align','justify','text','35.1 | Gambar As Built dan Pedoman | Dokumen diserahkan paling lambat sesuai jangka waktu kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','36.7 | Akses ke Lokasi Kerja | Pengguna Jasa memberikan akses sesuai jadwal dan kebutuhan pelaksanaan yang disepakati.'),
      JSON_OBJECT('type','paragraph','align','justify','text','45.b | Pembayaran Tagihan | Tagihan diproses setelah dokumen lengkap dan tidak diperselisihkan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','49.(i) | Hak dan Kewajiban Penyedia | Penyedia melaksanakan pekerjaan, pelaporan, penyerahan hasil, dan keselamatan kerja sesuai kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','54.1(d) | Risiko Pengguna Jasa | Risiko yang menjadi tanggung jawab Pengguna Jasa ditetapkan secara tertulis sesuai dokumen kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','56.3 | Tanggung Jawab Penyedia | Penyedia bertanggung jawab atas pekerjaan sejak tanggal mulai kerja sampai serah terima sesuai ketentuan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','58 | Perlindungan Tenaga Kerja | Penyedia memenuhi ketentuan ketenagakerjaan, jaminan sosial, dan keselamatan bagi seluruh personel.'),
      JSON_OBJECT('type','paragraph','align','justify','text','65 | Pemeriksaan dan Pengujian | Pemeriksaan mutu dan pengujian dilaksanakan sesuai spesifikasi serta berita acara yang menjadi bagian kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','66.1(h) | Peristiwa Kompensasi | Peristiwa kompensasi dinilai berdasarkan bukti, dampak waktu, dan biaya yang dapat dipertanggungjawabkan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','70.1 | Besaran Uang Muka | Uang muka diberikan sesuai persentase dan jaminan yang ditetapkan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','70.2 | Pembayaran Prestasi | Pembayaran dilakukan berdasarkan prestasi pekerjaan yang telah diverifikasi.'),
      JSON_OBJECT('type','paragraph','align','justify','text','70.3(f) | Pembayaran Bahan dan Peralatan | Bahan atau peralatan yang dapat dibayar harus memenuhi persyaratan bukti kepemilikan, lokasi, mutu, dan jaminan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','70.4 | Denda Keterlambatan | Denda keterlambatan sebesar 1/1000 per hari dari bagian kontrak yang belum diserahterimakan.'),
      JSON_OBJECT('type','paragraph','align','justify','text','75.4 | Penyesuaian Harga | Penyesuaian harga hanya berlaku apabila bentuk, masa, dan ketentuan kontrak membolehkannya.'),
      JSON_OBJECT('type','paragraph','align','justify','text','78.2 | Penyelesaian Perselisihan | Para pihak memilih tahapan penyelesaian sengketa yang dicantumkan dalam kontrak.'),
      JSON_OBJECT('type','paragraph','align','justify','text','Lainnya | Penyelesaian Perselisihan | Perselisihan diselesaikan melalui musyawarah atau layanan penyelesaian sengketa yang disepakati.')
    ) AS CHAR),b.tgl_update=NOW(),b.username_update='migration_phase58',b.is_deleted=0
WHERE m.kode='SSKK' AND b.kode_bagian='ISI';

UPDATE master_dokumen_pengadaan_bagian_neo b
JOIN master_dokumen_pengadaan_neo m ON m.id=b.master_id
SET b.judul='Klausul Syarat Umum SPK',b.petunjuk_edit='Sunting setiap klausul sebagai paragraf; gunakan nomor, huruf, perataan, dan gaya dari toolbar.',
    b.format_data=JSON_OBJECT('editor','paragraphs','layout','numbered_clauses','html_input',FALSE),
    b.isi_template=CAST(JSON_ARRAY(
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','LINGKUP PEKERJAAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Penyedia wajib menyelesaikan pekerjaan sesuai volume, KAK/spesifikasi, mutu, waktu, dan harga dalam SPK.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','HUKUM YANG BERLAKU'),
      JSON_OBJECT('type','paragraph','align','justify','text','Keabsahan, interpretasi, dan pelaksanaan SPK didasarkan pada hukum Republik Indonesia.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','LARANGAN KORUPSI, KOLUSI, NEPOTISME, PENYALAHGUNAAN WEWENANG, DAN PENIPUAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Para pihak dilarang melakukan korupsi, kolusi, nepotisme, penyalahgunaan wewenang, benturan kepentingan, dan penipuan.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','HAK KEPEMILIKAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Hak kepemilikan atas hasil pekerjaan dan dokumen mengikuti ketentuan SPK.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','CACAT MUTU'),
      JSON_OBJECT('type','paragraph','align','justify','text','Penyedia wajib memperbaiki setiap cacat mutu dalam jangka waktu yang ditetapkan.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','PEMUTUSAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Pemutusan SPK dilakukan berdasarkan sebab, pemberitahuan, dan prosedur yang ditetapkan dalam ketentuan pengadaan.'),
      JSON_OBJECT('type','numbered','align','left','style',JSON_ARRAY('bold'),'text','PENYELESAIAN PERSELISIHAN'),
      JSON_OBJECT('type','paragraph','align','justify','text','Perselisihan diselesaikan terlebih dahulu melalui musyawarah dan selanjutnya melalui forum yang disepakati.')
    ) AS CHAR),b.tgl_update=NOW(),b.username_update='migration_phase58',b.is_deleted=0
WHERE m.kode='SSUK' AND b.kode_bagian='ISI';
