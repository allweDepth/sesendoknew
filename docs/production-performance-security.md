# Profil produksi dan hardening

## Web/PHP

- Wajib HTTPS dan arahkan HTTP ke HTTPS; aktifkan HSTS setelah sertifikat tervalidasi.
- PHP OPcache: `opcache.enable=1`, `memory_consumption=192`, `max_accelerated_files=20000`, `validate_timestamps=0` saat rilis atomik.
- Batasi request/upload pada 4 MB, waktu eksekusi 30 detik, nonaktifkan `display_errors`, simpan error log di luar `public`.
- Aktifkan gzip/Brotli dan cache immutable satu tahun untuk aset yang memakai nama berversi; HTML/API `no-store`.

## MariaDB

- Awali `innodb_buffer_pool_size` pada 60–70% RAM server database khusus, aktifkan slow query log 1 detik, dan tinjau mingguan.
- Jalankan migrasi indeks phase 27. Semua daftar memakai pagination/limit; jangan menghapus filter scope wilayah/OPD/tahun.
- User aplikasi hanya diberi SELECT/INSERT/UPDATE/DELETE pada schema aplikasi, tanpa FILE, SUPER, CREATE USER, atau akses schema lain.
- Backup terenkripsi harian, uji pemulihan berkala, dan jangan letakkan dump di web root.

## Operasional keamanan

- Secret enkripsi dan kredensial berasal dari environment, bukan repository. Rotasi berkala dan setelah insiden.
- WAF/rate limit pada login dan endpoint pesan; pantau 401/403/429, upload ditolak, dan anomali query.
- Folder `storage/uploads` tidak mengeksekusi PHP; file disajikan oleh controller setelah pemeriksaan pemilik dan realpath.
- Jalankan regression suite, vulnerability scan dependency, dan uji akses lintas-role sebelum setiap rilis.

## Pembaruan keamanan 4 Oktober 2026

Perubahan aplikasi yang sudah dipasang dan diuji di komputer lokal:

- Detail driver SQL, nama database/tabel, dan kegagalan koneksi hanya dicatat di log server. Respons Maps mengembalikan pesan aman dan status 500/503 untuk gangguan layanan. Pesan validasi trigger yang boleh ditampilkan dibatasi dengan daftar eksplisit.
- Login dibatasi 30 permintaan per IP dan 10 per identitas login dalam jendela 15 menit. Registrasi dibatasi 5 per IP per jam. Penghitung atomik MariaDB tetap berlaku ketika cookie/sesi diganti. Blokir berakhir sesuai jendela awal; percobaan tertolak tidak memperpanjangnya. Identitas bucket disimpan sebagai hash, tanpa password. Keterbatasan: beberapa alias username/email dapat memiliki bucket berbeda, dan pemblokiran identitas bisa disalahgunakan untuk mengganggu login korban sementara.
- Jalankan `database/migrations/20261004_phase61_auth_rate_limits.sql` sebelum merilis kode autentikasi. Saat tabel ini tidak tersedia, login gagal dengan aman; jangan mengabaikan kegagalan penghitung.
- Login, registrasi, logout, dan perubahan data memakai CSRF. Route yang mengubah data wajib POST; GET pada API lama tidak dapat dipakai untuk mutasi. Form lama yang sudah terbuka perlu dimuat ulang setelah rilis.
- Akun yang dinonaktifkan dan perubahan role/scope diperiksa kembali pada permintaan berikutnya. Sesi berakhir setelah 30 menit tanpa aktivitas atau 8 jam sejak login. Cookie memakai HttpOnly, SameSite, dan Secure pada HTTPS.
- Hash password tidak disimpan pada sesi login baru atau dikirim ke JavaScript. Data sesi dalam script menggunakan escaping JSON untuk mencegah penutupan tag script melalui isian pengguna.
- Halaman daftar struktur tabel hanya dapat dibuka administrator; endpoint backup tetap khusus super admin.
- Folder internal, dump, debug, hasil ekspor, dan script PHP selain front controller diblokir dari HTTP. Eksekusi script/HTML/SVG unggahan ditolak. Helper upload memakai MIME yang diizinkan, ukuran aktual, nama acak, dan penolakan traversal. Tanda tangan harus PNG yang teridentifikasi sebagai gambar.
- PhpSpreadsheet diperbarui dari 5.4.0 ke 5.10.0 setelah delapan advisori terdeteksi. Composer audit sesudah pembaruan tidak menemukan advisori; OSV untuk CryptoJS 4.2.0 juga tidak menemukan advisori. Hasil ini bukan jaminan tidak adanya kerentanan baru atau kesalahan pemakaian library.

### Kredensial dan izin file

`config/database.php` membaca `SESENDOK_DB_HOST`, `SESENDOK_DB_NAME`, `SESENDOK_DB_USER`, `SESENDOK_DB_PASSWORD`, dan `SESENDOK_DB_SOCKET`. Untuk pengembangan, `config/database.local.php` menyimpan konfigurasi privat dan diabaikan Git. File ini harus dapat dibaca oleh proses PHP, tetapi tidak dapat diunduh melalui HTTP. Di Mac lokal, file memakai mode 600 dengan ACL baca khusus `_www`; menyimpan file dengan mode 600 tanpa izin proses web menyebabkan login gagal.

