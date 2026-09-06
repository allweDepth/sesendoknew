<?php
/*
|--------------------------------------------------------------------------
| TABLE PROFILES CONFIG - FINAL CLEAN MULTI WILAYAH & MULTI PERATURAN
|--------------------------------------------------------------------------
|
| Arsitektur ini sudah:
| ✅ Scope-aware (kd_wilayah & peraturan)
| ✅ Multi regulasi aman
| ✅ Multi wilayah aman
| ✅ Import governance siap
| ✅ Tidak ada query bocor lintas wilayah/peraturan
|
| Catatan penting:
| - 'auto_session' = otomatis isi field saat INSERT
| - 'where'        = filter otomatis saat SELECT
| - Keduanya berbeda dan WAJIB untuk tabel struktur
|
*/

$profiles = [

  /*
    |--------------------------------------------------------------------------
    | MASTER AKUN
    |--------------------------------------------------------------------------
    */
  'akun' => [
    'table' => 'akun_neo',
    'primary_key' => 'id', // 🔥 Semua PK konsisten pakai id
    'allowed_roles' => ['super_admin'],
    'soft_lock' => true,
    /* ======================================================
    DROPDOWN CONFIG
    ------------------------------------------------------
    menentukan field dropdown lookup
    ====================================================== */
    'dropdown' => [
      'value' => 'kode',     // value yang disimpan di mapping
      'label' => 'uraian'    // teks yang tampil di dropdown
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kode', 'uraian'],
        'order_by' => 'kode ASC'
      ],
      'referensi' => [
        'select' => ['id', 'kode', 'uraian', 'keterangan'],
        'searchable' => ['kode', 'uraian', 'keterangan'],
        'order_by' => 'kode ASC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ]
    ]
  ],


  /*
    |--------------------------------------------------------------------------
    | MASTER SBU
    |--------------------------------------------------------------------------
    */
  /*
|--------------------------------------------------------------------------
| MASTER SBU
|--------------------------------------------------------------------------
| SBU = Standar Biaya Umum
|
| Tabel ini digunakan sebagai referensi harga standar barang/jasa
| yang nantinya dapat dipetakan ke akun belanja.
|
| Struktur ini mendukung:
| - Multi wilayah
| - Multi peraturan
| - Import Excel
|
*/

  'sbu' => [

    // Nama tabel fisik di database
    'table' => 'master_biaya',

    'where' => [
      'tipe' => 'sbu'
    ],

    // Primary key tabel
    'primary_key' => 'id',

    // Role yang boleh mengakses
    'allowed_roles' => ['super_admin'],

    // Jika aktif maka data tidak boleh dihapus langsung
    'soft_lock' => true,


    /*
    |--------------------------------------------------------------------------
    | DROPDOWN CONFIG
    |--------------------------------------------------------------------------
    | Digunakan ketika tabel ini dipanggil sebagai dropdown
    |
    | value = nilai yang dikirim ke form
    | label = teks yang ditampilkan
    */
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian_barang',

      // filter berdasarkan akun jika dropdown dipanggil
      'filter_by_akun' => true
    ],


    /*
    |--------------------------------------------------------------------------
    | PIVOT TABLE
    |--------------------------------------------------------------------------
    | Digunakan untuk mapping SBU ke akun
    |
    | contoh:
    | SBU → akun belanja
    */
    'pivot' => [

      // tabel pivot
      'table' => 'sbu_akun_map',

      // foreign key di pivot
      'foreign_key' => 'sbu_id'
    ],


    /*
    |--------------------------------------------------------------------------
    | IMPORT CONFIG
    |--------------------------------------------------------------------------
    |
    | Mengatur bagaimana data Excel diimport
    |
    */
    'import' => [

      // apakah import diizinkan
      'enabled' => true,

      // siapa yang boleh import
      'allowed_roles' => ['super_admin'],


      /*
        |--------------------------------------------------------------------------
        | RELATIONS
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk resolve foreign key dari Excel
        |
        | contoh:
        | Excel → satuan = Kg
        | Database → satuan_id = 5
        |
        */
      'relations' => [

        'satuan' => [

          // tabel target
          'table' => 'satuan_neo',

          // kolom yang dicari
          'lookup' => 'uraian',

          // primary key yang diambil
          'id' => 'id',

          // kolom yang akan disimpan di tabel SBU
          'store' => 'satuan_id',

          // scope pencarian
          'scope' => [
            'peraturan_id' => 'user'
          ]
        ]

      ]
    ],


    /*
    |--------------------------------------------------------------------------
    | MODE QUERY
    |--------------------------------------------------------------------------
    |
    | Setiap mode menentukan:
    | - select field
    | - kolom yang bisa dicari
    | - sorting
    | - filter otomatis
    |
    */
    'modes' => [

      'default' => [

        'select' => ['*'],

        'searchable' => [
          'kd_aset',
          'uraian_barang'
        ],

        'order_by' => 'uraian_barang ASC',

        // filter otomatis berdasarkan tahun session
        'where' => [
          'tahun' => 'user'
        ]
      ],


      /*
        |--------------------------------------------------------------------------
        | MODE STANDAR HARGA
        |--------------------------------------------------------------------------
        |
        | Digunakan pada modul standar harga
        |
        */
      'standar_harga' => [

        'select' => [
          'id',
          'kd_aset',
          'kd_akun',
          'uraian_barang',
          'spesifikasi',
          'satuan',
          'harga_satuan'
        ],

        'searchable' => [
          'kd_aset',
          'kd_akun',
          'uraian_barang',
          'spesifikasi',
          'satuan',
          'harga_satuan'
        ],

        'order_by' => 'kd_aset ASC',

        'where' => [
          'tahun' => 'user'
        ]
      ],


      /*
        |--------------------------------------------------------------------------
        | MODE EDIT
        |--------------------------------------------------------------------------
        |
        | Digunakan ketika membuka form edit
        |
        */
      'edit' => [

        'select' => ['*'],

        'searchable' => ['*'],

        'order_by' => 'id ASC',

        'where' => [
          'tahun' => 'user'
        ]
      ]
    ]
  ],
  'ssh' => [
    'table' => 'master_biaya',

    'where' => [
      'tipe' => 'ssh'
    ],
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin'],
    'soft_lock' => true,
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian_barang',
      'filter_by_akun' => true
    ],
    'pivot' => [
      'table' => 'ssh_akun_map',
      'foreign_key' => 'ssh_id'
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kd_aset', 'uraian_barang'],
        'order_by' => 'uraian_barang ASC'
      ],
      'standar_harga' => [
        'select' => ['id', 'kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'searchable' => ['kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'order_by' => 'kd_aset ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC',
        'where' => [
          'tahun' => 'user'
        ]
      ]
    ]
  ],
  'asb' => [
    'table' => 'master_biaya',

    'where' => [
      'tipe' => 'asb'
    ],
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin'],
    'soft_lock' => true,
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian_barang',
      'filter_by_akun' => true
    ],
    'pivot' => [
      'table' => 'asb_akun_map',
      'foreign_key' => 'asb_id'
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kd_aset', 'uraian_barang'],
        'order_by' => 'uraian_barang ASC'
      ],
      'standar_harga' => [
        'select' => ['id', 'kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'searchable' => ['kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'order_by' => 'kd_aset ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC',
        'where' => [
          'tahun' => 'user'
        ]
      ]
    ]
  ],
  'hspk' => [
    'table' => 'master_biaya',

    'where' => [
      'tipe' => 'hspk'
    ],
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin'],
    'soft_lock' => true,
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian_barang',
      'filter_by_akun' => true //loadDropdown() otomatis tahu harus join pivot
    ],
    'pivot' => [
      'table' => 'hspk_akun_map',
      'foreign_key' => 'hspk_id'
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kd_aset', 'uraian_barang'],
        'order_by' => 'uraian_barang ASC'
      ],
      'standar_harga' => [
        'select' => ['id', 'kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'searchable' => ['kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
        'order_by' => 'kd_aset ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC',
        'where' => [
          'tahun' => 'user'
        ]
      ]
    ]
  ],
  'mapping' => [

    'table' => 'master_biaya_akun',
    'auto_session' => ['kd_wilayah', 'peraturan_id'],
    'soft_delete' => [
      'field' => 'is_deleted',
      'value_active' => 0,
      'value_deleted' => 1
    ],
    'not_duplicate' => ['master_biaya_id', 'kd_akun', 'peraturan_id'],
    'dropdown_sources' => [

      'master_biaya' => [

        'table' => 'master_biaya',

        'value' => 'id',

        'label' => 'uraian'

      ]

    ],

    'primary_key' => 'id',

    'allowed_roles' => ['super_admin', 'admin_wilayah'],

    'soft_lock' => true,

    /* JOIN RELASI */

    'join' => [

      [
        'table' => 'master_biaya',
        'on' => 'master_biaya.id = master_biaya_akun.master_biaya_id'
      ],

      [
        'table' => 'akun_neo',
        'on' => 'akun_neo.kode = master_biaya_akun.kd_akun'
      ]

    ],

    'modes' => [

      'default' => [

        'select' => [

          'master_biaya_akun.id',

          /* kode aset dari standar biaya */
          'master_biaya.kode_aset',

          /* nama komponen */
          'master_biaya.uraian AS uraian_biaya',

          /* tipe ssh/sbu/asb/hspk */
          'master_biaya.tipe',

          'master_biaya.kelompok_barang',

          'master_biaya.tahun',

          /* akun belanja */
          'master_biaya_akun.kd_akun',

          'akun_neo.uraian AS uraian_akun',

        ],

        'searchable' => [

          'master_biaya.kode',

          'master_biaya.uraian',

          'master_biaya.kelompok_barang',

          'akun_neo.uraian'
        ],

        'order_by' => 'master_biaya.kode ASC'

      ]

    ]

  ],
  'aset' => [
    'table' => 'aset_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
    'dropdown' => [
      'value' => 'kode',
      'label' => 'uraian',
      'label_fields' => ['kode', 'uraian'],
      'searchable' => ['kode', 'uraian'],
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kode', 'uraian'],
        'order_by' => 'kode ASC'
      ],
      'referensi' => [
        'select' => ['id', 'kode', 'uraian', 'keterangan'],
        'searchable' => ['kode', 'uraian'],
        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'organisasi' => [
    'table' => 'organisasi_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
    'modes' => [
      'default' => [
        'select' => ['id', 'kode', 'uraian', 'singkatan'],
        'searchable' => ['kode', 'uraian', 'singkatan'],
        'order_by' => 'kode ASC'
      ],
      'referensi' => [
        'select' => ['id', 'kode', 'uraian', 'alamat', 'keterangan'],
        'searchable' => ['kode', 'uraian', 'alamat', 'keterangan'],
        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'satuan' => [
    'table' => 'satuan_neo',
    'not_duplicate' => ['uraian', 'peraturan_id'],
    'primary_key' => 'id',
    'where' => [
      'is_deleted' => 0
    ],
    'soft_delete' => [
      'field' => 'is_deleted',
      'value_active' => 0,
      'value_deleted' => 1
    ],
    'import' => [
      'enabled' => true,
      'allowed_roles' => ['super_admin', 'admin_wilayah'],
      'check_duplicate' => true,
      'check_hierarchy' => false,
      'relation_map' => [
        'satuan' => [
          'target_table' => 'satuan_neo',
          'target_field' => 'uraian',
          'target_id'    => 'id',
          'store_as'     => 'satuan_id'
        ]
      ]
    ],
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian',  // 🔥 ini yang benar
      'searchable' => ['uraian', 'value'],
      'order_by' => 'uraian ASC'
    ],

    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
    'modes' => [
      'dropdown' => [
        'select' => ['id', 'value', 'uraian'],
        'searchable' => ['uraian', 'value'],
        'order_by' => 'uraian ASC'
      ],
      'referensi' => [
        'select' => ['id', 'value', 'uraian', 'keterangan'],
        'searchable' => ['uraian', 'value'],
        'order_by' => 'uraian ASC',
        'where' => ['is_deleted' => 0]
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['value', 'uraian'],
        'order_by' => 'uraian ASC',
        'scope' => [
          'peraturan_id' => 'user' // otomatis ambil dari session user
        ]
      ]
    ]
  ],

  // rekening_kegiatan menyimpan satuan sebagai nomenklatur teks, bukan id.
  'satuan_teks' => [
    'table' => 'satuan_neo',
    'primary_key' => 'id',
    'dropdown' => [
      'value' => 'uraian',
      'label' => 'uraian',
      'searchable' => ['uraian', 'value'],
      'order_by' => 'uraian ASC'
    ],
    'where' => ['disable' => 0]
  ],



  'sumber_dana' => [
    'table' => 'sumber_dana_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
    'dropdown' => ['value' => 'id', 'label' => 'uraian', 'searchable' => ['kode', 'uraian'], 'order_by' => 'kode ASC'],
    'import' => [
      'enabled' => true
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'kode', 'uraian'],
        'searchable' => ['kode', 'uraian'],
        'order_by' => 'kode ASC'
      ],
      'referensi' => [
        'select' => ['id', 'kode', 'uraian', 'keterangan'],
        'searchable' => ['kode', 'uraian', 'keterangan'],
        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'wilayah' => [
    'table' => 'wilayah_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
    'import' => [
      'enabled' => true
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'kode', 'uraian', 'status'],
        'searchable' => ['kode', 'uraian'],
        'order_by' => 'kode ASC'
      ],
      'referensi' => [
        'select' => ['id', 'kode', 'uraian', 'status', 'jml_kec', 'jml_desa', 'luas', 'keterangan'],
        'searchable' => ['kode', 'uraian', 'status', 'jml_kec', 'jml_desa', 'luas', 'keterangan'],
        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'peraturan' => [

    'table' => 'peraturan_neo',

    'primary_key' => 'id',

    'allowed_roles' => ['super_admin', 'admin_wilayah'],

    'soft_lock' => true,

    /*
    |--------------------------------------------------------------------------
    | IMPORT CONFIG
    |--------------------------------------------------------------------------
    | Mengaktifkan fitur import Excel
    */
    'import' => [
      'enabled' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | DROPDOWN CONFIG
    |--------------------------------------------------------------------------
    */
    'dropdown' => [
      'value' => 'id',
      'label' => 'judul'
    ],

    'modes' => [
      'default' => [
        'select' => ['id', 'kode', 'judul', 'nomor'],
        'searchable' => ['kode', 'judul', 'nomor'],
        'order_by' => 'tgl_penetapan DESC'
      ],
      'referensi' => [
        'select' => ['id', 'nomor', 'judul', 'tgl_penetapan', 'status', 'keterangan'],
        'searchable' => ['kode', 'uraian', 'alamat', 'keterangan'],
        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'rekanan' => [
    'table' => 'rekanan_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
    'soft_lock' => true,
    'dropdown' => ['value' => 'id', 'label' => 'nama_perusahaan', 'searchable' => ['nama_perusahaan', 'npwp'], 'order_by' => 'nama_perusahaan ASC'],
    'auto_session' => ['kd_wilayah'],
    'where' => [
      'is_deleted' => 0
    ],
    'soft_delete' => [
      'field' => 'is_deleted',
      'value_active' => 0,
      'value_deleted' => 1
    ],
    'import' => [
      'enabled' => true
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'nama_perusahaan', 'npwp'],
        'searchable' => ['nama_perusahaan', 'npwp'],
        'order_by' => 'nama_perusahaan ASC',
        'where' => [
          'kd_wilayah' => 'user'
        ]
      ],
      'referensi' => [
        'select' => ['nama_perusahaan', 'alamat', 'email', 'npwp', 'no_rekening', 'bank_rekening', 'direktur'],
        'searchable' => ['nama_perusahaan', 'alamat', 'direktur'],
        'order_by' => 'nama_perusahaan ASC'
      ]
    ]
  ],

  'renja' => [
    'table' => 'renja_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'import' => [
      'enabled' => true
    ],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kd_sub_keg', 'uraian'],
        'order_by' => 'tahun DESC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ]
    ]
  ],

  'renja_p' => [
    'table' => 'renja_p_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],

    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['kd_sub_keg', 'uraian'],
        'order_by' => 'tahun DESC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ]
    ]
  ],

  'renstra_skpd' => [
    'table' => 'renstra_skpd_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['uraian_prog_keg', 'indikator'],
        'order_by' => 'tahun DESC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ]
    ]
  ],

  'tujuan_sasaran_renstra' => [
    'table' => 'tujuan_sasaran_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'modes' => [

      'tujuan' => [
        'select' => ['id', 'text'],
        'searchable' => ['text'],
        'where' => [
          'tahun' => 'user',
          'kelompok' => 'tujuan'
        ],
        'order_by' => 'text ASC',
      ],

      'sasaran' => [
        'select' => ['id', 'text', 'indikator'],
        'searchable' => ['text', 'indikator'],
        'where' => "kelompok = 'sasaran'",
        'order_by' => 'text ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],

      'default' => [
        'select' => ['*'],
        'searchable' => ['text', 'indikator'],
        'order_by' => 'tahun DESC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'referensi' => [
        'select' => ['nama_perusahaan', 'alamat', 'email', 'npwp', 'no_rekening', 'bank_rekening', 'atas_nama_rekening', 'direktur'],
        'searchable' => ['nama_perusahaan', 'alamat', 'direktur'],
        'order_by' => 'nama_perusahaan ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ]
    ]
  ],
  'asn' => [
    'table' => 'db_asn_pemda_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'kd_opd'],
    'dropdown' => ['value' => 'id', 'label' => 'nama'],
    // Kolom status pada master ASN berisi status perkawinan, bukan flag aktif.
    'dropdown_status_filter' => false,
    'validation' => [
      'nip' => ['required', 'unique']
    ],
    'modes' => [
      'dropdown' => [
        'select' => ['id', 'nama', 'nip', 'alamat', 'golongan', 'ruang', 'jabatan', 'keterangan'],
        'searchable' => ['kd_opd', 'nama', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['id', 'kd_opd', 'uraian', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'kepegawaian' => [
        'select' => ['id', 'nama', 'nip', 'alamat', 'golongan', 'ruang', 'jabatan', 'keterangan'],
        'searchable' => ['id', 'kd_opd', 'nama', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'nama ASC'
      ]
    ]
  ],

  'pppk' => [
    'table' => 'db_asn_pemda_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd'],
    'where' => ['jenis_kepeg' => 'PPPK'],
    'validation' => ['nip' => ['required', 'unique'], 'nama' => ['required']],
    'modes' => [
      'default' => ['select' => ['id', 'nama', 'nip', 'jabatan', 'unit_kerja', 'status_kepeg', 'keterangan'], 'searchable' => ['nama', 'nip', 'jabatan'], 'order_by' => 'nama ASC'],
      'kepegawaian' => ['select' => ['id', 'nama', 'nip', 'jabatan', 'unit_kerja', 'status_kepeg', 'keterangan'], 'searchable' => ['nama', 'nip', 'jabatan'], 'order_by' => 'nama ASC'],
      'edit' => ['select' => ['*'], 'searchable' => ['*'], 'order_by' => 'nama ASC']
    ]
  ],

  'riwayat_jabatan' => [
    'table' => 'riwayat_jabatan_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'validation' => ['pegawai_id' => ['required'], 'jabatan' => ['required'], 'tmt' => ['required']],
    'modes' => [
      'kepegawaian' => ['select' => ['id', 'pegawai_id', 'nomor_sk', 'jabatan', 'unit_kerja', 'tmt', 'tanggal_selesai', 'keterangan'], 'searchable' => ['nomor_sk', 'jabatan', 'unit_kerja'], 'order_by' => 'tmt DESC'],
      'default' => ['select' => ['*'], 'searchable' => ['nomor_sk', 'jabatan', 'unit_kerja'], 'order_by' => 'tmt DESC'],
      'edit' => ['select' => ['*'], 'searchable' => ['*'], 'order_by' => 'id DESC']
    ]
  ],

  'riwayat_pangkat' => [
    'table' => 'riwayat_pangkat_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'validation' => ['pegawai_id' => ['required'], 'golongan' => ['required'], 'tmt' => ['required']],
    'modes' => [
      'kepegawaian' => ['select' => ['id', 'pegawai_id', 'nomor_sk', 'golongan', 'ruang', 'tmt', 'masa_kerja_tahun', 'keterangan'], 'searchable' => ['nomor_sk', 'golongan', 'ruang'], 'order_by' => 'tmt DESC'],
      'default' => ['select' => ['*'], 'searchable' => ['nomor_sk', 'golongan', 'ruang'], 'order_by' => 'tmt DESC'],
      'edit' => ['select' => ['*'], 'searchable' => ['*'], 'order_by' => 'id DESC']
    ]
  ],

  'cuti' => [
    'table' => 'cuti_pegawai_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'validation' => ['pegawai_id' => ['required'], 'jenis_cuti' => ['required'], 'tanggal_mulai' => ['required'], 'tanggal_selesai' => ['required']],
    'modes' => [
      'kepegawaian' => ['select' => ['id', 'pegawai_id', 'nomor_surat', 'jenis_cuti', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_hari', 'status', 'keterangan'], 'searchable' => ['nomor_surat', 'jenis_cuti', 'status'], 'order_by' => 'tanggal_mulai DESC'],
      'default' => ['select' => ['*'], 'searchable' => ['nomor_surat', 'jenis_cuti', 'status'], 'order_by' => 'tanggal_mulai DESC'],
      'edit' => ['select' => ['*'], 'searchable' => ['*'], 'order_by' => 'id DESC']
    ]
  ],

  'sk_pegawai' => [
    'table' => 'sk_pegawai_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'validation' => ['pegawai_id' => ['required'], 'nomor_sk' => ['required'], 'jenis_sk' => ['required']],
    'modes' => [
      'kepegawaian' => ['select' => ['id', 'pegawai_id', 'nomor_sk', 'tanggal_sk', 'jenis_sk', 'tentang', 'file', 'keterangan'], 'searchable' => ['nomor_sk', 'jenis_sk', 'tentang'], 'order_by' => 'tanggal_sk DESC'],
      'default' => ['select' => ['*'], 'searchable' => ['nomor_sk', 'jenis_sk', 'tentang'], 'order_by' => 'tanggal_sk DESC'],
      'edit' => ['select' => ['*'], 'searchable' => ['*'], 'order_by' => 'id DESC']
    ]
  ],

  'pejabat_tahunan' => [
    'table' => 'pejabat_tahunan_neo',
    'primary_key' => 'id',
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'where' => ['is_deleted' => 0],
    'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
    'validation' => ['jenis_pejabat' => ['required'], 'pegawai_id' => ['required', 'numeric'], 'nomor_sk' => ['required'], 'berlaku_mulai' => ['required'], 'berlaku_sampai' => ['required']],
    'dropdown' => ['value' => 'id', 'label' => 'nama_pegawai', 'searchable' => ['nama_pegawai', 'nip', 'jenis_pejabat', 'kd_sub_keg'], 'order_by' => 'nama_pegawai ASC'],
    'modes' => [
      'default' => ['select' => ['id', 'jenis_pejabat', 'nama_pegawai', 'nip', 'nomor_sk', 'berlaku_mulai', 'berlaku_sampai', 'kd_sub_keg', 'keterangan'], 'searchable' => ['jenis_pejabat', 'nama_pegawai', 'nip', 'nomor_sk', 'kd_sub_keg'], 'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'is_deleted' => 0], 'order_by' => 'berlaku_mulai DESC'],
      'dropdown' => ['select' => ['id', 'nama_pegawai', 'nip', 'jenis_pejabat', 'kd_sub_keg'], 'searchable' => ['nama_pegawai', 'nip', 'jenis_pejabat', 'kd_sub_keg'], 'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'is_deleted' => 0], 'order_by' => 'nama_pegawai ASC'],
      'edit' => ['select' => ['*'], 'searchable' => ['nama_pegawai', 'nip'], 'order_by' => 'id ASC']
    ]
  ],

  'register_surat' => [
    'table' => 'register_naskah_dinas',
    'primary_key' => 'id',
    'modes' => [
      'dropdown' => [
        'select' => ['id', 'jenis_naskah_dinas', 'sifat', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
        'searchable' => ['jenis_naskah_dinas', 'sifat', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
        'order_by' => 'tanggal ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['jenis_naskah_dinas', 'sifat', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
        'order_by' => 'tanggal ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'kepegawaian' => [
        'select' => ['id', 'jenis_naskah_dinas', 'sifat', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
        'searchable' => ['jenis_naskah_dinas', 'sifat', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
        'order_by' => 'tanggal ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC',
        'where' => [
          'tahun' => 'user'
        ] // ambil dari user login
      ]
    ]
  ],

  'misi_renstra_neo' => [
    'table' => 'misi_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'nama_misi'
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'renstra_id', 'nama_misi', 'disable', 'keterangan'],
        'searchable' => ['nama_misi'],
        'order_by' => 'id DESC',
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ],
      'dropdown' => [
        'select' => ['id', 'nama_misi'],
        'searchable' => ['nama_misi'],
        'order_by' => 'nama_misi ASC'
      ],
    ]
  ],

  'tujuan_renstra_neo' => [
    'table' => 'tujuan_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'nama_tujuan'
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'misi_id', 'nama_tujuan', 'disable', 'keterangan'],
        'searchable' => ['nama_tujuan'],
        'order_by' => 'id DESC',
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ],
      'dropdown' => [
        'select' => ['id', 'nama_tujuan'],
        'searchable' => ['nama_tujuan'],
        'order_by' => 'nama_tujuan ASC'
      ],
    ]
  ],

  'sasaran_renstra_neo' => [
    'table' => 'sasaran_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'nama_sasaran'
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'tujuan_id', 'nama_sasaran', 'disable', 'keterangan'],
        'searchable' => ['nama_sasaran'],
        'order_by' => 'id DESC',
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ],
      'dropdown' => [
        'select' => ['id', 'nama_sasaran'],
        'searchable' => ['nama_sasaran'],
        'order_by' => 'nama_sasaran ASC'
      ],
    ]
  ],

  'indikator_sasaran_renstra_neo' => [
    'table' => 'indikator_sasaran_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'modes' => [
      'default' => [
        'select' => [
          'id',
          'sasaran_id',
          'nama_indikator',
          'satuan',
          'baseline',
          'target_t1',
          'target_t2',
          'target_t3',
          'target_t4',
          'target_t5',
          'target_akhir',
          'keterangan'
        ],
        'searchable' => ['nama_indikator', 'satuan'],
        'order_by' => 'id DESC',
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ]
    ]
  ],

  'program_renstra_neo' => [
    'table' => 'program_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian'
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'sasaran_id', 'kode_program', 'uraian', 'disable', 'keterangan'],
        'searchable' => ['kode_program', 'uraian'],
        'order_by' => 'id DESC',
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ],
      'dropdown' => [
        'select' => ['id', 'uraian'],
        'searchable' => ['uraian'],
        'order_by' => 'nama_program ASC'
      ],
    ]
  ],

  'indikator_program_renstra_neo' => [
    'table' => 'indikator_program_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'modes' => [
      'default' => [
        'select' => [
          'id',
          'program_id',
          'nama_indikator',
          'satuan',
          'baseline',
          'target_t1',
          'target_t2',
          'target_t3',
          'target_t4',
          'target_t5',
          'target_akhir',
          'keterangan'
        ],
        'searchable' => ['nama_indikator', 'satuan'],
        'order_by' => 'id DESC',
      ],
      'dropdown' => [
        'select' => ['id', 'uraian'],
        'searchable' => ['uraian'],
        'order_by' => 'nama_kegiatan ASC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ]
    ]
  ],

  'kegiatan_renstra_neo' => [
    'table' => 'kegiatan_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian'
    ],
    'modes' => [
      'default' => [
        'select' => ['id', 'program_id', 'kode_kegiatan', 'uraian', 'keterangan'],
        'searchable' => ['kode_kegiatan', 'uraian'],
        'order_by' => 'id DESC'
      ]
    ],
    'validation' => [
      'program_id'    => ['required', 'numeric'],
      'uraian' => ['required']
    ]
  ],

  'sub_kegiatan_renstra_neo' => [
    'table' => 'sub_kegiatan_renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'master_sub_kegiatan_id'
    ],
    'modes' => [
      'default' => [
        'select' => [
          'id',
          'kegiatan_renstra_id',
          'master_sub_kegiatan_id',
          'lokasi',
          'kelompok_sasaran',
          'indikator_keluaran',
          'satuan',
          'baseline',
          'target_t1',
          'anggaran_t1',
          'target_t2',
          'anggaran_t2',
          'target_t3',
          'anggaran_t3',
          'target_t4',
          'anggaran_t4',
          'target_t5',
          'anggaran_t5',
          'target_akhir'
        ],
        'searchable' => ['lokasi', 'kelompok_sasaran'],
        'order_by' => 'id DESC'
      ]
    ],
    'validation' => [
      'kegiatan_renstra_id'    => ['required', 'numeric'],
      'master_sub_kegiatan_id' => ['required', 'numeric']
    ]
  ],

  'referensi_sub_kegiatan_renstra' => [
    'table' => 'rekening_kegiatan',
    'primary_key' => 'id',
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian',
      'label_fields' => ['kode', 'uraian'],
      'searchable' => ['kode', 'uraian']
    ],
    'where' => [
      'level' => 'sub_kegiatan'
    ],
    'relations' => [
      [
        'local_key' => 'parent_kode'
      ]
    ],
    'dropdown_parent_lookup' => [
      'table' => 'kegiatan_renstra_neo',
      'key' => 'id',
      'value' => 'kode_kegiatan'
    ]
  ],

  'renstra_neo' => [
    'table' => 'renstra_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['admin_opd'],
    'soft_lock' => true,
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'dropdown' => [
      'value' => 'id',
      'label' => 'visi'
    ],
    'modes' => [
      'default' => [
        'select' => [
          'id',
          'periode_id',
          'visi',
          'status',
          'kunci',
          'setujui',
          'disable',
          'keterangan'
        ],
        'searchable' => ['visi'],
        'order_by' => 'id DESC'
      ],
      'dropdown' => [
        'select' => ['id', 'visi'],
        'searchable' => ['visi'],
        'order_by' => 'id DESC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ]
    ]
  ],
  'periode_rpjmd' => [
    'table' => 'periode_rpjmd',
    'primary_key' => 'id',
    'dropdown' => [
      'value' => 'id',
      'label' => 'periode_mulai'
    ],
    'modes' => [
      'default' => [
        'select' => [
          'id',
          'periode_mulai',
          'periode_selesai',
          'status_aktif',
          'keterangan'
        ],
        'searchable' => ['periode_mulai', 'periode_selesai', 'keterangan'],
        'order_by' => 'periode_mulai DESC'
      ]
    ]
  ],


  // ======================================================
  // PROFIL REKENING KEGIATAN (SIPD HIERARKI)
  // ======================================================

  'rekening_kegiatan' => [

    // --------------------------------------------------
    // tabel utama hierarki SIPD
    // --------------------------------------------------
    'table' => 'rekening_kegiatan',
    'primary_key' => 'id',

    'auto_session' => [
      'kd_wilayah',
      'peraturan_id'
    ],
    // --------------------------------------------------
    // primary key
    // --------------------------------------------------
    'primary_key' => 'id',

    // --------------------------------------------------
    // dropdown configuration
    // --------------------------------------------------
    'dropdown' => [
      'value' => 'kode',
      'label' => 'uraian'
    ],

    // --------------------------------------------------
    // relasi parent-child
    // --------------------------------------------------
    'relations' => [
      [
        'local_key' => 'parent_kode',   // // relasi tetap
        'constraint' => 'level'         // // TAMBAHAN: enforce level (butuh dukungan service)
      ]
    ],

    // --------------------------------------------------
    // filter default
    // --------------------------------------------------
    'where' => [
      'status' => 1
    ],

    'soft_delete' => [
      'field' => 'status',
      'value_active' => 1,
      'value_deleted' => 0
    ],

    // --------------------------------------------------
    // request filter berdasarkan level
    // --------------------------------------------------
    'req_filters' => [

      'urusan' => [
        'where' => ['level' => 'urusan']
      ],

      'bidang' => [
        'where' => ['level' => 'bidang']
      ],

      'program' => [
        'where' => ['level' => 'program']
      ],

      'kegiatan' => [
        'where' => ['level' => 'kegiatan']
      ],

      'sub_kegiatan' => [
        'where' => ['level' => 'sub_kegiatan']
      ]

    ],

    // --------------------------------------------------
    // listing modes
    // --------------------------------------------------
    'modes' => [

      'default' => [

        'select' => [
          'id',
          'kode',
          'parent_kode',
          'level',
          'uraian',
          'kinerja',
          'indikator',
          'satuan',
        ],

        'searchable' => [
          'kode',
          'uraian'
        ],

        // urutan hierarchy benar
        'order_by' => 'kode ASC'

      ],

      // ----------------------------------------------
      // khusus urusan
      // ----------------------------------------------
      'urusan' => [
        'select' => [
          'id',
          'kode',
          'uraian'
        ],
        'where' => [
          'level' => 'urusan'
        ],
        'order_by' => 'kode ASC'
      ],

      // ----------------------------------------------
      // bidang
      // ----------------------------------------------
      'bidang' => [

        'select' => [
          'id',
          'kode',
          'uraian',
          'parent_kode'
        ],

        'where' => [
          'level' => 'bidang'
        ],

        'order_by' => 'kode ASC'
      ],

      // ----------------------------------------------
      // program
      // ----------------------------------------------
      'program' => [

        'select' => [
          'id',
          'kode',
          'uraian',
          'parent_kode'
        ],

        'where' => [
          'level' => 'program'
        ],

        'order_by' => 'kode ASC'
      ],

      // ----------------------------------------------
      // kegiatan
      // ----------------------------------------------
      'kegiatan' => [

        'select' => [
          'id',
          'kode',
          'uraian',
          'parent_kode'
        ],

        'where' => [
          'level' => 'kegiatan'
        ],

        'order_by' => 'kode ASC'
      ],

      // ----------------------------------------------
      // sub kegiatan
      // ----------------------------------------------
      'sub_kegiatan' => [

        'select' => [
          'id',
          'kode',
          'uraian',
          'kinerja',
          'indikator',
          'satuan',
          'parent_kode'
        ],

        'where' => [
          'level' => 'sub_kegiatan'
        ],

        'order_by' => 'kode ASC'
      ]
    ]
  ],

  'kontrak' => [
    // --------------------------------------------------
    // tabel utama
    // --------------------------------------------------
    'table' => 'kontrak_neo',
    'primary_key' => 'id',

    // --------------------------------------------------
    // auto session (mengikuti pola existing)
    // --------------------------------------------------
    'auto_session' => [
      'kd_wilayah',
      'kd_opd'
    ],

    // --------------------------------------------------
    // filter default (opsional tapi relevan)
    // --------------------------------------------------
    'where' => [
      // kosong → tidak ditemukan constraint di file
    ],

    // --------------------------------------------------
    // dropdown (untuk flyout jika dipakai)
    // --------------------------------------------------
    'dropdown' => [
      'value' => 'id',
      'label' => 'nomor_kontrak'
    ],

    // --------------------------------------------------
    // request filter (opsional, mengikuti pola)
    // --------------------------------------------------
    'req_filters' => [

      'dpa' => [
        'where' => ['tahap' => 'dpa']
      ],

      'dppa' => [
        'where' => ['tahap' => 'dppa']
      ]

    ],

    // --------------------------------------------------
    // MODES (INI YANG WAJIB)
    // --------------------------------------------------
    'modes' => [

      // ----------------------------------------------
      // default listing
      // ----------------------------------------------
      'default' => [

        'select' => [
          'id',
          'tahun',
          'periode_aktif_id',
          'nama_sub_keg',
          'tahap',
          'total_anggaran',
          'nilai_kontrak',
          'nomor_kontrak',
          'tanggal_kontrak',
          'nama_ppk',
          'nama_penyedia',
          'status_kontrak'
        ],

        'searchable' => [
          'nomor_kontrak',
          'nama_sub_keg',
          'nama_penyedia'
        ],

        'order_by' => 'id DESC'

      ],

      // ----------------------------------------------
      // mode detail (untuk view / edit)
      // ----------------------------------------------
      'detail' => [

        'select' => [
          'id',
          'kd_wilayah',
          'kd_opd',
          'tahun',
          'kd_sub_keg',
          'anggaran_id',
          'nama_sub_keg',
          'tahap',
          'total_anggaran',
          'nilai_kontrak',
          'nomor_kontrak',
          'tanggal_kontrak',
          'uraian_kontrak',
          'nomor_spmk',
          'tanggal_spmk',
          'waktu_pelaksanaan',
          'tanggal_mulai',
          'tanggal_selesai',
          'nama_ppk',
          'nama_penyedia',
          'nama_tim_teknis',
          'tanggal_pho',
          'tanggal_fho',
          'tanggal_serah_terima',
          'status_kontrak'
        ]

      ],

      // ----------------------------------------------
      // mode monitoring waktu
      // ----------------------------------------------
      'timeline' => [

        'select' => [
          'id',
          'nomor_kontrak',
          'tanggal_mulai',
          'tanggal_selesai',
          'tanggal_pho',
          'tanggal_fho'
        ],

        'order_by' => 'tanggal_mulai DESC'

      ]

    ]

  ],
  'trx_naskah_dinas' => [
    'table' => 'trx_naskah_dinas',
    'primary_key' => 'id',

    'read_relations' => [

      // =====================================
      // JENIS NASKAH
      // =====================================
      'cache_schema_naskah' => [
        'select' => ['jenis_id', 'schema_json'],
        'where' => [
          'jenis_id' => 'jenis_id'
        ],
        'alias' => [
          'struktur_json' => 'schema'
        ]
      ],
      'trx_naskah_struktur' => [
        'select' => ['naskah_id', 'struktur_json'],
        'where' => [
          'naskah_id' => 'id'
        ]
      ],

      // =====================================
      // ASN
      // =====================================
      'db_asn_pemda_neo' => [
        'select' => [
          'id',
          'nama',
          'gelar_depan',
          'gelar',
          'nip',
          'jabatan',
          'golongan',
          'ruang'
        ],
        'where' => [
          'kd_opd' => 'kd_opd',
          'kd_wilayah' => 'kd_wilayah',
          'disable' => ['value' => 0]
        ],
        'alias' => [
          'db_asn_pemda_neo' => 'asn'
        ]
      ]

    ],

    'write_relations' => [
      'trx_naskah_meta' => [
        'fk' => 'naskah_id'
      ],
      'trx_naskah_struktur' => [
        'fk' => 'naskah_id'
      ]
    ],

    // =====================================
    // 🔥 JSON UPDATE ENGINE (GLOBAL CONTROL)
    // =====================================
    'json_update' => [
      'mode' => 'direct', // direct | versioning_only | smart_versioning
      'relation_table' => 'trx_naskah_struktur',
      'fk' => 'naskah_id',
      'json_field' => 'struktur_json',

      // penentu apakah insert versi baru atau update biasa
      'versioning_fields' => ['nomor', 'tanggal_surat']
    ],
    'not_duplicate' => [
      'nomor',
      'tanggal_surat',
      'kd_wilayah',
      'tahun',
      'kd_opd'
    ],

    'counter' => [
      'table' => 'trx_nomor_counter',
      'tahun_field' => 'tahun',
      'value_field' => 'last_number'
    ],

    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => ['nomor', 'perihal'],
        'order_by' => 'tgl_insert DESC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'id ASC'
      ]
    ]
  ],
  'ref_klasifikasi_keamanan' => [
    'table' => 'ref_klasifikasi_keamanan',
    'primary_key' => 'id',
    'modes' => [
      'dropdown' => [
        'select' => ['id', 'uraian'],
        'searchable' => ['uraian'],
        'order_by' => 'nama ASC'
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['uraian'],
        'order_by' => 'id ASC'
      ]
    ]
  ],
  'sk' => [
    'table' => 'trx_naskah_dinas',
    'primary_key' => 'id',
    // SK hanya untuk user login biasa
    'allowed_roles' => ['super_admin', 'admin_opd'],

    'soft_lock' => false,

    // ambil otomatis dari session
    'auto_session' => ['kd_opd', 'kd_wilayah', 'tahun'],

    'modes' => [

      // ==========================
      // MODE DEFAULT (TABLE VIEW)
      // ==========================
      'default' => [
        'select' => [
          'id',
          'jenis_id',
          'workflow_status',
          'tahun',
          'tgl_insert'
        ],
        'searchable' => [
          'workflow_status'
        ],
        'order_by' => 'id DESC',
        'where' => [
          'kd_opd' => 'user',
          'tahun' => 'user'
        ]
      ],

      // ==========================
      // MODE DROPDOWN (jika perlu)
      // ==========================
      'dropdown' => [
        'select' => [
          'id',
          'id as uraian'
        ],
        'searchable' => ['id'],
        'order_by' => 'id DESC',
        'where' => [
          'kd_opd' => 'user'
        ]
      ]
    ],
    // ==========================
    // IMPORT (tidak dipakai)
    // ==========================
    'import' => [
      'enabled' => false
    ]
  ],
  'pengaturan' => [

    'table' => 'pengaturan_neo',
    'primary_key' => 'id',

    'scope' => [
      'kd_wilayah' => 'session'
    ],

    'columns' => [
      'kd_wilayah' => ['type' => 'string'],
      'tahun' => ['type' => 'integer'],
      'tahun_renstra' => ['type' => 'integer'],
      'ukuran_kertas' => ['type' => 'string'],
      'orientasi_kertas' => ['type' => 'string'],
      'font_pdf' => ['type' => 'string'],
      'ukuran_font_pdf' => ['type' => 'decimal'],
      'lebar_kertas_mm' => ['type' => 'decimal'],
      'tinggi_kertas_mm' => ['type' => 'decimal'],
      'margin_atas_mm' => ['type' => 'decimal'],
      'margin_kanan_mm' => ['type' => 'decimal'],
      'margin_bawah_mm' => ['type' => 'decimal'],
      'margin_kiri_mm' => ['type' => 'decimal'],
      'margin_header_mm' => ['type' => 'decimal'],
      'margin_footer_mm' => ['type' => 'decimal'],
      'header_pdf_aktif' => ['type' => 'boolean'],
      'footer_pdf_aktif' => ['type' => 'boolean'],
      'tinggi_header_mm' => ['type' => 'decimal'],
      'tinggi_footer_mm' => ['type' => 'decimal'],
      'header_pdf_json' => ['type' => 'string'],
      'footer_pdf_json' => ['type' => 'string'],
      'awal_renja' => ['type' => 'datetime'],
      'akhir_renja' => ['type' => 'datetime'],
      'kunci' => ['type' => 'boolean'],
    ],

    'permissions' => [
      'super_admin'   => ['view', 'add', 'edit'],
      'admin_wilayah' => ['view', 'add', 'edit'],
      'admin_opd'     => ['view', 'edit'],
      'kepala_opd'    => ['view', 'edit'],
      'tapd'           => ['view', 'edit'],
      'viewer'        => ['view']
    ],
  ],

  /* =========================================================
   GROUP SUB KEGIATAN
   ---------------------------------------------------------
   Tabel agregasi sub kegiatan
   Digunakan oleh modul:
   - RENJA
   - RENJA_P
   - RKA
   - RKA_P
   - DPA
   - DPPA
========================================================= */

  'group_sub_kegiatan' => [

    /* =====================================================
       TABLE NAME
    ===================================================== */
    'table' => 'group_sub_kegiatan',

    /* =====================================================
       PRIMARY KEY
    ===================================================== */
    'primary_key' => 'id',

    /* =====================================================
       DEFAULT SORT
    ===================================================== */
    'default_sort' => [
      'column' => 'kd_sub_keg',
      'dir'    => 'asc'
    ],

    /* =====================================================
       FIELD SEARCHABLE
    ===================================================== */
    'searchable' => [
      'kd_sub_keg',
      'nama_sub_keg'
    ],

    /* =====================================================
       FIELD YANG TIDAK BOLEH DUPLIKAT
       (kombinasi unik logical key)
    ===================================================== */
    'not_duplicate' => [
      'kd_wilayah',
      'kd_opd',
      'tahun',
      'kd_sub_keg',
      'tahap'
    ],

    /* =====================================================
       FIELD YANG TIDAK BOLEH DIUPDATE
    ===================================================== */
    'readonly' => [
      'id',
      'tgl_insert',
      'username_insert'
    ],

    /* =====================================================
       FIELD YANG DIISI OTOMATIS OLEH ENGINE
    ===================================================== */
    'auto_fill' => [

      'session' => [
        'kd_wilayah',
        'kd_opd',
        'tahun'
      ],

      'insert' => [
        'tgl_insert',
        'username_insert'
      ],

      'update' => [
        'tgl_update',
        'username_update'
      ]

    ],

    /* =====================================================
       FIELD SOFT DELETE
    ===================================================== */
    'soft_delete' => [
      'field' => 'disable',
      'value_active' => 0,
      'value_deleted' => 1
    ],

    /*
    --------------------------------------------------------
    AUTO LOOKUP FIELD
    --------------------------------------------------------
    Mengisi nama_sub_keg dari tabel master
    */

    'lookup' => [

      'nama_sub_keg' => [

        'table' => 'sub_kegiatan',

        'value_field' => 'uraian',

        'match' => [

          'kode' => 'kd_sub_keg',

          'kd_wilayah' => 'kd_wilayah',

          'peraturan_id' => 'tahun'

        ]

      ]

    ]


  ],
  /*
|--------------------------------------------------------------------------
| PROFIL USER
|--------------------------------------------------------------------------
| Digunakan oleh menu profil
| Form edit profil mengambil data user login
*/
  'profil' => [

    // tabel asli database
    'table' => 'user_sesendok_biila',

    // primary key tabel
    'primary_key' => 'id',

    // semua user boleh akses profilnya
    'allowed_roles' => ['super_admin', 'admin', 'user'],

    // tidak menggunakan soft lock
    'soft_lock' => false,

    /*
  =====================================================
  MODE EDIT (dipakai oleh form profil)
  =====================================================
  */
    'modes' => [

      'edit' => [

        // field yang boleh dibaca
        'select' => [
          'id',
          'username',
          'email',
          'nama',
          'nip',
          'type_user',
          'photo',
          'tgl_login',
          'tahun',
          'kontak_person',
          'nama_org',
          'kd_opd',
          'font_size',
          'theme',
          'warna_tbl',
          'ket'
        ],

        // field yang boleh dicari
        'searchable' => [
          'nama',
          'username',
          'email'
        ],

        // default order
        'order_by' => 'id ASC'

      ]

    ]

  ],
  'wallchat' => [

    'table' => 'wallchat',

    'primary_key' => 'id',

    'fields' => [
      'user_id',
      'receiver_id',
      'parent_id',
      'content',
      'type'
    ],

    // audit field sesuai struktur tabel
    'audit' => [
      'insert_time' => 'tgl_insert',
      'insert_user' => 'username_insert',
      'update_time' => 'tgl_update',
      'update_user' => 'username_update'
    ],

    // soft delete sesuai tabel
    'soft_delete' => 'is_deleted'
  ],
  // FIX: config untuk halaman_berita mengikuti pola kontrak

  'halaman_berita' => [

    // --------------------------------------------------
    // tabel utama
    // --------------------------------------------------
    'table' => 'halaman_berita',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'tapd'],

    'sanitize' => [
      'konten' => ['html' => true]
    ],

    // --------------------------------------------------
    // auto session
    // --------------------------------------------------
    'auto_session' => [
      'kd_wilayah',
      'kd_opd'
    ],

    // --------------------------------------------------
    // filter default
    // --------------------------------------------------
    'where' => [
      'is_deleted' => 0 // FIX: penting agar tidak tampil data terhapus
    ],

    // --------------------------------------------------
    // dropdown
    // --------------------------------------------------
    'dropdown' => [
      'value' => 'id',
      'label' => 'judul'
    ],

    // --------------------------------------------------
    // request filter
    // --------------------------------------------------
    'req_filters' => [

      'aktif' => [
        'where' => ['is_deleted' => 0]
      ],

      'deleted' => [
        'where' => ['is_deleted' => 1]
      ]

    ],

    // --------------------------------------------------
    // MODES
    // --------------------------------------------------
    'modes' => [

      // ----------------------------------------------
      // default listing
      // ----------------------------------------------
      'default' => [

        'select' => [
          'id',
          'judul',
          'slug',
          'jenis_halaman',
          'aktif',
          'konten',
          'gambar',
          'keterangan',
          'kd_wilayah',
          'kd_opd',
          'tgl_insert',
          'username_insert'
        ],

        'searchable' => [
          'judul',
          'slug',
          'jenis_halaman',
          'aktif',
          'konten',
          'username_insert'
        ],

        'order_by' => 'id DESC'

      ],

      // ----------------------------------------------
      // detail (view/edit)
      // ----------------------------------------------
      'detail' => [

        'select' => [
          'id',
          'kd_wilayah',
          'kd_opd',
          'judul',
          'slug',
          'jenis_halaman',
          'aktif',
          'konten',
          'gambar',
          'keterangan',
          'tgl_insert',
          'username_insert',
          'tgl_update',
          'username_update',
          'is_deleted'
        ]

      ],

      // ----------------------------------------------
      // publik (opsional untuk frontend berita)
      // ----------------------------------------------
      'publik' => [

        'select' => [
          'id',
          'judul',
          'slug',
          'konten',
          'gambar',
          'tgl_insert'
        ],

        'where' => [
          'is_deleted' => 0
        ],
        'order_by' => 'tgl_insert DESC'
      ]
    ]
  ],
];

