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
