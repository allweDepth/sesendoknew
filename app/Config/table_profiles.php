<?php

return [

    'akun' => [
        'table' => 'akun_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kode','uraian'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'asb' => [
        'table' => 'asb_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kd_aset','uraian_barang'],
                'order_by' => 'uraian_barang ASC'
            ]
        ]
    ],

    'aset' => [
        'table' => 'aset_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kode','uraian'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'organisasi' => [
        'table' => 'organisasi_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['id','kode','uraian','singkatan'],
                'searchable' => ['kode','uraian','singkatan'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'satuan' => [
        'table' => 'satuan_neo',
        'primary_key' => 'id',
        'modes' => [
            'dropdown' => [
                'select' => ['id','value','item'],
                'searchable' => ['item','value'],
                'order_by' => 'item ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['item'],
                'order_by' => 'item ASC'
            ]
        ]
    ],

    'sub_kegiatan' => [
        'table' => 'sub_kegiatan_neo',
        'primary_key' => 'id',
        'modes' => [
            'dropdown' => [
                'select' => ['id','kode','nomenklatur_urusan'],
                'searchable' => ['kode','nomenklatur_urusan'],
                'order_by' => 'kode ASC'
            ],
            'default' => [
                'select' => ['*'],
                'searchable' => ['kode','nomenklatur_urusan'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'sumber_dana' => [
        'table' => 'sumber_dana_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['id','kode','uraian'],
                'searchable' => ['kode','uraian'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'wilayah' => [
        'table' => 'wilayah_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['id','kode','uraian','status'],
                'searchable' => ['kode','uraian'],
                'order_by' => 'kode ASC'
            ]
        ]
    ],

    'peraturan' => [
        'table' => 'peraturan_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['id','kode','judul','nomor'],
                'searchable' => ['kode','judul','nomor'],
                'order_by' => 'tgl_penetapan DESC'
            ]
        ]
    ],

    'rekanan' => [
        'table' => 'rekanan_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['id','nama_perusahaan','npwp'],
                'searchable' => ['nama_perusahaan','npwp'],
                'order_by' => 'nama_perusahaan ASC'
            ]
        ]
    ],

    'renja' => [
        'table' => 'renja_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kd_sub_keg','uraian'],
                'order_by' => 'tahun DESC'
            ]
        ]
    ],

    'renja_p' => [
        'table' => 'renja_p_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['kd_sub_keg','uraian'],
                'order_by' => 'tahun DESC'
            ]
        ]
    ],

    'renstra_skpd' => [
        'table' => 'renstra_skpd_neo',
        'primary_key' => 'id',
        'modes' => [
            'default' => [
                'select' => ['*'],
                'searchable' => ['uraian_prog_keg','indikator'],
                'order_by' => 'tahun DESC'
            ]
        ]
    ],

    'tujuan_sasaran_renstra' => [
        'table' => 'tujuan_sasaran_renstra_neo',
        'primary_key' => 'id',
        'modes' => [

            'tujuan' => [
                'select' => ['id','text'],
                'searchable' => ['text'],
                'where' => "kelompok = 'tujuan'",
                'order_by' => 'text ASC'
            ],

            'sasaran' => [
                'select' => ['id','text','indikator'],
                'searchable' => ['text','indikator'],
                'where' => "kelompok = 'sasaran'",
                'order_by' => 'text ASC'
            ],

            'default' => [
                'select' => ['*'],
                'searchable' => ['text','indikator'],
                'order_by' => 'tahun DESC'
            ]
        ]
    ]

];