/* Canonical unified Standar Harga profiles (master_biaya). */
$standarHargaProfile = static function (string $tipe): array {
  return [
    'table' => 'master_biaya',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'auto_session' => ['kd_wilayah', 'tahun', 'peraturan_id'],
    'where' => ['tipe' => $tipe, 'is_deleted' => 0],
    'soft_delete' => [
      'field' => 'is_deleted',
      'value_active' => 0,
      'value_deleted' => 1
    ],
    'not_duplicate' => ['tipe', 'kode', 'kd_wilayah', 'tahun', 'peraturan_id'],
    'validation' => [
      'kode' => ['required'],
      'uraian' => ['required'],
      'satuan_id' => ['required', 'numeric'],
      'harga' => ['required', 'numeric']
    ],
    'dropdown' => [
      'value' => 'id',
      'label' => 'uraian',
      'searchable' => ['kode', 'uraian'],
      'order_by' => 'uraian ASC'
    ],
    'join' => [[
      'table' => 'satuan_neo',
      'on' => 'satuan_neo.id = master_biaya.satuan_id'
    ]],
    'import' => [
      'enabled' => true,
      'allowed_roles' => ['super_admin', 'admin_wilayah']
    ],
    'import_header_map' => ['satuan' => 'satuan'],
    'import_relations' => [
      'satuan' => [
        'table' => 'satuan_neo',
        'lookup' => 'uraian',
        'id' => 'id',
        'store' => 'satuan_id',
        'scope' => ['peraturan_id' => 'user']
      ]
    ],
    'modes' => [
      'default' => [
        'select' => [
          'master_biaya.id',
          'master_biaya.kode',
          'master_biaya.kode_aset',
          'master_biaya.kelompok_barang',
          'master_biaya.uraian',
          'master_biaya.spesifikasi',
          'satuan_neo.uraian AS satuan',
          'master_biaya.harga',
          'master_biaya.tkdn',
          'master_biaya.keterangan'
        ],
        'searchable' => [
          'master_biaya.kode',
          'master_biaya.kode_aset',
          'master_biaya.kelompok_barang',
          'master_biaya.uraian',
          'master_biaya.spesifikasi'
        ],
        'order_by' => 'master_biaya.kode ASC',
        'where' => ['tahun' => 'user', 'peraturan_id' => 'user']
      ],
      'edit' => [
        'select' => ['master_biaya.*'],
        'searchable' => ['master_biaya.kode', 'master_biaya.uraian'],
        'order_by' => 'master_biaya.id ASC'
      ]
    ]
  ];
};

