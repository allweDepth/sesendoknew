<?php

/**
 * Membaca naskah acuan syarat kontrak yang berasal dari berkas contoh pengguna.
 * Naskah disimpan sebagai teks agar tetap dapat dipecah menjadi paragraf yang
 * dapat disunting; PDF tidak dibentuk dari HTML mentah yang terlihat pengguna.
 */
final class ProcurementReferenceTermsService
{
    private const ROOT = __DIR__.'/../../resources/procurement/reference/';

    public static function rows(string $code, array $contract=[]): array
    {
        $code = strtoupper($code);
        if ($code === 'SURAT_PERJANJIAN') return self::contractRows($contract);
        if ($code === 'SSKK') return self::sskkRows($contract);
        if ($code === 'SSUK') return self::ssukRows($contract);
        return [];
    }

    private static function contractRows(array $c): array
    {
        $package = trim((string)($c['uraian_kontrak'] ?? '')) ?: '{{nama_paket}}';
        $days = trim((string)($c['waktu_pelaksanaan'] ?? '')) ?: 'sesuai SSKK';
        $maintenance = trim((string)($c['masa_pemeliharaan'] ?? '')) ?: 'sesuai SSKK';
        $rows = [];
        $add = static function(string $text,string $format='body',string $align='justify',array $style=[]) use (&$rows): void {
            $rows[]=['type'=>'paragraph','align'=>$align,'style'=>$style,'format'=>$format,'text'=>$text];
        };
        $add('Dan dengan memperhatikan:','body','left');
        $add('1. Undang-Undang Nomor 2 Tahun 2017 tentang Jasa Konstruksi;','hanging-list');
        $add('2. Kitab Undang-Undang Hukum Perdata (Buku III tentang perikatan);','hanging-list');
        $add('3. Peraturan Pemerintah Nomor 22 Tahun 2020 tentang Peraturan Pelaksanaan Undang-Undang Nomor 2 Tahun 2017 tentang Jasa Konstruksi beserta perubahannya;','hanging-list');
        $add('4. Peraturan Presiden Nomor 16 Tahun 2018 tentang Pengadaan Barang/Jasa Pemerintah beserta perubahannya;','hanging-list');
        $add('5. Ketentuan pengadaan khusus daerah apabila berlaku; dan','hanging-list');
        $add('6. Peraturan Menteri Pekerjaan Umum dan Perumahan Rakyat tentang Standar dan Pedoman Pengadaan Jasa Konstruksi melalui Penyedia.','hanging-list');
        $add('PARA PIHAK MENERANGKAN TERLEBIH DAHULU BAHWA:','clause-heading','center',['bold']);
        $add('(a) telah dilakukan proses pemilihan Penyedia yang telah sesuai dengan Dokumen Pemilihan;','hanging-list');
        $add('(b) Pengguna Jasa telah menunjuk Penyedia menjadi pihak dalam Kontrak ini melalui Surat Penunjukan Penyedia Barang/Jasa (SPPBJ) untuk melaksanakan Pekerjaan Konstruksi '.$package.' sebagaimana diterangkan dalam Syarat-Syarat Umum Kontrak yang merupakan satu kesatuan dalam Kontrak ini, selanjutnya disebut “Pekerjaan Konstruksi”;','hanging-list');
        $add('(c) Penyedia telah menyatakan kepada Pengguna Jasa memiliki keahlian profesional, tenaga kerja konstruksi, dan sumber daya teknis, serta telah menyetujui untuk melaksanakan Pekerjaan Konstruksi sesuai persyaratan dan ketentuan dalam Kontrak ini;','hanging-list');
        $add('(d) Pengguna Jasa dan Penyedia menyatakan memiliki kewenangan untuk menandatangani Kontrak ini dan mengikat pihak yang diwakili;','hanging-list');
        $add('(e) Pengguna Jasa dan Penyedia mengakui dan menyatakan bahwa sehubungan dengan penandatanganan Kontrak ini masing-masing pihak:','hanging-list');
        $add('1) telah dan senantiasa diberikan kesempatan untuk didampingi oleh advokat;','hanging-list');
        $add('2) menandatangani Kontrak ini setelah meneliti secara patut;','hanging-list');
        $add('3) telah membaca dan memahami secara penuh ketentuan Kontrak ini; dan','hanging-list');
        $add('4) telah mendapatkan kesempatan yang memadai untuk memeriksa dan mengonfirmasikan semua ketentuan dalam Kontrak ini beserta semua fakta dan kondisi terkait.','hanging-list');
        $add('Maka oleh karena itu, Pengguna Jasa dan Penyedia dengan ini bersepakat dan menyetujui untuk membuat perjanjian pelaksanaan paket Pekerjaan Konstruksi '.$package.'.');
        $add("Pasal 1\nISTILAH DAN UNGKAPAN",'clause-heading','center',['bold']);
        $add('Peristilahan dan ungkapan dalam Surat Perjanjian ini memiliki arti dan makna yang sama seperti yang tercantum dalam lampiran Surat Perjanjian ini.');
        $add("Pasal 2\nRUANG LINGKUP PEKERJAAN",'clause-heading','center',['bold']);
        $add('Ruang lingkup utama pekerjaan terdiri atas:');
        $add('1. '.$package,'hanging-list');
        $add('2. pekerjaan persiapan dan pekerjaan terkait sebagaimana Daftar Kuantitas dan Harga;','hanging-list');
        $add('3. pekerjaan utama sesuai spesifikasi teknis dan gambar;','hanging-list');
        $add('4. pekerjaan penunjang, keselamatan konstruksi, dan pengendalian mutu;','hanging-list');
        $add('5. pengujian, dokumentasi, serta penyerahan hasil pekerjaan; dan','hanging-list');
        $add('6. pekerjaan lain yang menjadi satu kesatuan sesuai dokumen Kontrak.','hanging-list');
        $add("Pasal 3\nHARGA KONTRAK, SUMBER PEMBIAYAAN DAN PEMBAYARAN",'clause-heading','center',['bold']);
        $add('(1) Nilai Kontrak termasuk Pajak Pertambahan Nilai (PPN) yang diperoleh berdasarkan total harga penawaran terkoreksi sebagaimana tercantum dalam Daftar Kuantitas dan Harga adalah sebesar Rp{{nilai_kontrak}}.','hanging-list');
        $add('(2) Kontrak ini dibiayai dari {{sumber_dana}}.','hanging-list');
        $add('(3) Pembayaran untuk Kontrak ini dilakukan ke rekening Penyedia yang sah dan tercantum dalam data kontrak setelah dokumen tagihan dinyatakan lengkap.','hanging-list');
        $add("Pasal 4\nDOKUMEN KONTRAK",'clause-heading','center',['bold']);
        $add('(1) Kelengkapan dokumen berikut merupakan satu kesatuan dan bagian yang tidak terpisahkan dari Kontrak ini: adendum Kontrak (apabila ada), Surat Perjanjian, Surat Penawaran, Daftar Kuantitas dan Harga, Syarat-Syarat Umum Kontrak, Syarat-Syarat Khusus Kontrak beserta lampirannya berupa lampiran daftar harga satuan timpang, subpenyedia, personel manajerial dan peralatan utama, Rencana Keselamatan Konstruksi, spesifikasi teknis, gambar, SPPBJ, jadwal pelaksanaan, jaminan, Berita Acara Rapat Persiapan Penandatanganan Kontrak, serta Berita Acara Rapat Persiapan Pelaksanaan Kontrak.','hanging-list');
        $add('(2) Jika terjadi pertentangan antarketentuan dokumen, yang berlaku adalah dokumen yang lebih tinggi menurut urutan hierarki berikut:','hanging-list');
        foreach(['a) adendum Surat Perjanjian (apabila ada);','b) Surat Perjanjian;','c) Daftar Kuantitas/Keluaran dan Harga hasil negosiasi (apabila ada negosiasi);','d) Daftar Kuantitas/Keluaran dan Harga terkoreksi (apabila ada koreksi aritmatik);','e) surat penawaran;','f) syarat-syarat khusus Kontrak;','g) syarat-syarat umum Kontrak;','h) spesifikasi teknis; dan','i) gambar-gambar.'] as $line)$add($line,'hanging-list');
        $add("Pasal 5\nMASA KONTRAK",'clause-heading','center',['bold']);
        $add('(1) Masa Kontrak adalah jangka waktu berlakunya Kontrak ini terhitung sejak tanggal penandatanganan Kontrak sampai dengan Tanggal Penyerahan Akhir Pekerjaan.','hanging-list');
        $add('(2) Masa Pelaksanaan ditentukan dalam Syarat-Syarat Khusus Kontrak, dihitung sejak Tanggal Mulai Kerja dalam SPMK sampai dengan Tanggal Penyerahan Pertama Pekerjaan selama '.$days.' hari kalender.','hanging-list');
        $add('(3) Masa Pemeliharaan ditentukan dalam Syarat-Syarat Khusus Kontrak, dihitung sejak Tanggal Penyerahan Pertama Pekerjaan sampai dengan Tanggal Penyerahan Akhir Pekerjaan selama '.$maintenance.' hari kalender.','hanging-list');
        $add('Dengan demikian, Pengguna Jasa dan Penyedia telah bersepakat untuk menandatangani Kontrak ini pada tanggal tersebut di atas dan melaksanakan Kontrak sesuai ketentuan peraturan perundang-undangan di Republik Indonesia. Kontrak dibuat dalam 2 (dua) rangkap, masing-masing dibubuhi meterai, mempunyai kekuatan hukum yang sama dan mengikat bagi para pihak; rangkap lain dapat diperbanyak sesuai kebutuhan tanpa dibubuhi meterai.');
        return $rows;
    }

