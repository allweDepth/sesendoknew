<div class="ui container" id="anggaranDocument" data-table="<?= htmlspecialchars($table) ?>">
  <?php if(in_array($table,['rkpd','rkpd_p'],true)): ?><div class="ui four ordered fluid steps"><div class="completed step"><div class="content"><div class="title">Renja OPD</div><div class="description"><?= $table==='rkpd_p'?'Rancangan Perubahan Renja':'Rancangan Awal Renja' ?></div></div></div><div class="active step"><div class="content"><div class="title"><?= $table==='rkpd_p'?'Rancangan P-RKPD':'Rancangan RKPD' ?></div><div class="description">Sub-kegiatan dan pembagian pagu OPD</div></div></div><div class="step"><div class="content"><div class="title">Validasi TAPD</div><div class="description">Pagu total tidak boleh terlampaui</div></div></div><div class="step"><div class="content"><div class="title">Penetapan Perkada</div></div></div></div><?php endif; ?>
  <div class="ui info message">
    <div class="header"><?= htmlspecialchars($title) ?></div>
    Data mengikuti wilayah, OPD, dan tahun pengguna aktif. Dokumen tahap berikutnya hanya dibentuk dari data yang telah disetujui.
  </div>
  <div id="crud-table-container"></div>
</div>
