# Maps

Menu Maps tersedia bagi pengguna yang sudah login. Peta menggunakan Leaflet untuk basemap XYZ dan layer SHP; Google Roadmap, Satellite, Hybrid, dan Terrain juga ditampilkan sebagai tile XYZ pada kanvas Leaflet yang sama tanpa API key.

Tampilan menu, form, dropdown pencarian, checkbox, tombol, dan tabel menggunakan Fomantic UI. Menu Maps dibagi menjadi tiga halaman: **Peta** menampilkan kanvas peta dan layer aktif tanpa form unggah; **Atur Layer SHP** mengatur visibilitas, simbologi, label, dan inspeksi field atribut; **Tambah/Unggah SHP** menangani unggahan sebagai alur terpisah.

Pada halaman **Peta**, klik feature pada layer untuk membuka panel properti berisi seluruh field atribut dari record DBF. Panel dapat ditutup, dan akan mengikuti layer yang sedang dipilih. Editor kategori menampilkan tabel yang dapat dibuka/ciutkan dan dicari, dengan pengaturan warna, jenis garis, dan ketebalan tiap kategori. Saat label diaktifkan, font, ukuran, warna, dan ketebalannya dapat diatur.

## Menyiapkan database

Jalankan `database/migrations/20261002_phase59_maps_layers.sql` pada database aplikasi sebelum membuka halaman Maps. File shapefile disimpan privat di `storage/uploads/maps/`, sedangkan metadata layer disimpan di tabel `maps_layers`.

## Role dan cakupan data

- `admin_opd`, `kepala_opd`, dan `pa_kpa` dapat mengunggah atau menghapus layer untuk `kd_wilayah` dan `kd_opd` pada akunnya.
- Pengguna OPD lain hanya dapat melihat layer pada OPD-nya.
- `admin_wilayah` dan `tapd` dapat melihat layer dalam wilayahnya, mengikuti pilihan OPD aktif.
- `super_admin` dapat melihat seluruh layer atau mengikuti scope OPD yang sedang dipilih.

Endpoint file memeriksa scope yang sama seperti daftar layer; file shapefile tidak disajikan sebagai aset publik.

## Mengunggah shapefile

Unggah satu file `.zip` berisi tepat satu `.shp` dan file pendamping dengan nama dasar yang sama. Paket menyimpan komponen `.shp`, `.shx`, `.dbf`, `.prj`, `.cpg`, `.qix`, `.sbn`, `.sbx`, `.ain`, `.aih`, `.atx`, `.ixs`, `.mxs`, dan metadata `.shp.xml`/`.shx.xml`/`.dbf.xml` yang dikenal. Komponen disimpan satu per satu di folder privat layer; jalur/nama dari dalam ZIP tidak digunakan untuk menentukan lokasi penyimpanan. Batas aplikasi adalah 32 MB per komponen, 96 MB total hasil ekstraksi dan 96 MB untuk file ZIP. PHP memerlukan ekstensi `zip`. Pastikan batas `upload_max_filesize`, `post_max_size`, serta batas body request pada web server mengizinkan unggahan tersebut.

Di **Atur Layer SHP**, pilih **Kategori berdasarkan field**, tentukan field DBF, lalu klasifikasikan nilai unik. Palet warna bisa disesuaikan per kategori; renderer, field, dan warna disimpan bersama gaya layer dan digunakan pada peta serta legenda. Maksimal 1.000 kategori per layer.

Geometri titik, multipoint, garis, dan poligon didukung. Koordinat dari `.prj` dikonversi ke WGS 84/EPSG:4326 untuk ditampilkan. Bila `.prj` tidak tersedia, data harus sudah menggunakan koordinat WGS 84.

Pada database yang sudah memasang migrasi Maps sebelumnya, jalankan `database/migrations/20261002_phase60_maps_shapefile_packages.sql` untuk menambah kapasitas metadata komponen shapefile.

## Basemap