foreach (['ssh', 'hspk', 'asb', 'sbu'] as $standarHargaType) {
  $profiles[$standarHargaType] = $standarHargaProfile($standarHargaType);
}

$profiles['akun_neo'] = $profiles['akun'];

/* Phase 3: canonical planning and budgeting document profiles. */
$documentProfile = static function (string $table, array $searchable = ['kd_sub_keg', 'uraian']): array {
  return [
    'table' => $table,
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
    'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
    'where' => ['is_deleted' => 0],
    'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
    'import' => ['enabled' => true, 'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd']],
    'modes' => [
      'default' => [
        'select' => ['*'],
        'searchable' => $searchable,
        'order_by' => 'id DESC',
        'where' => ['is_deleted' => 0]
      ],
      'edit' => ['select' => ['*'], 'searchable' => $searchable, 'order_by' => 'id ASC']
    ]
  ];
};
$profiles['rkpd'] = $documentProfile('rkpd_neo', ['kd_program', 'kd_kegiatan', 'kd_sub_keg', 'indikator', 'lokasi']);
$profiles['rkpd']['validation'] = ['kd_sub_keg' => ['required'], 'target' => ['required', 'numeric'], 'pagu' => ['required', 'numeric']];
$profiles['rkpd_p'] = $documentProfile('rkpd_p_neo', ['kd_program', 'kd_kegiatan', 'kd_sub_keg', 'indikator', 'lokasi']);
$profiles['renja'] = $documentProfile('renja_neo');
$profiles['rka'] = $documentProfile('rka_neo');
$profiles['dpa'] = $documentProfile('dpa_neo');
$profiles['renja_p'] = $documentProfile('renja_p_neo');
$profiles['rka_p'] = $documentProfile('rka_p_neo');
$profiles['dppa'] = $documentProfile('dppa_neo');

