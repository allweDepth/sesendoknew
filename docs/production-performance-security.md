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
