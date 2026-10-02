# Maps

Menu Maps tersedia bagi pengguna yang sudah login. Peta menggunakan Leaflet untuk basemap XYZ dan layer SHP; Google Maps ditampilkan melalui Google Maps JavaScript API resminya.

Menu Maps dibagi menjadi tiga halaman: **Peta** menampilkan kanvas peta dan layer aktif tanpa form unggah; **Atur Layer SHP** mengatur visibilitas, simbologi, label, dan inspeksi field atribut; **Tambah/Unggah SHP** menangani unggahan sebagai alur terpisah.

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

Google Roadmap, Satellite, Hybrid, dan Terrain memerlukan Google Maps Platform API key serta aktivasi Maps JavaScript API dan billing yang sesuai. Set variabel lingkungan `GOOGLE_MAPS_API_KEY` pada proses PHP; Google menyediakan peta ketika pilihan tersebut digunakan. Batasi key berdasarkan referrer aplikasi dan API yang diizinkan. Jangan simpan key pada source control.

Jangan memakai URL tile XYZ Google secara langsung: akses tile peta Google harus mengikuti ketentuan Google Maps Platform. Endpoint tile tak terdokumentasi bukan pengganti API resmi dan dapat berhenti bekerja atau melanggar persyaratan penggunaan.