$profiles['kontrak'] = [
  'table' => 'kontrak_neo',
  'primary_key' => 'id',
  'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
  'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
  'where' => ['is_deleted' => 0],
  'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
  'not_duplicate' => ['kd_wilayah', 'kd_opd', 'tahun', 'nomor_kontrak'],
  'lookup' => ['nama_ppk' => ['table' => 'pejabat_tahunan_neo', 'value_field' => 'nama_pegawai', 'match' => ['id' => 'ppk_id']]],
  'validation' => ['rekanan_id' => ['required', 'numeric'], 'nomor_spk' => ['required'], 'nomor_spmk' => ['required'], 'nomor_kontrak' => ['required']],
  'req_filters' => ['kontrak_dpa' => ['where' => ['tahap' => 'dpa']], 'kontrak_dppa' => ['where' => ['tahap' => 'dppa']]],
  'dropdown' => ['value' => 'id', 'label' => 'nomor_kontrak'],
  'modes' => [
    'default' => [
      'select' => ['kontrak_neo.id', 'kontrak_neo.tahap', 'kontrak_neo.kd_sub_keg', 'kontrak_neo.nomor_spk', 'kontrak_neo.nomor_spmk', 'kontrak_neo.nomor_kontrak', 'kontrak_neo.uraian_kontrak', 'rekanan_neo.nama_perusahaan AS penyedia', 'kontrak_neo.total_anggaran', 'kontrak_neo.nilai_kontrak', 'kontrak_neo.tanggal_mulai', 'kontrak_neo.tanggal_selesai', 'kontrak_neo.status_kontrak'],
      'searchable' => ['kontrak_neo.nomor_spk', 'kontrak_neo.nomor_spmk', 'kontrak_neo.nomor_kontrak', 'kontrak_neo.uraian_kontrak', 'rekanan_neo.nama_perusahaan'],
      'where' => ['kontrak_neo.is_deleted' => 0],
      'order_by' => 'kontrak_neo.id DESC'
    ],
    'edit' => ['select' => ['kontrak_neo.*'], 'searchable' => ['kontrak_neo.nomor_kontrak'], 'order_by' => 'kontrak_neo.id ASC']
  ],
  'join' => [['table' => 'rekanan_neo', 'on' => 'rekanan_neo.id = kontrak_neo.rekanan_id']]
];
$profiles['realisasi'] = [
  'table' => 'daftar_realisasi_neo',
  'primary_key' => 'id',
  'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd'],
  'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
  'where' => ['is_deleted' => 0],
  'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
  'validation' => ['kontrak_id' => ['required', 'numeric'], 'tanggal' => ['required'], 'jumlah' => ['required', 'numeric'], 'progress_fisik' => ['required', 'numeric']],
  'modes' => [
    'default' => ['select' => ['daftar_realisasi_neo.id', 'kontrak_neo.nomor_kontrak', 'daftar_realisasi_neo.tanggal', 'daftar_realisasi_neo.periode', 'daftar_realisasi_neo.kd_sub_keg', 'daftar_realisasi_neo.kd_akun', 'daftar_realisasi_neo.jumlah', 'daftar_realisasi_neo.progress_fisik', 'daftar_realisasi_neo.nomor_bukti', 'daftar_realisasi_neo.keterangan'], 'searchable' => ['kontrak_neo.nomor_kontrak', 'daftar_realisasi_neo.kd_sub_keg', 'daftar_realisasi_neo.nomor_bukti', 'daftar_realisasi_neo.keterangan'], 'where' => ['daftar_realisasi_neo.is_deleted' => 0], 'order_by' => 'daftar_realisasi_neo.tanggal DESC'],
    'edit' => ['select' => ['daftar_realisasi_neo.*'], 'searchable' => ['daftar_realisasi_neo.keterangan'], 'order_by' => 'daftar_realisasi_neo.id ASC']
  ],
  'join' => [['table' => 'kontrak_neo', 'on' => 'kontrak_neo.id = daftar_realisasi_neo.kontrak_id']]
];
$profiles['rab_kontrak'] = ['table' => 'rab_paket_neo', 'primary_key' => 'id', 'dropdown' => ['value' => 'id', 'label' => 'uraian', 'searchable' => ['nomor', 'uraian'], 'order_by' => 'nomor ASC'], 'modes' => ['dropdown' => ['select' => ['id', 'kontrak_id', 'nomor', 'uraian', 'satuan', 'jumlah_negoisasi', 'bobot'], 'searchable' => ['nomor', 'uraian'], 'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'is_deleted' => 0], 'order_by' => 'nomor ASC']]];
foreach (['dpa', 'dppa'] as $budgetDropdown) {
  $profiles[$budgetDropdown]['dropdown'] = ['value' => 'id', 'label' => 'uraian', 'searchable' => ['kd_sub_keg', 'uraian'], 'order_by' => 'id DESC'];
}

$officialDropdown = static function (string $type): array {
  return [
    'table' => 'pejabat_tahunan_neo',
    'primary_key' => 'id',
    'dropdown' => ['value' => 'id', 'label' => 'nama_pegawai', 'searchable' => ['nama_pegawai', 'nip', 'kd_sub_keg'], 'order_by' => 'nama_pegawai ASC'],
    'modes' => ['dropdown' => [
      'select' => ['id', 'nama_pegawai', 'nip', 'jenis_pejabat', 'kd_sub_keg'],
      'searchable' => ['nama_pegawai', 'nip', 'kd_sub_keg'],
      'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'jenis_pejabat' => $type, 'berlaku_mulai <=' => date('Y-m-d'), 'berlaku_sampai >=' => date('Y-m-d'), 'is_deleted' => 0],
      'order_by' => 'nama_pegawai ASC'
    ]]
  ];
};
$profiles['pejabat_ppk'] = $officialDropdown('PPK');
$profiles['pejabat_pptk'] = $officialDropdown('PPTK');

$profiles['absensi'] = [
  'table' => 'absensi_pegawai_neo',
  'primary_key' => 'id',
  'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd'],
  'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
  'where' => ['is_deleted' => 0],
  'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
  'validation' => ['pegawai_id' => ['required', 'numeric'], 'tanggal' => ['required'], 'status' => ['required']],
  'import' => ['enabled' => true, 'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd']],
  'modes' => ['default' => ['select' => ['absensi_pegawai_neo.id', 'db_asn_pemda_neo.nama AS pegawai', 'absensi_pegawai_neo.tanggal', 'absensi_pegawai_neo.jam_masuk', 'absensi_pegawai_neo.jam_pulang', 'absensi_pegawai_neo.status', 'absensi_pegawai_neo.keterangan'], 'searchable' => ['db_asn_pemda_neo.nama', 'absensi_pegawai_neo.status'], 'where' => ['absensi_pegawai_neo.kd_wilayah' => 'user', 'absensi_pegawai_neo.kd_opd' => 'user', 'absensi_pegawai_neo.tahun' => 'user', 'absensi_pegawai_neo.is_deleted' => 0], 'order_by' => 'absensi_pegawai_neo.tanggal DESC'], 'edit' => ['select' => ['absensi_pegawai_neo.*'], 'searchable' => ['absensi_pegawai_neo.status'], 'order_by' => 'absensi_pegawai_neo.id DESC']],
  'join' => [['table' => 'db_asn_pemda_neo', 'on' => 'db_asn_pemda_neo.id=absensi_pegawai_neo.pegawai_id']]
];
$profiles['penugasan_subkegiatan'] = [
  'table' => 'user_subkegiatan_neo',
  'primary_key' => 'id',
  'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd'],
  'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'],
  'where' => ['is_deleted' => 0],
  'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1],
  'validation' => ['user_id' => ['required', 'numeric'], 'kd_sub_keg' => ['required'], 'peran' => ['required'], 'berlaku_mulai' => ['required'], 'berlaku_sampai' => ['required']],
  'modes' => ['default' => ['select' => ['user_subkegiatan_neo.*', "CASE WHEN EXISTS (SELECT 1 FROM sub_kegiatan_renstra_neo sr JOIN rekening_kegiatan rr ON rr.id=sr.master_sub_kegiatan_id JOIN kegiatan_renstra_neo kr ON kr.id=sr.kegiatan_renstra_id AND kr.is_deleted=0 JOIN program_renstra_neo pr ON pr.id=kr.program_id AND pr.is_deleted=0 JOIN sasaran_renstra_neo sa ON sa.id=pr.sasaran_id AND sa.is_deleted=0 JOIN tujuan_renstra_neo tr ON tr.id=sa.tujuan_id AND tr.is_deleted=0 JOIN misi_renstra_neo mr ON mr.id=tr.misi_id AND mr.is_deleted=0 JOIN renstra_neo re ON re.id=mr.renstra_id AND re.is_deleted=0 WHERE sr.is_deleted=0 AND rr.kode=user_subkegiatan_neo.kd_sub_keg AND re.kd_wilayah=user_subkegiatan_neo.kd_wilayah AND re.kd_opd=user_subkegiatan_neo.kd_opd) THEN 'Ada dalam Renstra' ELSE 'Tidak ada dalam Renstra' END AS status_renstra"], 'searchable' => ['kd_sub_keg', 'peran', 'keterangan'], 'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'is_deleted' => 0], 'order_by' => 'id DESC']]
];
$profiles['user_opd_dropdown'] = [
  'table'=>'user_sesendok_biila','primary_key'=>'id','where'=>['disable'=>0],
  'dropdown'=>['value'=>'id','label'=>'nama','label_fields'=>['nama','type_user'],'searchable'=>['nama','nip','username']]
];
$profiles['sub_kegiatan_penugasan'] = [
  'table'=>'rekening_kegiatan','primary_key'=>'id','where'=>['level'=>'sub_kegiatan'],
  'dropdown'=>['value'=>'kode','label'=>'uraian','label_fields'=>['kode','uraian'],'searchable'=>['kode','uraian']]
];
$profiles['kop_surat'] = ['table' => 'kop_surat_neo', 'primary_key' => 'id', 'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd'], 'auto_session' => ['kd_wilayah', 'kd_opd', 'tahun'], 'where' => ['is_deleted' => 0], 'soft_delete' => ['field' => 'is_deleted', 'value_active' => 0, 'value_deleted' => 1], 'validation' => ['nama_pemerintah' => ['required'], 'nama_opd' => ['required']], 'modes' => ['default' => ['select' => ['*'], 'searchable' => ['nama_pemerintah', 'nama_opd', 'alamat'], 'where' => ['kd_wilayah' => 'user', 'kd_opd' => 'user', 'tahun' => 'user', 'is_deleted' => 0], 'order_by' => 'id DESC']]];

