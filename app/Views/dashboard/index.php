<style>
.dashboard-wrapper{
    min-height:100%;
    display:flex;
    flex-direction:column;
}

.dashboard-content{
    flex:1;
}
</style>

<div class="dashboard-wrapper">

    <div class="dashboard-content">

        <div class="main ui intro container">

            <h2 class="ui dividing header">Pengantar untuk user </h2>

            <div class="ui large info message">
                <h2 class="ui header dash_header">
                    <i class="settings icon"></i>
                    <div class="content">
                        seSendok
                        <div class="sub header">
                            seSendok – Sistem Elektronik Sinkronisasi Dokumen Perencanaan,
                            Penganggaran, dan Realisasi Kinerja merupakan aplikasi
                            perencanaan, anggaran dan realisasi berbasis web
                        </div>
                    </div>
                </h2>
            </div>

            <div class="ui info message">
                <h3 class="ui header dash_header">
                    <i class="upload icon"></i>
                    <div class="content">
                        menginpor file pada aplikasi <?php echo Auth::tahun(); ?>?
                        <div class="sub header">
                            <div class="ui divided selection list">
                                <div class="item">
                                    file harus extension
                                    <a class="ui green label">
                                        <i class="file excel icon"></i>xlsx
                                    </a>
                                </div>
                                <div class="item">
                                    Format angka Indonesia:
                                    <a class="ui blue label">1.200.000,50</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </h3>
            </div>

            <h2 class="ui dividing header">Cara menggunakan</h2>

            <div class="ui relaxed divided list">
                <div class="item">
                    <i class="large list ol middle aligned icon"></i>
                    <div class="content">
                        <div class="header">Referensi >> Wilayah</div>
                        <div class="description">Input kode wilayah (admin)</div>
                    </div>
                </div>

                <div class="item">
                    <i class="large list ol middle aligned icon"></i>
                    <div class="content">
                        <div class="header">Referensi >> Peraturan</div>
                        <div class="description">Input peraturan (admin)</div>
                    </div>
                </div>

                <div class="item">
                    <i class="large list ol middle aligned icon"></i>
                    <div class="content">
                        <div class="header">Pengaturan >> Tahun Anggaran</div>
                        <div class="description">Tentukan Tahun Anggaran</div>
                    </div>
                </div>
            </div>

            <p>
                Tutorial dapat di download
                <a href="template/tutorial_user.pdf" target="_blank">disini</a>
            </p>

        </div>

    </div>

    <!-- FOOTER -->
    <div class="ui vertical footer segment">
        <div class="three column divided stackable center aligned ui grid">

            <div class="column">
                <div class="ui icon header">
                    <i class="teal rocket circular icon"></i>
                    AHSP : <a href="javascript:void(0)">efisiensi dan efektif</a>
                </div>
            </div>

            <div class="column">
                <div class="ui icon header">
                    <i class="teal theme circular icon"></i>
                    transparansi, <a href="javascript:void(0)">akuntabilitas</a>
                </div>
            </div>

            <div class="column">
                <div class="ui icon header">
                    <i class="teal food circular icon"></i>
                    serta <a href="javascript:void(0)">partisipatif</a>
                </div>
            </div>

        </div>
    </div>

</div>