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
    'dropdown_sources' => [

      'master_biaya' => [

        'table' => 'master_biaya',

        'value' => 'id',

        'label' => 'uraian_barang'

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
        'order_by' => 'uraian ASC'
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['uraian'],
        'order_by' => 'uraian ASC',
        'scope' => [
          'peraturan_id' => 'user' // otomatis ambil dari session user
        ]
      ]
    ]
  ],



  'sumber_dana' => [
    'table' => 'sumber_dana_neo',
    'primary_key' => 'id',
    'allowed_roles' => ['super_admin', 'admin_wilayah'],
    'soft_lock' => true,
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
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
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
    'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
    'validation' => [
      'nip' => ['required', 'unique']
    ],
    'modes' => [
      'dropdown' => [
        'select' => ['id', 'uraian', 'nip', 'alamat', 'golongan', 'ruang', 'jabatan', 'keterangan'],
        'searchable' => ['kd_opd', 'uraian', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'default' => [
        'select' => ['*'],
        'searchable' => ['id', 'kd_opd', 'uraian', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'kepegawaian' => [
        'select' => ['id', 'uraian', 'nip', 'alamat', 'golongan', 'ruang', 'jabatan', 'keterangan'],
        'searchable' => ['id', 'kd_opd', 'uraian', 'nip', 'npwp'],
        'order_by' => 'nama ASC'
      ],
      'edit' => [
        'select' => ['*'],
        'searchable' => ['*'],
        'order_by' => 'nama ASC'
      ]
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
        'local_key' => 'parent_kode'
      ]
    ],

    // --------------------------------------------------
    // filter default
    // --------------------------------------------------
    'where' => [
      'status' => 1
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


  'trx_naskah_dinas' => [
    'table' => 'trx_naskah_dinas',
    'primary_key' => 'id',

    // ===================================================
    // TAMBAHAN RELASI WRITE
    // ===================================================
    'write_relations' => [
      'trx_naskah_struktur' => [
        'fk' => 'naskah_id',
        'source' => 'struktur_json',
        'type' => 'single'
      ],

      'trx_naskah_meta' => [
        'fk' => 'naskah_id',
        'source' => '*', // seluruh request
        'type' => 'json'
      ]

    ],
    'versioning' => [
      'mode' => 'insert_on_change',
      'fields' => ['nomor', 'tanggal_surat']
    ],
    // JANGAN DUPLIKAT
    'not_duplicate' => [
      'nomor',
      'tanggal_surat',
      'kd_wilayah',
      'tahun',
      'kd_opd'
    ],
    // ===================================================
    // COUNTER
    // ===================================================
    'counter' => [
      'table' => 'trx_nomor_counter',
      'tahun_field' => 'tahun',
      'value_field' => 'last_number'
    ],

    'modes' => [
      'default' => [
        'select' => [
          'id',
          'nomor',
          'tanggal_surat',
          'perihal',
          'status',
          'file_pdf',
          'tgl_insert'
        ],
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
      'awal_renja' => ['type' => 'datetime'],
      'akhir_renja' => ['type' => 'datetime'],
      'kunci' => ['type' => 'boolean'],
    ],

    'permissions' => [
      'super_admin'   => ['view', 'add', 'edit'],
      'admin_wilayah' => ['view', 'add', 'edit'],
      'admin_opd'     => ['view'],
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
          'kd_opd'
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

    'primary' => 'id',

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
];
$profiles['akun_neo'] = $profiles['akun'];
// $profiles['sub_kegiatan_ref'] = $profiles['sub_kegiatan'];
// $profiles['sub_kegiatan_ref']['module_alias'] = 'sub_kegiatan_ref';
return $profiles;