$profiles['rpjmd_kabupaten']=['table'=>'rpjmd_kabupaten_neo','primary_key'=>'id','auto_session'=>['kd_wilayah'],'soft_delete'=>['field'=>'is_deleted','value_active'=>0,'value_deleted'=>1],'validation'=>['nama_dokumen'=>['required'],'berlaku_mulai'=>['required'],'berlaku_sampai'=>['required']],'modes'=>['default'=>['select'=>['id','nama_dokumen','nomor_perda','berlaku_mulai','berlaku_sampai','status','keterangan'],'searchable'=>['nama_dokumen','nomor_perda','visi'],'where'=>['kd_wilayah'=>'user','is_deleted'=>0],'order_by'=>'berlaku_mulai DESC'],'edit'=>['select'=>['*'],'searchable'=>['nama_dokumen'],'order_by'=>'id DESC']]];
$profiles['usulan_pembangunan']=['table'=>'usulan_pembangunan_neo','primary_key'=>'id','auto_session'=>['kd_wilayah','kd_opd','tahun'],'soft_delete'=>['field'=>'is_deleted','value_active'=>0,'value_deleted'=>1],'validation'=>['jenis_usulan'=>['required'],'pengusul'=>['required'],'uraian'=>['required']],'modes'=>['default'=>['select'=>['id','jenis_usulan','pengusul','uraian','lokasi','perkiraan_anggaran','prioritas','status'],'searchable'=>['pengusul','uraian','lokasi'],'where'=>['kd_wilayah'=>'user','tahun'=>'user','is_deleted'=>0],'order_by'=>'prioritas ASC,id DESC'],'edit'=>['select'=>['*'],'searchable'=>['uraian'],'order_by'=>'id DESC']]];
$profiles['evaluasi_renja']=['table'=>'evaluasi_renja_neo','primary_key'=>'id','auto_session'=>['kd_wilayah','kd_opd','tahun'],'soft_delete'=>['field'=>'is_deleted','value_active'=>0,'value_deleted'=>1],'validation'=>['triwulan'=>['required','numeric'],'renja_kinerja_id'=>['required','numeric'],'kd_sub_keg'=>['required'],'indikator'=>['required']],'modes'=>['default'=>['select'=>['id','triwulan','renja_kinerja_id','kd_sub_keg','indikator','target_tahunan','realisasi_kumulatif','pagu_anggaran','realisasi_anggaran_kumulatif','status'],'searchable'=>['kd_sub_keg','indikator'],'where'=>['kd_wilayah'=>'user','kd_opd'=>'user','tahun'=>'user','is_deleted'=>0],'order_by'=>'triwulan DESC,kd_sub_keg'],'edit'=>['select'=>['*'],'searchable'=>['indikator'],'order_by'=>'id DESC']]];

