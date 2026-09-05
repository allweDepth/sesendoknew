<?php
$user = $_SESSION['user'] ?? [];
$type = strtolower(str_replace(' ', '_', $user['type_user'] ?? ''));
$canEdit = in_array($type, ['super_admin', 'admin_wilayah']);
$disabled = $canEdit ? '' : 'disabled';
$tahunLabel = $user['tahun'] ?? '-';
?>

<div class="ui container form-wrapper">

    <!-- HEADER -->
    <div class="ui top attached segment page-header">
        <div class="ui grid">
            <div class="twelve wide column">
                <h2 class="ui header">
                    <i class="settings icon"></i>
                    <div class="content">
                        Pengaturan Sistem
                        <div class="sub header">
                            Konfigurasi wilayah, periode & kontrol sistem
                        </div>
                    </div>
                </h2>
            </div>
            <div class="four wide right aligned column">
                <div class="ui tiny grey label">
                    Tahun Aktif: <?= $tahunLabel ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB MENU -->
    <div class="ui top attached tabular menu" id="pengaturan-tabs">
        <a class="active item" data-tab="pengaturan">Pengaturan Wilayah</a>
        <a class="item" data-tab="periode">Periode RPJMD</a>
        <a class="item" data-tab="page-setup">Page Setup PDF</a>
    </div>

    <!-- TAB 1 -->
    <div class="ui bottom attached tab segment active" data-tab="pengaturan">

        <form class="ui form" id="form-pengaturan">

            <!-- ================= IDENTITAS ================= -->
            <h4 class="ui dividing header">Identitas Wilayah</h4>

            <div class="two fields">
                <div class="field">
                    <label>Tahun Anggaran</label>
                    <input type="number" name="tahun" <?= $disabled ?>>
                </div>
                <div class="field">
                    <label>Tahun Renstra</label>
                    <input type="number" name="tahun_renstra" <?= $disabled ?>>
                </div>
            </div>

            <!-- ================= ATURAN REFERENSI ================= -->
            <h4 class="ui dividing header">Aturan Referensi</h4>

            <div class="ui stackable two column grid">

                <?php
                $dropdowns = [
                    "aturan_anggaran" => "Anggaran",
                    "aturan_organisasi" => "Organisasi",
                    "aturan_pengadaan" => "Pengadaan",
                    "aturan_akun" => "Akun",
                    "aturan_asb" => "ASB",
                    "aturan_sbu" => "SBU",
                    "aturan_ssh" => "SSH",
                    "aturan_hspk" => "HSPK",
                    "aturan_sumber_dana" => "Sumber Dana",
                    "aturan_sub_kegiatan" => "Sub Kegiatan"
                ];

                foreach ($dropdowns as $name => $label): ?>
                    <div class="eight wide column">
                        <div class="field">
                            <label><?= $label ?></label>
                            <div class="ui fluid search selection dropdown peraturan-dropdown <?= $disabled ?>" data-name="<?= $name ?>">
                                <input type="hidden" name="<?= $name ?>">
                                <i class="dropdown icon"></i>
                                <div class="default text">Pilih <?= $label ?></div>
                                <div class="menu"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- ================= PERIODE DOKUMEN ================= -->
            <h4 class="ui dividing header">Periode Dokumen</h4>
            <div class="ui blue icon message">
                <i class="calendar check outline icon"></i>
                <div class="content"><div class="header">Jadwal ini mengendalikan CRUD OPD secara otomatis</div>
                <p>Di luar rentang waktu atau saat modul dikunci, pengguna OPD tidak dapat menambah, mengubah, maupun menghapus data. Pesan penolakan akan menyebutkan jadwal yang benar.</p></div>
            </div>
            <div id="schedule-status-cards" class="ui four stackable cards"></div>

            <div class="table-wrapper">
            <table class="ui compact celled striped table" id="document-period-table">
                <thead><tr><th>Dokumen</th><th>Mulai</th><th>Selesai</th><th class="collapsing">Aksi</th></tr></thead>
                <tbody>

                <?php
                $ranges = [
                    "renja" => "Renja",
                    "dpa" => "DPA",
                    "rkpd" => "RKPD",
                    "rka" => "RKA",
                    "renja_p" => "Renja Perubahan",
                    "dppa" => "DPPA",
                    "kua_ppas" => "KUA PPAS",
                    "rapbd" => "RAPBD",
                    "rak" => "RAK",
                    "rkpd_perubahan" => "RKPD Perubahan",
                    "kua_ppas_perubahan" => "KUA PPAS Perubahan",
                    "apbd_perubahan" => "APBD Perubahan",
                    "renstra" => "Renstra"
                ];

                foreach ($ranges as $name => $label): ?>
                    <tr data-period-row="<?= $name ?>">
                        <td><i class="clock outline icon"></i><strong><?= $label ?></strong></td>
                        <td><div class="ui calendar start_<?= $name ?>"><div class="ui fluid input left icon"><i class="calendar icon"></i><input type="text" name="awal_<?= $name ?>" readonly <?= $disabled ?>></div></div></td>
                        <td><div class="ui calendar end_<?= $name ?>"><div class="ui fluid input left icon"><i class="calendar icon"></i><input type="text" name="akhir_<?= $name ?>" readonly <?= $disabled ?>></div></div></td>
                        <td><button type="button" class="ui mini blue icon button edit-document-period <?= $disabled ?>" data-period="<?= $name ?>" title="Edit periode <?= $label ?>"><i class="edit icon"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <!-- ================= KONTROL SISTEM ================= -->
            <h4 class="ui dividing header">Kontrol Sistem</h4>

            <div class="ui four stackable cards">

                <?php
                $controls = [
                    "Global" => ["kunci", "setujui"],
                    "Renstra" => ["kunci_renstra", "setujui_renstra"],
                    "Renja" => ["kunci_renja", "setujui_renja"],
                    "DPA" => ["kunci_dpa", "setujui_dpa"],
                    "Renja P" => ["kunci_renja_p", "setujui_renja_p"],
                    "DPPA" => ["kunci_dppa", "setujui_dppa"],
                    "Paket" => ["kunci_paket", "setujui_paket"],
                    "Realisasi" => ["kunci_realisasi", "setujui_realisasi"],
                ];

                foreach ($controls as $title => $fields): ?>
                    <div class="card">
                        <div class="content">
                            <div class="header"><?= $title ?></div>
                        </div>
                        <div class="content">
                            <div class="ui toggle checkbox">
                                <input type="checkbox" name="<?= $fields[0] ?>" <?= $disabled ?>>
                                <label>Kunci</label>
                            </div><br>
                            <div class="ui toggle checkbox">
                                <input type="checkbox" name="<?= $fields[1] ?>" <?= $disabled ?>>
                                <label>Setujui</label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- ================= KETERANGAN ================= -->
            <h4 class="ui dividing header">Keterangan</h4>

            <div class="field">
                <textarea name="keterangan" rows="3" <?= $disabled ?>></textarea>
            </div>

            <div class="ui divider"></div>

            <?php if ($canEdit): ?>
                <div class="right aligned field">
                    <button class="ui primary button">
                        <i class="save icon"></i> Simpan Perubahan
                    </button>
                </div>
            <?php else: ?>
                <div class="ui grey disabled button">Read Only</div>
            <?php endif; ?>

        </form>

    </div>

    <!-- TAB 2 -->
    <div class="ui bottom attached tab segment" data-tab="periode">
        <div id="periode-rpjmd-container"></div>
    </div>

    <div class="ui bottom attached tab segment" data-tab="page-setup">
        <form class="ui form" id="form-page-setup">
            <div class="ui info message"><i class="file pdf outline icon"></i>Berlaku global hanya untuk seluruh ekspor PDF pada wilayah dan tahun aktif.</div>
            <div class="four fields">
                <div class="field"><label>Ukuran Kertas</label><select class="ui search dropdown" name="ukuran_kertas">
                    <optgroup label="ISO A"><option value="A0">A0 - 841 x 1189 mm</option><option value="A1">A1 - 594 x 841 mm</option><option value="A2">A2 - 420 x 594 mm</option><option value="A3">A3 - 297 x 420 mm</option><option value="A3PLUS">A3+ - 329 x 483 mm</option><option value="A4">A4 - 210 x 297 mm</option><option value="A5">A5 - 148 x 210 mm</option><option value="A6">A6 - 105 x 148 mm</option></optgroup>
                    <optgroup label="Foto dan presentasi"><option value="PHOTO_89_127">9 x 13 cm (89 x 127 mm)</option><option value="PHOTO_102_152">10 x 15 cm (102 x 152 mm)</option><option value="PHOTO_127_178">13 x 18 cm (127 x 178 mm)</option><option value="PHOTO_127_203">13 x 20 cm (127 x 203 mm)</option><option value="WIDE_102_181">16:9 wide (102 x 181 mm)</option><option value="PHOTO_203_254">20 x 25 cm (203 x 254 mm)</option><option value="POSTCARD_100_148">100 x 148 mm</option></optgroup>
                    <optgroup label="Legal, Letter, dan Folio"><option value="F4">F4/Folio - 216 x 330 mm</option><option value="FOLIO_216_330">8.5 x 13 in - 216 x 330 mm</option><option value="LEGAL">US Legal - 216 x 356 mm</option><option value="LETTER">US Letter - 216 x 279 mm</option><option value="INDIAN_LEGAL">Indian Legal - 215 x 345 mm</option><option value="JIS_B4">JIS B4 - 257 x 364 mm</option><option value="JIS_B5">JIS B5 - 182 x 257 mm</option></optgroup>
                    <optgroup label="Amplop"><option value="ENVELOPE_10">Envelope #10 - 105 x 241 mm</option><option value="C4">Envelope C4 - 229 x 324 mm</option><option value="C6">Envelope C6 - 114 x 162 mm</option><option value="DL">Envelope DL - 110 x 220 mm</option></optgroup>
                    <option value="CUSTOM">Ukuran Custom...</option>
                </select></div>
                <div class="field"><label>Orientasi</label><select class="ui dropdown" name="orientasi_kertas"><option value="AUTO">Otomatis per dokumen</option><option value="P">Portrait</option><option value="L">Landscape</option></select></div>
                <div class="field"><label>Font PDF</label><select class="ui dropdown" name="font_pdf"><option value="helvetica">Helvetica</option><option value="times">Times</option><option value="courier">Courier</option></select></div>
                <div class="field"><label>Ukuran Font PDF</label><div class="ui right labeled input"><input type="number" min="6" max="18" step="0.5" name="ukuran_font_pdf"><div class="ui label">pt</div></div></div>
            </div>
            <div class="ui segment" id="custom-paper-fields"><h4 class="ui dividing header">Ukuran Custom</h4><div class="two fields"><div class="field"><label>Lebar</label><div class="ui right labeled input"><input type="number" min="50" max="2000" step="0.1" name="lebar_kertas_mm"><div class="ui label">mm</div></div></div><div class="field"><label>Tinggi</label><div class="ui right labeled input"><input type="number" min="50" max="2000" step="0.1" name="tinggi_kertas_mm"><div class="ui label">mm</div></div></div></div></div>
            <h4 class="ui dividing header">Margin Dokumen</h4>
            <div class="six fields"><div class="field"><label>Atas</label><input type="number" min="0" step="0.5" name="margin_atas_mm"></div><div class="field"><label>Kanan</label><input type="number" min="0" step="0.5" name="margin_kanan_mm"></div><div class="field"><label>Bawah</label><input type="number" min="0" step="0.5" name="margin_bawah_mm"></div><div class="field"><label>Kiri</label><input type="number" min="0" step="0.5" name="margin_kiri_mm"></div><div class="field"><label>Header</label><input type="number" min="0" step="0.5" name="margin_header_mm"></div><div class="field"><label>Footer</label><input type="number" min="0" step="0.5" name="margin_footer_mm"></div></div>
            <button class="ui primary button"><i class="save icon"></i>Simpan Page Setup PDF</button>
        </form>
    </div>

</div>
