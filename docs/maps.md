# Maps

Menu Maps tersedia bagi pengguna yang sudah login. Peta menggunakan Leaflet untuk basemap XYZ dan layer SHP; Google Maps ditampilkan melalui Google Maps JavaScript API resminya.

## Menyiapkan database

Jalankan `database/migrations/20261002_phase59_maps_layers.sql` pada database aplikasi sebelum membuka halaman Maps. File shapefile disimpan privat di `storage/uploads/maps/`, sedangkan metadata layer disimpan di tabel `maps_layers`.

## Role dan cakupan data

- `admin_opd`, `kepala_opd`, dan `pa_kpa` dapat mengunggah atau menghapus layer untuk `kd_wilayah` dan `kd_opd` pada akunnya.
- Pengguna OPD lain hanya dapat melihat layer pada OPD-nya.
- `admin_wilayah` dan `tapd` dapat melihat layer dalam wilayahnya, mengikuti pilihan OPD aktif.
- `super_admin` dapat melihat seluruh layer atau mengikuti scope OPD yang sedang dipilih.

Endpoint file memeriksa scope yang sama seperti daftar layer; file shapefile tidak disajikan sebagai aset publik.

## Mengunggah shapefile

Pilih `.shp`; sertakan `.dbf`, `.shx`, dan `.prj` bila tersedia. Nama dasar semua komponen harus sama. Batas aplikasi adalah 32 MB per file dan 96 MB per set. Pastikan batas `upload_max_filesize`, `post_max_size`, serta batas body request pada web server mengizinkan unggahan tersebut.

Geometri titik, multipoint, garis, dan poligon didukung. Koordinat dari `.prj` dikonversi ke WGS 84/EPSG:4326 untuk ditampilkan. Bila `.prj` tidak tersedia, data harus sudah menggunakan koordinat WGS 84.

## Basemap

OpenStreetMap, Esri Terrain/Topographic, dan Esri Satellite dapat dipilih tanpa API key. Basemap OSM dan Esri mengikuti atribusi yang ditampilkan pada peta.

Google Roadmap, Satellite, Hybrid, dan Terrain memerlukan Google Maps Platform API key serta aktivasi Maps JavaScript API dan billing yang sesuai. Set variabel lingkungan `GOOGLE_MAPS_API_KEY` pada proses PHP; Google menyediakan peta ketika pilihan tersebut digunakan. Batasi key berdasarkan referrer aplikasi dan API yang diizinkan. Jangan simpan key pada source control.