$sakipProfile=static function(string $table,array $select,array $searchable,array $validation=[],array $auto=['kd_wilayah','kd_opd','tahun']):array{return ['table'=>$table,'primary_key'=>'id','auto_session'=>$auto,'soft_delete'=>['field'=>'is_deleted','value_active'=>0,'value_deleted'=>1],'validation'=>$validation,'modes'=>['default'=>['select'=>$select,'searchable'=>$searchable,'where'=>['is_deleted'=>0],'order_by'=>'id DESC'],'edit'=>['select'=>['*'],'searchable'=>$searchable,'order_by'=>'id DESC']]];};
$profiles['iku_opd']=$sakipProfile('iku_opd_neo',['id','program_renstra_id','kode_iku','nama_indikator','satuan','polaritas','baseline','target_t1','target_t2','target_t3','target_t4','target_t5','target_akhir','frekuensi_pengukuran','status'],['kode_iku','nama_indikator','sumber_data'],['kode_iku'=>['required'],'nama_indikator'=>['required'],'satuan'=>['required']],['kd_wilayah','kd_opd']);
$profiles['pohon_kinerja']=$sakipProfile('pohon_kinerja_neo',['id','parent_id','kode_kinerja','uraian_kinerja','jenjang','jenis_kinerja','indikator','target','satuan','sumber_ref','status'],['kode_kinerja','uraian_kinerja','indikator'],['kode_kinerja'=>['required'],'uraian_kinerja'=>['required'],'jenjang'=>['required'],'jenis_kinerja'=>['required']],['kd_wilayah','kd_opd','tahun']);
$profiles['perjanjian_kinerja']=$sakipProfile('perjanjian_kinerja_neo',['id','nomor_dokumen','tanggal_dokumen','tahun','jenis','pihak_pertama_jabatan','pihak_kedua_jabatan','status','tanggal_penetapan'],['nomor_dokumen','pihak_pertama_jabatan','pihak_kedua_jabatan'],['nomor_dokumen'=>['required'],'tanggal_dokumen'=>['required'],'pihak_pertama_pegawai_id'=>['required','numeric'],'pihak_kedua_pegawai_id'=>['required','numeric']],['kd_wilayah','kd_opd','tahun']);
$profiles['perjanjian_kinerja_detail']=$sakipProfile('perjanjian_kinerja_detail_neo',['id','perjanjian_kinerja_id','nomor_urut','sasaran_kinerja','indikator_kinerja','satuan','target','program_kegiatan','kd_sub_keg','anggaran','sumber_anggaran'],['sasaran_kinerja','indikator_kinerja','program_kegiatan','kd_sub_keg'],['perjanjian_kinerja_id'=>['required','numeric'],'sasaran_kinerja'=>['required'],'indikator_kinerja'=>['required'],'target'=>['required','numeric']],['kd_wilayah','kd_opd','tahun']);
$profiles['pengukuran_kinerja']=$sakipProfile('pengukuran_kinerja_neo',['id','perjanjian_kinerja_detail_id','periode','nomor_periode','target_periode','realisasi_periode','realisasi_kumulatif','capaian_persen','status'],['analisis_capaian','kendala','tindak_lanjut'],['perjanjian_kinerja_detail_id'=>['required','numeric'],'periode'=>['required'],'nomor_periode'=>['required','numeric']],['kd_wilayah','kd_opd','tahun']);
$profiles['evaluasi_renstra']=$sakipProfile('evaluasi_renstra_neo',['id','tahun_evaluasi','jenis_evaluasi','indikator','satuan','target_kumulatif','realisasi_kumulatif','capaian_persen','pagu_anggaran','realisasi_anggaran','status'],['indikator','faktor_pendorong','faktor_penghambat','rekomendasi_reviu'],['renstra_id'=>['required','numeric'],'tahun_evaluasi'=>['required','numeric'],'jenis_evaluasi'=>['required'],'indikator'=>['required']],['kd_wilayah','kd_opd']);
$profiles['renja_kinerja']=$sakipProfile('renja_sub_kegiatan_kinerja_neo',['id','tahun','kd_sub_keg','indikator_keluaran','satuan','target','lokasi','kelompok_sasaran','pagu_indikatif','prakiraan_maju_target','prakiraan_maju_pagu','status'],['kd_sub_keg','indikator_keluaran','lokasi','kelompok_sasaran'],['kd_sub_keg'=>['required'],'indikator_keluaran'=>['required'],'satuan'=>['required'],'target'=>['required','numeric']],['kd_wilayah','kd_opd','tahun']);
$profiles['renstra_dropdown']=['table'=>'renstra_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown'=>['value'=>'id','label'=>'visi','searchable'=>['visi']]];
$profiles['sasaran_renstra_dropdown']=['table'=>'sasaran_renstra_neo','primary_key'=>'id','where'=>['sasaran_renstra_neo.is_deleted'=>0,'renstra_neo.kd_wilayah'=>'user','renstra_neo.kd_opd'=>'user'],'join'=>[['table'=>'tujuan_renstra_neo','on'=>'tujuan_renstra_neo.id=sasaran_renstra_neo.tujuan_id'],['table'=>'misi_renstra_neo','on'=>'misi_renstra_neo.id=tujuan_renstra_neo.misi_id'],['table'=>'renstra_neo','on'=>'renstra_neo.id=misi_renstra_neo.renstra_id']],'dropdown'=>['value'=>'id','label'=>'nama_sasaran','label_fields'=>['kode_sasaran','nama_sasaran'],'searchable'=>['kode_sasaran','nama_sasaran']]];
$profiles['program_renstra_dropdown']=['table'=>'program_renstra_neo','primary_key'=>'id','where'=>['program_renstra_neo.is_deleted'=>0,'renstra_neo.kd_wilayah'=>'user','renstra_neo.kd_opd'=>'user'],'join'=>[['table'=>'sasaran_renstra_neo','on'=>'sasaran_renstra_neo.id=program_renstra_neo.sasaran_id'],['table'=>'tujuan_renstra_neo','on'=>'tujuan_renstra_neo.id=sasaran_renstra_neo.tujuan_id'],['table'=>'misi_renstra_neo','on'=>'misi_renstra_neo.id=tujuan_renstra_neo.misi_id'],['table'=>'renstra_neo','on'=>'renstra_neo.id=misi_renstra_neo.renstra_id']],'dropdown'=>['value'=>'id','label'=>'uraian','label_fields'=>['kode_program','uraian'],'searchable'=>['kode_program','uraian']]];
$profiles['iku_dropdown']=['table'=>'iku_opd_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown_status_filter'=>false,'dropdown'=>['value'=>'id','label'=>'nama_indikator','label_fields'=>['kode_iku','nama_indikator'],'searchable'=>['kode_iku','nama_indikator']]];
$profiles['pohon_kinerja_dropdown']=['table'=>'pohon_kinerja_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown_status_filter'=>false,'dropdown'=>['value'=>'id','label'=>'uraian_kinerja','label_fields'=>['kode_kinerja','uraian_kinerja'],'searchable'=>['kode_kinerja','uraian_kinerja']]];
$profiles['pk_dropdown']=['table'=>'perjanjian_kinerja_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown_status_filter'=>false,'dropdown'=>['value'=>'id','label'=>'nomor_dokumen','searchable'=>['nomor_dokumen']]];
$profiles['pk_detail_dropdown']=['table'=>'perjanjian_kinerja_detail_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown'=>['value'=>'id','label'=>'indikator_kinerja','label_fields'=>['sasaran_kinerja','indikator_kinerja'],'searchable'=>['sasaran_kinerja','indikator_kinerja']]];
$profiles['sakip_sumber_dropdown']=['table'=>'sakip_sumber_kinerja_v','primary_key'=>'value_key','where'=>['kd_wilayah'=>'user','kd_opd'=>'user'],'dropdown'=>['value'=>'value_key','label'=>'uraian','searchable'=>['uraian']]];
$profiles['sub_kegiatan_renstra_dropdown']=['table'=>'sub_kegiatan_renstra_neo','primary_key'=>'id','where'=>['renstra_neo.kd_wilayah'=>'user','renstra_neo.kd_opd'=>'user','sub_kegiatan_renstra_neo.is_deleted'=>0],'join'=>[['table'=>'rekening_kegiatan','on'=>'rekening_kegiatan.id=sub_kegiatan_renstra_neo.master_sub_kegiatan_id'],['table'=>'kegiatan_renstra_neo','on'=>'kegiatan_renstra_neo.id=sub_kegiatan_renstra_neo.kegiatan_renstra_id'],['table'=>'program_renstra_neo','on'=>'program_renstra_neo.id=kegiatan_renstra_neo.program_id'],['table'=>'sasaran_renstra_neo','on'=>'sasaran_renstra_neo.id=program_renstra_neo.sasaran_id'],['table'=>'tujuan_renstra_neo','on'=>'tujuan_renstra_neo.id=sasaran_renstra_neo.tujuan_id'],['table'=>'misi_renstra_neo','on'=>'misi_renstra_neo.id=tujuan_renstra_neo.misi_id'],['table'=>'renstra_neo','on'=>'renstra_neo.id=misi_renstra_neo.renstra_id']],'dropdown'=>['value'=>'id','label'=>'lokasi','label_fields'=>['rekening_kegiatan.kode','rekening_kegiatan.uraian'],'searchable'=>['rekening_kegiatan.kode','rekening_kegiatan.uraian']]];
$profiles['renja_kinerja_dropdown']=['table'=>'renja_sub_kegiatan_kinerja_neo','primary_key'=>'id','where'=>['is_deleted'=>0],'dropdown'=>['value'=>'id','label'=>'indikator_keluaran','label_fields'=>['kd_sub_keg','indikator_keluaran'],'searchable'=>['kd_sub_keg','indikator_keluaran']]];

