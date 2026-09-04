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
        <a class="item" data-tab="page-setup">Page Setup PDF/Excel</a>
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
            <div class="ui info message">Berlaku global untuk ekspor PDF dan pengaturan cetak Excel pada seluruh menu di wilayah dan tahun aktif.</div>
            <div class="four fields">
                <div class="field"><label>Ukuran Kertas</label><select name="ukuran_kertas" <?= $disabled ?>><option value="A4">A4 (210 × 297 mm)</option><option value="F4">F4/Folio (210 × 330 mm)</option><option value="LEGAL">Legal (216 × 356 mm)</option></select></div>
                <div class="field"><label>Orientasi</label><select name="orientasi_kertas" <?= $disabled ?>><option value="AUTO">Otomatis per dokumen</option><option value="P">Portrait</option><option value="L">Landscape</option></select></div>
                <div class="field"><label>Font PDF</label><select name="font_pdf" <?= $disabled ?>><option value="helvetica">Helvetica</option><option value="times">Times</option><option value="courier">Courier</option></select></div>
                <div class="field"><label>Ukuran Font PDF</label><input type="number" min="6" max="18" step="0.5" name="ukuran_font_pdf" <?= $disabled ?>></div>
            </div>
            <?php if ($canEdit): ?><button class="ui primary button"><i class="save icon"></i>Simpan Page Setup</button><?php else: ?><div class="ui grey disabled button">Read Only</div><?php endif; ?>
        </form>
    </div>

</div>
