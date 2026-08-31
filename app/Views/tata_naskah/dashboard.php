<?php $s=$summary??[]; ?>
<div class="ui container" style="margin-top:20px">
  <h2 class="ui header"><i class="file alternate outline icon"></i><div class="content">Tata Naskah Dinas<div class="sub header">Pembuatan, pengamanan, penandatanganan, dan pengendalian naskah dinas</div></div></h2>
  <div class="ui four stackable statistics segment">
    <div class="statistic"><div class="value"><?= (int)($s['total']??0) ?></div><div class="label">Total Naskah</div></div>
    <div class="orange statistic"><div class="value"><?= (int)($s['draft']??0) ?></div><div class="label">Draft</div></div>
    <div class="blue statistic"><div class="value"><?= (int)($s['verifikasi']??0) ?></div><div class="label">Verifikasi</div></div>
    <div class="green statistic"><div class="value"><?= (int)($s['selesai']??0) ?></div><div class="label">Ditandatangani/Final</div></div>
  </div>
  <div class="ui three stackable cards">
    <a class="ui raised card" href="/tata_naskah/buat" data-spa="server"><div class="content"><i class="right floated large edit blue icon"></i><div class="header">Buat Naskah</div><div class="description">Pilih kelompok dan jenis naskah, isi struktur, lalu simpan.</div></div></a>
    <a class="ui raised card" href="/tata_naskah/daftar" data-spa="client"><div class="content"><i class="right floated large list teal icon"></i><div class="header">Daftar Naskah</div><div class="description">Cari, periksa status, preview, dan cetak PDF.</div></div></a>
    <div class="ui raised card"><div class="content"><i class="right floated large shield alternate violet icon"></i><div class="header">Standar ANRI 2025</div><div class="description">Kepala, batang tubuh, kaki, klasifikasi keamanan, nomor, lampiran, dan tembusan.</div></div></div>
  </div>
</div>