OpenStreetMap, OpenTopoMap, CARTO Positron (terang), CARTO Dark Matter (gelap), Esri World Street Map, Esri Terrain/Topographic, dan Esri World Imagery (Satellite) dapat dipilih tanpa API key. Tile mengikuti atribusi provider yang ditampilkan pada peta dan ketentuan layanan/provider masing-masing.

Google Roadmap (`lyrs=m`), Satellite (`lyrs=s`), Hybrid (`lyrs=y`), dan Terrain (`lyrs=p`) menggunakan `https://mt{s}.google.com/vt/lyrs=…&x={x}&y={y}&z={z}` dengan subdomain 0–3, tanpa API key. Atribusi Google ditampilkan di peta. Semua basemap memakai Leaflet sehingga layer SHP, kategori, label, koordinat, dan kontrol zoom tetap tersedia saat berpindah basemap.

Endpoint Google XYZ ini bukan API resmi yang terdokumentasi; ketersediaannya bergantung pada provider dan penggunaan tetap mengikuti ketentuan Google. Tidak ada proxy atau cache tile di server aplikasi.

## Seleksi dan koordinat

Klik feature untuk memberi highlight kuning dan membuka seluruh atribut DBF. Klik area kosong, tutup panel properti, tekan Escape, atau nonaktifkan layer untuk menghapus seleksi dan menutup properti. Highlight tidak mengubah simbologi yang disimpan.

Klik peta menampilkan satu penanda lokasi beserta koordinat WGS84/EPSG:4326 (latitude/longitude bertanda +/−) dan UTM dalam meter. Zona UTM serta hemisfer N/S dipilih otomatis dan kode EPSG ditampilkan. UTM tersedia pada lintang 80°S–84°N. Zona khusus Norwegia/Svalbard mengikuti pembagian UTM. Titik klik berikutnya mengganti penanda sebelumnya.

## Pencarian lokasi

Semua halaman Maps menyediakan komponen Search Fomantic UI. Masukkan minimal tiga karakter nama lokasi/alamat dan tekan Enter atau tombol cari. Klik hasil, atau pilih dengan tombol panah lalu Enter, untuk memindahkan/zoom peta dan menampilkan penanda dengan koordinat WGS84 serta UTM. Data pencarian berasal dari OpenStreetMap melalui Photon, terpisah dari gambar XYZ, dan dapat dipakai bersama semua peta dasar termasuk Google. Hasil bergantung pada kelengkapan data OSM; ini bukan Google Places.

Permintaan hanya dikirim saat pengguna mencari, maksimum satu permintaan per detik per halaman; hingga 30 kata pencarian disimpan dalam cache memori halaman. Respons terlambat dibatalkan saat teks diganti atau halaman ditutup. Hasil kosong, gangguan jaringan, dan timeout ditampilkan di bawah kolom. Nama/alamat yang dicari dikirim ke penyedia; atribut maupun geometri SHP tidak dikirim.