Password yang dulu tersimpan di repository masih ada dalam riwayat Git dan harus dirotasi pada setiap instalasi yang menggunakannya. Jangan memublikasikan dump SQL atau folder unggahan dari repository. `MessageCryptoService` saat ini memiliki fallback kunci dari kredensial database: sebelum rotasi password, migrasikan ciphertext pesan ke `APP_MESSAGE_KEY` acak yang terpisah dan simpan backup terenkripsi agar pesan lama tidak hilang. Pemisahan konfigurasi pada rilis ini belum merotasi kredensial atau kunci pesan.

### Status server lokal dan batas pemeriksaan

MariaDB lokal telah dikonfigurasi di `/opt/homebrew/etc/my.cnf` dengan `bind-address=127.0.0.1` dan `local-infile=0`, lalu layanan dimulai ulang. Binding port 3306 dan variabel server telah diverifikasi. Backup konfigurasi sebelum perubahan disimpan di `/private/tmp/sesendok-mariadb-my.cnf-before-hardening`; pindahkan ke penyimpanan administratif bila perlu dipertahankan. Pengaturan lokal tidak otomatis berlaku pada server hosting.

Akun aplikasi lokal masih memiliki hak penuh pada schema aplikasi, karena fitur reset/restore menggunakan DDL. Untuk produksi, gunakan akun runtime dengan SELECT/INSERT/UPDATE/DELETE dan akun migrasi terpisah; pindahkan reset/restore DDL ke proses administratif sebelum mencabut haknya. Hak tidak dicabut diam-diam pada rilis ini karena akan merusak fitur tersebut.

Server produksi dan perimeter belum diperiksa: domain/hosting tidak tersedia dalam konteks ini. HTTPS, pembatasan SSH, firewall, WAF/rate limit untuk traffic umum, mitigasi DDoS di penyedia hosting, MFA administrator, antivirus/CDR unggahan, dan uji pemulihan backup masih memerlukan konfigurasi dan verifikasi di server tujuan. Apache lokal menggunakan HTTP; cookie Secure dan HSTS baru berlaku setelah HTTPS. Jangan memakai header `X-Forwarded-For` untuk bucket IP tanpa daftar proxy tepercaya. Akun publik registrasi tetap berstatus nonaktif sampai diaktifkan pengelola.

Validasi yang telah dijalankan: simulasi error SQL pada endpoint Maps tanpa menghapus tabel asli, rate limit dalam transaksi rollback, HTTP login/dashboard/logout dengan akun viewer sementara yang dihapus setelah pengujian, penolakan CSRF dan akses schema oleh viewer, blokir file sensitif melalui Apache, tes Maps, tes keamanan phase 27, dan baca/tulis ulang XLSX dengan library baru. Pengujian ini tidak mencakup pentest menyeluruh atau audit semua fitur dan OS.

Panduan acuan: [autentikasi OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html) dan [unggahan OWASP](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).

### Perbaikan regresi 4 Oktober 2026

Pembatasan metode menggunakan pasangan controller/action yang eksplisit. Pemeriksaan berdasarkan awalan nama sebelumnya salah menolak GET `uploadPage` dan `procurementDraft`. Halaman unggah dan pratinjau kembali bekerja; aksi penyimpanan tetap membutuhkan POST dan CSRF.

`bin/setup-upload-storage.sh` mengatur direktori unggahan privat dan publik dengan ACL khusus proses PHP serta pemilik proyek pada macOS, termasuk pewarisan izin untuk folder baru. Direktori tidak memakai izin 777. Untuk Linux, tentukan `WEB_GROUP` sesuai proses PHP. URL `/uploads/` diarahkan ke `public/uploads/`, sehingga tanda tangan dan gambar identitas dapat dibaca; folder privat dan ekstensi aktif tetap diblokir. Unggah tanda tangan memeriksa keberadaan direktori sebelum menyimpan.

Properti feature dan editor SHP ditempatkan di sidebar kanan: pada halaman Peta, keduanya berada dalam panel Layer OPD dengan satu area gulir; pada halaman pengaturan/unggah, keduanya berada pada panel pratinjau kanan. Tampilan editor dengan layer asli telah diperiksa pada Safari tanpa menyimpan perubahan geometri.

`python3 tests/security_http_regression_test.py` lulus 62 pemeriksaan terhadap Apache/MariaDB lokal: login/logout, 26 halaman, API baca, tabel anggaran, penolakan GET mutasi/CSRF, buat/edit SHP dan snapshot, unggah ZIP/DBF, foto profil, tanda tangan dan baca ulang gambar, serta blokir file internal. Akun dan unggahan uji dibersihkan otomatis. Tes Maps, geometri, penulis shapefile, respons error aman, XLSX phase 34, dan PDF pengadaan phase 56 juga lulus. Dua tes berbasis pencocokan teks (phase 26 E2E dan phase 35 navigasi) masih gagal; kegagalan yang sama dikonfirmasi pada kode sebelum hardening (`728823c`). Hasil ini tidak berarti seluruh fitur atau keamanan server produksi telah teruji.
