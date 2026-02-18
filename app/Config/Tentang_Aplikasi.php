ID = 4
type_user = super_admin
Wilayah = Semua
OPD = Semua
Sub Kegiatan = Semua
Approve = Ya


ID = 3
type_user = admin_wilayah
Wilayah = 1 wilayah
OPD = Semua OPD di wilayah tersebut
Sub Kegiatan = Semua
Approve = Ya


ID = 2
type_user = admin_opd
Wilayah = 1 wilayah
OPD = 1 OPD
Sub Kegiatan = Berdasarkan mapping user_sub_kegiatan
Approve = Tidak


ID = 1
type_user = viewer
Wilayah = 1 wilayah
OPD = 1 OPD
Sub Kegiatan = Berdasarkan mapping user_sub_kegiatan
Approve = Tidak
+----+---------------+------------+---------+---------------+----------+
| ID | TYPE_USER     | WILAYAH    | OPD     | SUB_KEGIATAN | APPROVE  |
+----+---------------+------------+---------+---------------+----------+
| 4  | super_admin   | Semua      | Semua   | Semua         | Ya       |
| 3  | admin_wilayah | 1 wilayah  | Semua   | Semua         | Ya       |
| 2  | admin_opd     | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
| 1  | viewer        | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
+----+---------------+------------+---------+---------------+----------+

PRINSIP FINAL ENGINE – DynamicTableService
+----------------------+----------------------------------------------+--------------------------+
| jenis                | Kondisi Tambahan                             | Aksi Engine              |
+----------------------+----------------------------------------------+--------------------------+
| add                  | -                                            | INSERT (buat data baru)  |
| edit                 | hanya kirim id_row                           | LOAD 1 ROW (isi form)    |
| edit                 | kirim id + field lain                        | UPDATE (simpan perubahan)|
| delete               | kirim id_row                                 | DELETE (hapus data)      |
| dropdown             | kirim source                                 | LOAD DROPDOWN            |
| (kosong / lainnya)   | -                                            | LISTING (buildQuery)     |
+----------------------+----------------------------------------------+--------------------------+
Struktur Umum
+----------------+-----------------------------------------------+
| Level          | Fungsi                                        |
+----------------+-----------------------------------------------+
| module         | Nama modul (pengaturan, renstra, dll)        |
| table          | Nama tabel (periode_rpjmd, misi_renstra_neo) |
| tag            | Jenis komponen form                          |
| prop           | Properti komponen                            |
+----------------+-----------------------------------------------+
Bentuk Dasar
const UIConfig = {
    pengaturan: {
        periode_rpjmd: [
            {
                tag: "fieldCalendar",
                prop: {
                    label: "Periode Mulai",
                    name: "periode_mulai",
                    calendarType: "year",
                    attr: {
                        "data-group": "rpjmd",
                        "data-range": "start"
                    }
                }
            }
        ]
    }
};
+-------------------+----------------------------------+
| tag               | Komponen                         |
+-------------------+----------------------------------+
| field             | input text/number                |
| fieldTextarea     | textarea                         |
| fieldDropdown     | dropdown                         |
| fieldCheckbox     | checkbox / toggle                |
| fieldCalendar     | calendar fomantic                |
| fields            | group fields (grid)              |
| inlineFields      | inline layout                    |
| divider           | header pemisah                   |
| cardProfile       | card upload/avatar               |
+-------------------+----------------------------------+
Properti prop Standar
+---------------+----------------------------------+
| Properti      | Fungsi                           |
+---------------+----------------------------------+
| label         | Label field                      |
| name          | Name attribute (wajib untuk DB)  |
| type          | type input                       |
| options       | static dropdown options          |
| source        | dropdown server source           |
| parent        | dropdown dependency              |
| calendarType  | year / date                      |
| readonly      | readonly field                   |
| classField    | class tambahan                   |
| attr          | attribute HTML tambahan          |
+---------------+----------------------------------+
 Aturan UIConfig

✔ name harus sama dengan kolom database
✔ module harus sama dengan AppState.jenis
✔ table harus sama dengan AppState.tbl
✔ Jangan hardcode ID kalau bisa pakai attr
STRUKTUR AppState
+----------------+------------------------------------------+
| Property       | Fungsi                                   |
+----------------+------------------------------------------+
| halaman        | Page aktif untuk pagination              |
| rows           | Jumlah data per halaman                  |
| jenis          | Module aktif                             |
| tbl            | Tabel aktif                              |
| mode           | add / edit / detail                      |
| cari           | Keyword pencarian                        |
| currentMenu    | Tracking menu terakhir                   |
| serverSources  | Dropdown server dependency               |
| role           | Role login aktif                         |
+----------------+------------------------------------------+
Role Matrix Contoh
+-------------+--------+--------+--------+---------+
| Role        | Add    | Edit   | Delete | Upload  |
+-------------+--------+--------+--------+---------+
| super_admin | ✔      | ✔      | ✔      | ✔       |
| admin       | ✔      | ✔      | ✔      | ✔       |
| editor      | ✔      | ✔      | ✖      | ✔       |
| viewer      | ✖      | ✖      | ✖      | ✖       |
+-------------+--------+--------+--------+---------+