Default `MAPS_SEARCH_ENDPOINT` adalah `https://photon.komoot.io/api/`. [Server publik Photon](https://github.com/komoot/photon#demo-server) mengizinkan penggunaan wajar, dapat membatasi permintaan, dan tidak menjamin ketersediaan. Untuk penggunaan ramai, atur variabel lingkungan ini ke server Photon sendiri atau proxy yang mengembalikan GeoJSON Photon. Origin HTTPS endpoint tersebut otomatis dimasukkan ke `connect-src` CSP; endpoint relatif menggunakan origin aplikasi. Attribution OpenStreetMap ditampilkan di kolom pencarian.

## Gambar dan edit SHP

Admin OPD, kepala OPD, dan PA/KPA dengan wilayah/OPD yang valid dapat membuka **Gambar SHP**, tombol pensil pada layer, atau **Edit SHP terpilih** pada panel properti. Hak akses penyimpanan diperiksa kembali di server dan selalu mengikuti OPD pengguna.

- **Aktif edit / Off edit**: Aktif edit mengizinkan perubahan geometri, field, dan atribut. Off edit mengunci perubahan serta tetap mempertahankan draft. Tidak ada penyimpanan otomatis ketika mengganti mode.
- **Simpan edit / Simpan SHP ke OPD** menyimpan draft. **Tidak simpan / Batal edit** membuang draft dan menampilkan layer asal kembali. Menutup dengan tombol X meminta konfirmasi pengabaian perubahan.
- **Titik, garis, poligon tertutup**: pilih jenis geometri, klik **Gambar feature**, kemudian klik peta. Titik selesai dengan satu klik. Garis membutuhkan minimal dua titik, poligon tiga titik; **Selesai** menutup ring poligon secara otomatis. **Undo titik** menghapus titik gambar terakhir; **Batalkan gambar** mengabaikan gambar yang sedang dibuat.
- Pilih feature pada editor untuk mengedit atribut. Geser node putih untuk mengubah posisi; klik node kecil di tengah sisi untuk menambah node; klik kanan node putih untuk menghapusnya. Jumlah node minimum garis/poligon tetap dijaga.
- **Lanjut awal/akhir garis** menambahkan titik ke garis yang dipilih. Untuk MultiLineString, klik node pada bagian garis yang akan dilanjutkan terlebih dahulu.
- **Divide garis di node** membagi garis pada node tengah terpilih. **Divide poligon** memakai dua klik sebagai garis lurus pemotong, diperpanjang melewati poligon. Atribut asal disalin ke bagian hasil. **Pisahkan multipart** memisahkan bagian MultiLineString, MultiPolygon, atau MultiPoint menjadi feature tersendiri.
- **Boolean poligon** menyediakan union, intersection, difference A−B, dan XOR antara feature A terpilih dan feature B dalam layer yang sama. Hasil mengganti A dan B serta menggunakan atribut A. Hasil kosong ditolak tanpa menghapus input. **Undo edit** menyimpan hingga sepuluh langkah geometri/field terakhir.
- **Divide layer berdasarkan field** menyimpan 2–50 layer baru menurut nilai field, dalam satu transaksi; layer asal tetap tersedia. Bila penyimpanan salah satu hasil gagal, seluruh hasil dibatalkan.

Field dasar mendukung teks (`C`), angka (`N`), boolean (`L`), dan tanggal (`D`). Nama field harus unik tanpa membedakan huruf besar/kecil, maksimal 10 karakter ASCII (huruf, angka, underscore). Maksimal 64 field; teks maksimal 254 byte UTF-8, angka maksimal 20 karakter dengan 0–8 desimal. Tanggal memakai YYYY-MM-DD. **Terapkan field** menerapkan perubahan schema dan mempertahankan nilai saat field diubah namanya; field yang dihapus tidak disertakan saat menyimpan.

Hasil editor adalah paket nyata `.shp`, `.shx`, `.dbf`, `.prj`, dan `.cpg`, dengan koordinat WGS84 dua dimensi dan DBF UTF-8. Unduh melalui tombol download pada layer. SHP titik, multipoint, garis/multigaris, dan poligon/multipoligon 2D dapat diedit; SHP Z/M dan null-shape perlu diekspor menjadi 2D sebelum diedit. Satu layer harus berisi satu jenis geometri SHP.

Penyimpanan edit menulis folder komponen baru dan mengganti metadata secara transaksional. Folder versi sebelumnya dipertahankan sebagai snapshot privat. Revision token menolak penyimpanan dari versi layer yang sudah berubah sehingga pengguna perlu memuat ulang. Tidak diperlukan migrasi database tambahan. Batas body request `/maps/geometry` adalah 40 MB; batas PHP `post_max_size` perlu mengizinkan request tersebut untuk layer berukuran besar.

Pustaka polygon-clipping 0.15.7 dibundel lokal beserta lisensi MIT di `public/assets/vendor/polygon-clipping/`; operasi geometri tidak mengirim data layer ke layanan pihak ketiga.
