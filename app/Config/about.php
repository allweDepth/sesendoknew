+----+---------------+------------+---------+---------------+----------+
| ID | TYPE_USER     | WILAYAH    | OPD     | SUB_KEGIATAN | APPROVE  |
+----+---------------+------------+---------+---------------+----------+
| 4  | super_admin   | Semua      | Semua   | Semua         | Ya       |
| 3  | admin_wilayah | 1 wilayah  | Semua   | Semua         | Ya       |
| 2  | admin_opd     | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
| 1  | viewer        | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
+----+---------------+------------+---------+---------------+----------+
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
┌───────────────────────────────────────────┐
│                FRONTEND                   │
│-------------------------------------------│
│  app.js                                   │
│   • TableManager                          │
│   • FormContainerManager                  │
│   • FormEngine                            │
│   • AjaxEngine                            │
│                                           │
│  UIConfig                                 │
│  ActionConfig                             │
│  RenstraHeaderConfig                      │
└──────────────────────┬────────────────────┘
                       │ AJAX (POST /dynamic)
                       ▼
┌───────────────────────────────────────────┐
│         DynamicTableService              │
│-------------------------------------------│
│  handle()                                │
│    ├─ add      → insert()                │
│    ├─ edit     → update()                │
│    ├─ delete   → delete()                │
│    ├─ dropdown → loadDropdown()          │
│    └─ lainnya  → buildQuery(mode)        │
│                                           │
│  Mode Aware + Action Aware               │
│  User Scope Aware                        │
└──────────────────────┬────────────────────┘
                       ▼
┌───────────────────────────────────────────┐
│         table_profiles.php               │
│-------------------------------------------│
│  • table                                 │
│  • primary_key                           │
│  • modes                                 │
│     ├─ default                           │
│     ├─ kepegawaian                       │
│     ├─ referensi                         │
│     └─ dll                               │
└──────────────────────┬────────────────────┘ 
                       ▼
┌───────────────────────────────────────────┐
│                DATABASE                  │
│-------------------------------------------│
│  sk_asn_neo                              │
│  renstra_neo                             │
│  misi_renstra_neo                        │
│  tujuan_renstra_neo                      │
│  dll                                     │
└───────────────────────────────────────────┘