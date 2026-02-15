<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIPD</title>

    <link rel="stylesheet" href="/assets/css/fomantic.min.css">
    <style>
        #mainContext {
            min-height: calc(100vh - 40px);
        }

        .pusher .ui .container {
            padding: 20px !important;
        }

        html, body {
    height: 100%;
    overflow: hidden;
}

/* FIX SEGMENT PUSHABLE */
#mainContext.ui.segment {
    height: calc(100vh - 40px);
    padding: 0 !important;
    overflow: hidden;   /* PENTING */
}

/* PUSHER */
#mainContext .pusher {
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* STICKY HEADER */
#mainContext .pusher > .ui.sticky {
    flex: 0 0 auto;
}

/* SCROLL AREA */
#mainContext .content-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}
    </style>
</head>

<body>

    <!-- NAVBAR (DI LUAR PUSHABLE) -->
    <div class="ui top attached menu">
        <div class="item" id="toggleSidebar">
            <a>
                <i class="bars icon"></i>
            </a>
        </div>
        <div class="right menu">
            <div class="ui inline item dropdown" id="countRow"><span><i class="list icon"></i></span><input type="hidden" name="countRow" value="5">
                <div class="text">5</div>
                <div class="menu">
                    <div class="item" data-value="all">All</div>
                    <div class="item selected" data-value="5">5</div>
                    <div class="item" data-value="10">10</div>
                    <div class="item" data-value="15">15</div>
                    <div class="item" data-value="20">20</div>
                    <div class="item" data-value="30">30</div>
                    <div class="item" data-value="40">40</div>
                    <div class="item" data-value="50">50</div>
                    <div class="item" data-value="100">100</div>
                </div>
            </div>
            <div class="item">
                <div class="ui cari_data transparent icon input">
                    <input type="text" placeholder="Search..." name="cari_data" id="cari_data">
                    <i class="search link icon"></i>
                </div>
            </div>
            <div class="right menu">
                <div class="ui dropdown item"><span><i class="user icon"></i></span><i class="dropdown icon"></i>
                    <div class="menu"><a class="item" data-tab="wallchat"><i class="circular comments outline icon"></i>Pesan</a><a class="item" name="change_themes"><i class="circular moon icon"></i>Change Themes</a><a class="item" data-tab="profil"><i class="circular qrcode icon"></i>Pengaturan</a><a class="item" href="/logout"><i class="circular sign out alternate icon"></i>Log
                            Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- SIDEBAR WRAPPER -->
    <div class="ui bottom attached segment pushable" id="mainContext">
        <!-- SIDEBAR -->
        <div class="ui inverted vertical sidebar menu left">
            <!-- HEADER -->
            <div class="item">
                <h2 class="ui inverted center aligned icon header dash_header">
                    <i class="circular blue building icon"></i>
                    <div class="content">
                        seSendok
                        <div class="sub header">Pemerintahan</div>
                        <div class="ui blue basic inverted label">
                            <?= $_SESSION["user"]["tahun"] ?? date('Y'); ?>
                        </div>
                    </div>
                </h2>
            </div>
            <!-- SEARCH -->
            <div class="item">
                <div class="ui inverted transparent icon input">
                    <input type="text" placeholder="Menu...">
                    <i class="search icon"></i>
                </div>
            </div>
            <!-- HOME -->
            <a class="item" href="/dashboard">
                <i class="home icon"></i> Dashboard
            </a>
            <!-- ANGGARAN -->
            <div class="ui accordion inverted item">
                <div class="title item">
                    <i class="dropdown icon"></i><span></span>Anggaran
                </div>
                <div class="content">
                    <a class="item" href="/renstra"><i class="purple sitemap icon"></i> RENSTRA</a>
                    <a class="item" href="/renja"><i class="violet tag icon"></i> RENJA</a>
                    <a class="item" href="/dpa"><i class="yellow tags icon"></i> DPA</a>
                    <a class="item" href="/renja_perubahan"><i class="orange edit icon"></i> RENJA Perubahan</a>
                    <a class="item" href="/dppa"><i class="brown folder open icon"></i> DPPA</a>
                </div>
            </div>

            <!-- KONTRAK -->
            <a class="item" href="/kontrak">
                <i class="file contract icon"></i> Kontrak
            </a>

            <!-- REALISASI -->
            <div class="ui accordion inverted item">
                <div class="title item">
                    <i class="dropdown icon"></i> Realisasi
                </div>
                <div class="content">
                    <a class="item" href="/input_realisasi"><i class="purple chart pie icon"></i> Input Realisasi</a>
                    <a class="item" href="/spj"><i class="violet chart line icon"></i> SPJ</a>
                    <a class="item" href="/laporan"><i class="yellow chart bar icon"></i> Laporan</a>
                </div>
            </div>

            <!-- REFERENSI -->
            <div class="ui accordion inverted item">
                <div class="title item">
                    <i class="dropdown icon"></i> Referensi
                </div>
                <div class="content">
                    <a class="item" href="/referensi?tbl=bidang_urusan"><i class="user plus icon"></i> Bidang Urusan</a>
                    <a class="item" href="/referensi?tbl=program"><i class="users icon"></i> Program</a>
                    <a class="item" href="/referensi?tbl=kegiatan"><i class="outdent icon"></i> Kegiatan</a>
                    <a class="item" href="/referensi?tbl=sub_kegiatan"><i class="layer group icon"></i> Sub Kegiatan</a>
                    <a class="item" href="/referensi?tbl=rekanan"><i class="book reader icon"></i> Rekanan</a>
                    <a class="item" href="/referensi?tbl=satuan"><i class="calculator icon"></i> Satuan</a>
                    <a class="item" href="/referensi?tbl=mapping"><i class="stream icon"></i> Mapping</a>
                    <a class="item" href="/referensi?tbl=aset"><i class="calendar alternate icon"></i> Neraca</a>
                    <a class="item" href="/referensi?tbl=akun"><i class="calendar alternate outline icon"></i> Akun</a>
                    <a class="item" href="/referensi?tbl=sumber_dana"><i class="money check alternate icon"></i> Sumber Dana</a>
                    <a class="item" href="/referensi?tbl=organisasi"><i class="id card icon"></i> Organisasi</a>
                    <a class="item" href="/referensi?tbl=peraturan"><i class="balance scale icon"></i> Peraturan</a>
                    <a class="item" href="/referensi?tbl=wilayah"><i class="globe icon"></i> Wilayah</a>
                </div>
            </div>

            <!-- STANDAR HARGA -->
            <div class="ui accordion inverted item">
                <div class="title item">
                    <i class="dropdown icon"></i> Standar Harga Satuan
                </div>
                <div class="content">
                    <a class="item" href="/ssh"><i class="file icon"></i> SSH</a>
                    <a class="item" href="/hspk"><i class="file alternate icon"></i> HSPK</a>
                    <a class="item" href="/asb"><i class="file alternate outline icon"></i> ASB</a>
                    <a class="item" href="/sbu"><i class="file outline icon"></i> SBU</a>
                </div>
            </div>

            <!-- KEPEGAWAIAN -->
            <div class="ui accordion inverted item">
                <div class="title item">
                    <i class="dropdown icon"></i> Kepegawaian
                </div>
                <div class="content">
                    <a class="item" href="/kepegawaian?tbl=asn"><i class="users icon"></i> ASN</a>
                    <a class="item" href="/kepegawaian?tbl=sk_asn"><i class="file signature icon"></i> Surat Keputusan (SK)</a>
                    <a class="item" href="/kepegawaian?tbl=register_surat"><i class="clipboard list icon"></i> Register Surat</a>
                    <a class="item" href="/kepegawaian?tbl=tata_naskah"><i class="edit icon"></i> Tata Naskah</a>
                </div>
            </div>

            <!-- ADMIN MENU -->
            <?php if (($_SESSION['user']['type_user'] ?? '') === 'admin'): ?>

                <a class="item" href="/halaman_berita">
                    <i class="newspaper icon"></i> Halaman Berita
                </a>
                <a class="item" href="/reset_tabel">
                    <i class="erase icon"></i> Reset Tabel
                </a>
                <a class="item" href="/pengaturan">
                    <i class="toolbox icon"></i> Pengaturan
                </a>
            <?php endif; ?>

            <a class="item" href="/wallchat">
                <i class="comments outline icon"></i> Pesan
            </a>

            <a class="item" href="/profil">
                <i class="user icon"></i> Profil
            </a>
        </div>
        <!-- FLYOUT -->
        <?php require __DIR__ . '/../partials/flyout.php'; ?>
        <!-- PUSHER -->
        <div class="pusher">
            <!-- sticky-->
            <div class="ui sticky">
                <div class="ui icon message dashboard"><i class="home icon"></i>
                    <div class="content">
                        <div class="header">DASHBOARD</div>
                        <div class="pDashboard">seSendok</div>
                    </div>
                </div>
            </div>
            <div class="content-scroll">
                <?= $content ?>
            </div>

        </div>

    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
    <script src="/assets/js/app.js"></script>

</body>

</html>