foreach (['akun', 'mapping', 'aset', 'organisasi', 'satuan', 'satuan_teks', 'sumber_dana', 'wilayah', 'peraturan', 'rekanan', 'rekening_kegiatan', 'sbu', 'ssh', 'hspk', 'asb', 'periode_rpjmd', 'rpjmd_kabupaten', 'usulan_pembangunan', 'evaluasi_renja', 'misi_renstra_neo', 'tujuan_renstra_neo', 'sasaran_renstra_neo', 'indikator_sasaran_renstra_neo', 'program_renstra_neo', 'indikator_program_renstra_neo', 'kegiatan_renstra_neo', 'sub_kegiatan_renstra_neo', 'renstra_neo', 'rkpd', 'renja', 'rka', 'dpa', 'rkpd_p', 'renja_p', 'rka_p', 'dppa', 'kontrak', 'rab_kontrak', 'realisasi', 'asn', 'pppk', 'riwayat_jabatan', 'riwayat_pangkat', 'cuti', 'sk_pegawai', 'pejabat_tahunan', 'absensi'] as $importKey) {
  if (isset($profiles[$importKey])) $profiles[$importKey]['import'] = ['enabled' => true, 'allowed_roles' => ['super_admin', 'admin_wilayah', 'admin_opd']];
}

/* Correct legacy Renstra dropdown fields that referenced non-existent columns. */
$profiles['program_renstra_neo']['modes']['dropdown']['order_by'] = 'uraian ASC';
unset($profiles['indikator_program_renstra_neo']['modes']['dropdown']);
// $profiles['sub_kegiatan_ref'] = $profiles['sub_kegiatan'];
// $profiles['sub_kegiatan_ref']['module_alias'] = 'sub_kegiatan_ref';
return $profiles;
