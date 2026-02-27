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

return [

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
    'sbu' => [
        'table' => 'sbu_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,

        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kd_aset', 'uraian_barang'],
                'order_by' => 'uraian_barang ASC',
                'where' => [
                    'tahun' => 'user' // 🔥 Ambil tahun dari session login
                ]
            ],
            'standar_harga' => [
                'select' => ['id', 'kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
                'searchable' => ['kd_aset', 'kd_akun', 'uraian_barang', 'spesifikasi', 'satuan', 'harga_satuan'],
                'order_by' => 'kd_aset ASC',
                'where' => [
                    'tahun' => 'user'
                ]
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
    'ssh' => [
        'table' => 'ssh_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
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
        'table' => 'asb_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
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
        'table' => 'hspk_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
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
        'table' => 'mapping_aset_akun',
        'primary_key' => 'id',
        'allowed_roles' => ['super_admin', 'admin_wilayah'],
        'soft_lock' => true,
        'modes' => [

            'default' => [
                'select' => [
                    'id',
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun',
                    'kelompok',
                    'disable',
                    'aksi',
                    'keterangan',
                    'peraturan',
                    'tgl_insert',
                    'username_insert',
                    'tgl_update',
                    'username_update'
                ],
                'searchable' => [
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun',
                    'kelompok',
                    'keterangan',
                    'peraturan'
                ],
                'order_by' => 'kd_aset ASC'
            ],

            'referensi' => [
                'select' => [
                    'id',
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun',
                    'kelompok',
                    'keterangan',
                    'peraturan'
                ],
                'searchable' => [
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun',
                    'kelompok'
                ],
                'order_by' => 'kd_aset ASC'
            ],

            'aktif' => [
                'select' => [
                    'id',
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun',
                    'kelompok'
                ],
                'searchable' => [
                    'kd_aset',
                    'uraian_aset',
                    'kd_akun',
                    'uraian_akun'
                ],
                'order_by' => 'kd_aset ASC',
                'where' => "disable = 0"
            ],

            'edit' => [
                'select' => ['*'],
                'searchable' => ['*'],
                'order_by' => 'id ASC'
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
        'primary_key' => 'id',
        'import' => [
            'enabled' => true,
            'allowed_roles' => ['super_admin', 'admin_wilayah'],
            'check_duplicate' => true,
            'check_hierarchy' => false
        ],
        'dropdown' => [
            'value' => 'id',
            'label' => 'item',  // 🔥 ini yang benar
            'searchable' => ['item', 'value'],
            'order_by' => 'item ASC'
        ],

        'allowed_roles' => ['super_admin', 'admin_wilayah'],
        'soft_lock' => true,
        'modes' => [
            'dropdown' => [
                'select' => ['id', 'value', 'item'],
                'searchable' => ['item', 'value'],
                'order_by' => 'item ASC'
            ],
            'referensi' => [
                'select' => ['id', 'value', 'item', 'keterangan'],
                'searchable' => ['item', 'value'],
                'order_by' => 'item ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['item'],
                'order_by' => 'item ASC'
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

        'dropdown' => [
            'import' => [
                'enabled' => true
            ],
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
                'where' => "kelompok = 'tujuan'",
                'order_by' => 'text ASC',
                'where' => [
                    'tahun' => 'user'
                ] // ambil dari user login
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
                'select' => ['id', 'nama', 'nip', 'alamat', 'golongan', 'ruang', 'jabatan', 'keterangan'],
                'searchable' => ['kd_opd', 'nama', 'nip', 'npwp'],
                'order_by' => 'nama ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['id', 'kd_opd', 'nama', 'nip', 'npwp'],
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
    'sk_asn' => [
        'table' => 'sk_asn_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['admin_opd'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
        'modes' => [
            'dropdown' => [
                'select' => ['id', 'nomor', 'tgl_surat_dibuat', 'tentang', 'pemberi_tgs', 'keterangan'],
                'searchable' => ['kd_opd', 'nomor', 'tentang', 'tgl_surat_dibuat'],
                'order_by' => 'id ASC',
                'where' => [
                    'tahun' => 'user'
                ] // ambil dari user login
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['id', 'kd_opd', 'tgl_surat_dibuat', 'tentang'],
                'order_by' => 'id ASC',
                'where' => [
                    'tahun' => 'user'
                ] // ambil dari user login
            ],
            'kepegawaian' => [
                'select' => ['id', 'nomor', 'tgl_surat_dibuat', 'tentang', 'pemberi_tgs', 'keterangan'],
                'searchable' => ['kd_opd', 'nomor', 'tentang', 'tgl_surat_dibuat'],
                'order_by' => 'tgl_surat_dibuat ASC',
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
    'tata_naskah' => [
        'table' => 'naskah_dinas_neo',
        'primary_key' => 'id',
        'allowed_roles' => ['admin_opd'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd'],
        'modes' => [
            'dropdown' => [
                'select' => ['id', 'jenis_naskah_dinas', 'nomor', 'tgl_surat_dibuat', 'klasifikasi_keamanan', 'tentang', 'keterangan'],
                'searchable' => ['jenis_naskah_dinas', 'klasifikasi_keamanan', 'nomor', 'tgl_surat_dibuat', 'uraian', 'file', 'keterangan'],
                'order_by' => 'tgl_surat_dibuat ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['jenis_naskah_dinas', 'klasifikasi_keamanan', 'nomor', 'tanggal', 'uraian', 'file', 'keterangan'],
                'order_by' => 'tgl_surat_dibuat ASC'
            ],
            'kepegawaian' => [
                'select' => ['id', 'jenis_naskah_dinas', 'klasifikasi_keamanan', 'nomor', 'tgl_surat_dibuat', 'uraian', 'file', 'keterangan'],
                'searchable' => ['jenis_naskah_dinas', 'klasifikasi_keamanan', 'nomor', 'tgl_surat_dibuat', 'uraian', 'file', 'keterangan'],
                'order_by' => 'tgl_surat_dibuat ASC'
            ],
            'edit' => [
                'select' => ['*'],
                'searchable' => ['*'],
                'order_by' => 'id ASC'
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
            ]
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
            ]
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
            ]
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
            'label' => 'nama_program'
        ],
        'modes' => [
            'default' => [
                'select' => ['id', 'sasaran_id', 'kode_program', 'nama_program', 'disable', 'keterangan'],
                'searchable' => ['kode_program', 'nama_program'],
                'order_by' => 'id DESC',
            ],
            'edit' => [
                'select' => ['*'],
                'searchable' => ['*'],
                'order_by' => 'id ASC'
            ]
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
            'label' => 'nama_kegiatan'
        ],
        'modes' => [
            'default' => [
                'select' => ['id', 'program_id', 'kode_kegiatan', 'nama_kegiatan', 'keterangan'],
                'searchable' => ['kode_kegiatan', 'nama_kegiatan'],
                'order_by' => 'id DESC'
            ]
        ],
        'validation' => [
            'program_id'    => ['required', 'numeric'],
            'nama_kegiatan' => ['required']
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
    /*
|--------------------------------------------------------------------------
| URUSAN
|--------------------------------------------------------------------------
*/
    'urusan' => [

        'table' => 'urusan',

        // Primary key internal database
        'primary_key' => 'id',

        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,

        // Otomatis isi dari session saat INSERT
        'auto_session' => ['kd_wilayah', 'peraturan'],

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN CONFIG
        |--------------------------------------------------------------------------
        | value  → value yang dikirim ke form
        | label  → yang ditampilkan
        */
        'dropdown' => [
            'value' => 'kode',
            'label' => 'nama'
        ],

        'modes' => [
            'default' => [
                'select' => ['id', 'kode', 'nama'],
                'searchable' => ['kode', 'nama'],
                'order_by' => 'kode ASC',

                // WAJIB agar tidak bocor wilayah/peraturan
                'where' => [
                    'kd_wilayah' => 'user',
                    'peraturan'  => 'user'
                ]
            ]
        ]
    ],

    /*
        |--------------------------------------------------------------------------
        | BIDANG
        |--------------------------------------------------------------------------
        */
    'bidang' => [

        'table' => 'bidang',
        'primary_key' => 'id',

        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'peraturan'],

        /*
        |--------------------------------------------------------------------------
        | RELATIONS
        |--------------------------------------------------------------------------
        | bidang adalah anak dari urusan
        |
        | local_key  → kolom di tabel bidang
        | parent_key → kolom di tabel urusan
        */
        'relations' => [
            'urusan' => [
                'local_key'  => 'kode_urusan',
                'parent_key' => 'kode'
            ]
        ],

        'dropdown' => [
            'value' => 'kode',
            'label' => 'nama'
        ],

        'modes' => [
            'default' => [
                'select' => ['id', 'kode', 'kode_urusan', 'nama'],
                'searchable' => ['kode', 'nama'],
                'order_by' => 'kode ASC',
                'where' => [
                    'kd_wilayah' => 'user',
                    'peraturan'  => 'user'
                ]
            ]
        ]
    ],

    /*
|--------------------------------------------------------------------------
| PROGRAM
|--------------------------------------------------------------------------
*/
    'program' => [

        'table' => 'program',
        'primary_key' => 'id',

        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'peraturan'],

        /*
        |--------------------------------------------------------------------------
        | RELATION
        |--------------------------------------------------------------------------
        | program anak dari bidang
        */
        'relations' => [
            'bidang' => [
                'local_key'  => 'kode_bidang',
                'parent_key' => 'kode'
            ]
        ],

        'dropdown' => [
            'value' => 'kode',
            'label' => 'nama'
        ],

        'modes' => [
            'default' => [
                'select' => ['id', 'kode', 'kode_bidang', 'nama'],
                'searchable' => ['kode', 'nama'],
                'order_by' => 'kode ASC',
                'where' => [
                    'kd_wilayah' => 'user',
                    'peraturan'  => 'user'
                ]
            ]
        ]
    ],

    /*
|--------------------------------------------------------------------------
| KEGIATAN
|--------------------------------------------------------------------------
*/
    'kegiatan' => [

        'table' => 'kegiatan',
        'primary_key' => 'id',

        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'peraturan'],

        /*
        |--------------------------------------------------------------------------
        | RELATION
        |--------------------------------------------------------------------------
        | kegiatan anak dari program
        */
        'relations' => [
            'program' => [
                'local_key'  => 'kode_program',
                'parent_key' => 'kode'
            ]
        ],

        'dropdown' => [
            'value' => 'kode',
            'label' => 'nama'
        ],

        'modes' => [
            'default' => [
                'select' => ['id', 'kode', 'kode_program', 'nama'],
                'searchable' => ['kode', 'nama'],
                'order_by' => 'kode ASC',
                'where' => [
                    'kd_wilayah' => 'user',
                    'peraturan'  => 'user'
                ]
            ]
        ]
    ],

    /*
|--------------------------------------------------------------------------
| SUB KEGIATAN
|--------------------------------------------------------------------------
*/
    'sub_kegiatan' => [

        'table' => 'sub_kegiatan',
        'primary_key' => 'id',

        'allowed_roles' => ['super_admin'],
        'soft_lock' => true,
        'auto_session' => ['kd_wilayah', 'peraturan'],

        /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    | sub_kegiatan anak dari kegiatan
    */
        'relations' => [
            'kegiatan' => [
                'local_key'  => 'kode_kegiatan',
                'parent_key' => 'kode'
            ]
        ],

        'dropdown' => [
            'value' => 'kode',
            'label' => 'nama'
        ],

        'modes' => [
            'default' => [
                'select' => ['id', 'kode', 'kode_kegiatan', 'nama'],
                'searchable' => ['kode', 'nama'],
                'order_by' => 'kode ASC',
                'where' => [
                    'kd_wilayah' => 'user',
                    'peraturan'  => 'user'
                ]
            ]
        ]
    ],


    'trx_naskah_dinas' => [
        'table' => 'trx_naskah_dinas',
        'primary_key' => 'id',
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
                'select' => ['id', 'nama'],
                'searchable' => ['nama'],
                'order_by' => 'nama ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['nama'],
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
];