    private static function ssukRows(array $contract): array
    {
        $kind = strtoupper((string)($contract['jenis_pengadaan'] ?? ''));
        $consultancy = str_contains($kind, 'KONSULTANSI');
        $file = self::ROOT.($consultancy ? 'ssuk-konsultansi.txt' : 'ssuk-konstruksi.txt');
        $text = is_file($file) ? (string)file_get_contents($file) : '';
        if ($text === '') return [];

        $text = str_replace(["\r", "\f", "\u{FFFC}"], ["", "", ""], $text);
        $lines = preg_split('/\n/u', $text) ?: [];
        $blocks = [];
        $current = null;
        foreach ($lines as $raw) {
            $line = trim(preg_replace('/[ \t]+/u', ' ', $raw) ?? '');
            if ($line === '') {
                if ($current !== null && ($current['format'] ?? '') !== 'clause-heading') {
                    $blocks[] = $current;
                    $current = null;
                }
                continue;
            }
            if (preg_match('/^(?:jdih\.pu\.go\.id|-\s*\d+\s*-|syarat umum(?: spk)?\s+hal\s+\d+)/iu', $line)) continue;
            if (in_array(strtoupper($line), ['SYARAT UMUM', 'SURAT PERINTAH KERJA (SPK)'], true)) continue;

            $isHeading = preg_match('/^(\d+)\.\s+(.+)$/u', $line, $m) === 1 && preg_match('/^[A-Z0-9 ,()\/\-&.]+$/u', $m[2]);
            $isList = preg_match('/^(?:[a-z]\.|\d+\)|[ivx]+\.)\s+/iu', $line) === 1;
            if ($isHeading || $isList) {
                if ($current !== null) $blocks[] = $current;
                $current = [
                    'type' => 'paragraph',
                    'align' => $isHeading ? 'left' : 'justify',
                    'style' => $isHeading ? ['bold'] : [],
                    'format' => $isHeading ? 'clause-heading' : 'hanging-list',
                    'text' => $line,
                ];
                continue;
            }
            if ($current === null) {
                $current = ['type'=>'paragraph','align'=>'justify','style'=>[],'format'=>'body','text'=>$line];
                continue;
            }
            // Judul panjang pada dokumen acuan dapat berlanjut ke baris berikutnya.
            if (($current['format'] ?? '') === 'clause-heading' && preg_match('/^[A-Z0-9 ,()\/\-&.]+$/u', $line)) {
                $current['text'] .= ' '.$line;
                continue;
            }
            if (($current['format'] ?? '') !== 'body' && ($current['format'] ?? '') !== 'hanging-list') {
                $blocks[] = $current;
                $current = ['type'=>'paragraph','align'=>'justify','style'=>[],'format'=>'body','text'=>$line];
            } else {
                $current['text'] .= ' '.$line;
            }
        }
        if ($current !== null) $blocks[] = $current;
        return array_values(array_filter($blocks, static fn($row) => trim((string)($row['text'] ?? '')) !== ''));
    }

    private static function sskkRows(array $c): array
    {
        $days = trim((string)($c['waktu_pelaksanaan'] ?? '')) ?: '200';
        $ppk = trim((string)($c['nama_ppk_resmi'] ?? $c['nama_ppk'] ?? '')) ?: '{{nama_ppk}}';
        $provider = trim((string)($c['direktur'] ?? $c['nama_penyedia_resmi'] ?? $c['nama_penyedia'] ?? '')) ?: '{{nama_penyedia}}';
        $providerAddress = trim((string)($c['alamat_penyedia'] ?? $c['alamat'] ?? '')) ?: '-';
        $opd = trim((string)($c['nama_opd'] ?? '')) ?: '{{nama_opd}}';
        $rows = [
            ['4.1 & 4.2','Korespondensi',"Alamat Para Pihak sebagai berikut:\n\nSatuan Kerja PPK : {$opd}\nNama : {$ppk}\nE-mail : ................................\n\nPenyedia : {{nama_penyedia}}\nNama : {$provider}\nAlamat : {$providerAddress}\nE-mail : ................................"],
            ['4.2 & 5.1','Wakil Sah Para Pihak',"Wakil Sah Para Pihak sebagai berikut:\n\nUntuk PPK:\nNama : {$ppk}\nBerdasarkan Surat Keputusan pejabat yang berwenang tentang pengangkatan PPTK dan tim teknis pada {{nama_opd}}.\n\nUntuk Penyedia:\nNama : {$provider}"],
            ['6.3.b & 6.3.c / 44.4 & 44.6','Pencairan Jaminan','Jaminan dicairkan dan disetorkan pada Kas Daerah Kabupaten/Kota sesuai satuan kerja Pengguna Jasa.'],
            ['27.1','Masa Pelaksanaan',"Masa Pelaksanaan selama {$days} hari kalender terhitung sejak Tanggal Mulai Kerja yang tercantum dalam SPMK."],
            ['33.8','Masa Pemeliharaan','Masa Pemeliharaan berlaku selama 360 (tiga ratus enam puluh) hari kalender terhitung sejak Tanggal Penyerahan Pertama Pekerjaan (PHO).'],
            ['33.19','Serah Terima Sebagian Pekerjaan (Bagian Kontrak)',"Dalam Kontrak ini diberlakukan serah terima pekerjaan sebagian atau secara parsial untuk bagian sebagai berikut:\n1. Bagian pekerjaan yang ditetapkan dalam dokumen kontrak;\n2. Bagian pekerjaan lain yang disetujui Pengguna Jasa."],
            ['33.22','Masa Pemeliharaan untuk Serah Terima Sebagian Pekerjaan (Bagian Kontrak)','Tidak ditentukan, kecuali dinyatakan lain dalam dokumen kontrak.'],
            ['35.1','Gambar As Built dan Pedoman Pengoperasian dan Perawatan/Pemeliharaan','Gambar “As built” dan/atau pedoman pengoperasian dan perawatan/pemeliharaan harus diserahkan paling lambat 3 (tiga) hari kalender setelah Tanggal Penyerahan Pertama Pekerjaan.'],
            ['36.7','Penyesuaian Harga','Penyesuaian harga tidak diberikan, kecuali ditetapkan lain sesuai jenis dan masa kontrak.'],
            ['45.b','Pembayaran Tagihan','Batas akhir waktu yang disepakati untuk penerbitan SPP oleh Pengguna Jasa untuk pembayaran tagihan angsuran adalah 3 (tiga) hari kerja terhitung sejak tagihan dan kelengkapan dokumen penunjang yang tidak diperselisihkan diterima oleh Pengguna Jasa.'],
            ['49.(i)','Hak dan Kewajiban Penyedia',"Hak dan kewajiban Penyedia:\n1. menerima pembayaran untuk pelaksanaan pekerjaan sesuai harga yang telah ditentukan dalam Kontrak;\n2. meminta fasilitas sarana dan prasarana dari PPK untuk kelancaran pelaksanaan pekerjaan sesuai ketentuan Kontrak;\n3. melaporkan pelaksanaan pekerjaan secara periodik kepada PPK;\n4. melaporkan penggunaan produksi dalam negeri/TKDN secara periodik kepada PPK;\n5. melaksanakan dan menyelesaikan pekerjaan sesuai jadwal yang ditetapkan dalam Kontrak;\n6. melaksanakan pekerjaan secara cermat, akurat, dan penuh tanggung jawab dengan menyediakan tenaga kerja, bahan, peralatan, angkutan, serta pekerjaan permanen maupun sementara yang diperlukan;\n7. memberikan keterangan yang diperlukan untuk pemeriksaan pelaksanaan oleh PPK;\n8. menyerahkan hasil pekerjaan sesuai jadwal penyerahan dalam Kontrak;\n9. menerapkan Sistem Manajemen Keselamatan dan Kesehatan Kerja untuk melindungi tempat kerja, masyarakat, lingkungan, dan harta benda dari dampak kegiatan Penyedia."],
            ['54.1.(d)','Tindakan Penyedia yang Mensyaratkan Persetujuan Pengguna Jasa',"Tindakan lain oleh Penyedia yang memerlukan persetujuan PPK:\n1) perubahan jadwal;\n2) perubahan lingkup pekerjaan;\n3) perubahan spesifikasi teknis;\n4) pekerjaan tambah kurang;\n5) perubahan volume pekerjaan;\n6) perubahan bahan/material disertai contoh material yang diajukan Penyedia;\n7) detail gambar pelaksanaan (shop drawings); dan\n8) pembangunan konstruksi fisik berdasarkan dokumen yang wajib mendapatkan persetujuan PPK sebagaimana diatur lebih lanjut dalam SSUK."],
            ['56.3','Tindakan Penyedia yang Mensyaratkan Persetujuan Pengawas Pekerjaan','Tindakan lain oleh Penyedia yang memerlukan persetujuan Pengawas Pekerjaan adalah seluruh pekerjaan struktur maupun arsitektur yang akan dikerjakan, disertai request sheet pekerjaan.'],
            ['58','Kepemilikan Dokumen',"Penyedia diperbolehkan menggunakan salinan dokumen dan piranti lunak yang dihasilkan dari Pekerjaan Konstruksi dengan pembatasan harus seizin PPK. Penggandaan dokumen selebihnya dibebankan kepada Penyedia."],
            ['65','Fasilitas','PPK akan memberikan fasilitas sebagaimana dicantumkan dalam dokumen kontrak. Apabila tidak dicantumkan, berarti tidak ada fasilitas khusus.'],
            ['66.1.(h)','Peristiwa Kompensasi',"Termasuk Peristiwa Kompensasi yang dapat diberikan kepada Penyedia:\n1) PPK mengubah jadwal yang memengaruhi pelaksanaan pekerjaan;\n2) keterlambatan pembayaran kepada Penyedia;\n3) PPK tidak memberikan gambar, spesifikasi, dan/atau instruksi sesuai jadwal yang dibutuhkan;\n4) Penyedia belum dapat masuk ke lokasi sesuai jadwal kontrak;\n5) PPK menginstruksikan pengujian tambahan dan hasilnya tidak menemukan kerusakan, kegagalan, atau penyimpangan;\n6) PPK memerintahkan penundaan pelaksanaan pekerjaan;\n7) PPK memerintahkan penanganan kondisi tak terduga yang disebabkan oleh PPK."],
            ['70.1 (e)','Besaran Uang Muka','Uang muka diberikan paling tinggi sebesar 30% (tiga puluh persen) dari Harga Kontrak, atau sesuai persentase yang ditetapkan dalam dokumen kontrak.'],
            ['70.2 (d)','Pembayaran Prestasi Pekerjaan',"Pembayaran prestasi pekerjaan dilakukan secara termin/bulanan. Dokumen penunjang tagihan pembayaran prestasi pekerjaan meliputi:\n1. Laporan Harian;\n2. Laporan Mingguan;\n3. Laporan Bulanan; dan\n4. Gambar/foto dokumentasi pekerjaan."],
            ['70.3 (f)','Pembayaran Bahan dan/atau Peralatan',"Penentuan dan besaran pembayaran untuk bahan dan/atau peralatan yang menjadi bagian permanen pekerjaan utama (material on site): bahan on site ditentukan dari persentase jumlah bahan dan alat terhadap pekerjaan bersangkutan apabila diizinkan oleh PPK."],
            ['70.4 (c)','Denda akibat Keterlambatan','Besar denda keterlambatan untuk setiap hari keterlambatan adalah 1/1000 (satu perseribu) dari harga Bagian Kontrak yang belum diserahterimakan apabila ditetapkan serah terima parsial, sebelum PPN.'],
            ['75.4','Perbaikan Cacat Mutu','Denda keterlambatan akibat Cacat Mutu untuk setiap hari keterlambatan adalah 1/1000 (satu perseribu) dari biaya perbaikan cacat mutu. Jangka waktu perbaikan sesuai perkiraan waktu yang diperlukan dan ditetapkan oleh PPK.'],
            ['78.2','Umur Konstruksi dan Pertanggungan terhadap Kegagalan Bangunan',"a) Bangunan hasil pekerjaan memiliki Umur Konstruksi selama 3 (tiga) tahun sejak Tanggal Penyerahan Akhir Pekerjaan dengan mempertimbangkan pemeliharaan.\nb) Pertanggungan terhadap Kegagalan Bangunan ditetapkan selama 3 (tiga) tahun sejak Tanggal Penyerahan Akhir Pekerjaan."],
            ['Lainnya','Penyelesaian Perselisihan/Sengketa','Dalam hal musyawarah untuk mufakat tidak tercapai, para pihak sepakat menyelesaikan perselisihan/sengketa melalui Layanan Penyelesaian Sengketa yang diselenggarakan oleh LKPP.'],
        ];
        return array_map(static fn($r) => ['type'=>'paragraph','align'=>'left','style'=>[],'format'=>'sskk-row','text'=>implode(' | ', $r)], $rows);
    }
}
