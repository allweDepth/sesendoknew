<?php $canManage = !empty($canManage);
$regionalScope = !empty($regionalScope);
$scopeOpd = $scopeOpd ?? ''; ?>
<div class="ui container" id="strukturOrganisasi" data-manage="<?= $canManage ? '1' : '0' ?>">
  <div class="ui blue icon message"><i class="sitemap icon"></i>
    <div class="content">
      <div class="header"><?= $regionalScope ? 'Struktur Pimpinan Wilayah' : 'Struktur Jabatan Struktural OPD' ?></div>
      <p><?= $regionalScope ? 'Admin wilayah mengatur Bupati dan Sekretaris Daerah sebagai pimpinan tertinggi ASN wilayah.' : 'Susun jabatan dari Kepala OPD, Sekretaris/Kepala Bidang (Eselon III), hingga jabatan struktural terbawah.' ?> Nomor, tanggal SK, dan TMT wajib dicatat.</p>
    </div>
  </div>
  <?php if ($canManage): ?><button class="ui primary button" id="tambahJabatan"><i class="plus icon"></i>Tambah Pejabat Struktural</button><?php else: ?><div class="ui label">Mode baca — pengaturan dilakukan Admin OPD, Kepala OPD, atau PA/KPA</div><?php endif; ?>
  <div class="ui divider"></div>
  <div id="strukturList">
    <div class="ui active centered inline loader"></div>
  </div>
</div>
<div class="ui modal" id="strukturModal"><i class="close icon"></i>
  <div class="header">Atur Jabatan Struktural</div>
  <div class="content">
    <form class="ui form" id="strukturForm"><input type="hidden" name="id">
      <?php if ($regionalScope): ?><div class="field"><label>Unit Pimpinan</label><select class="ui fluid dropdown" name="target_kd_opd">
            <option value="BUPATI">Bupati Kabupaten Pasangkayu</option>
            <option value="SETDA">Sekretariat Daerah</option>
          </select></div><?php else: ?><input type="hidden" name="target_kd_opd" value="<?= htmlspecialchars($scopeOpd, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
      <div class="two fields">
        <div class="required field"><label>Nama Jabatan</label><select class="ui fluid search dropdown" name="nama_jabatan_preset">
            <option value="">Pilih jabatan struktural</option>
            <option>Kepala OPD</option>
            <option>Sekretaris</option>
            <option>Kepala Bidang</option>
            <option>Kepala Bagian</option>
            <option>Kepala UPTD</option>
            <option>Kepala Subbagian</option>
            <option>Kepala Seksi</option>
            <option value="__custom__">Jabatan lainnya...</option>
          </select><input class="ui small input" name="nama_jabatan" maxlength="255" required placeholder="Nama jabatan sesuai SK" style="margin-top:8px"></div>
        <div class="required field"><label>Pejabat ASN</label><select class="ui fluid search dropdown" name="pegawai_id" required></select></div>
      </div>
      <div class="three fields">
        <div class="field"><label>Kelompok Jabatan</label><input name="kelompok_jabatan" maxlength="150" placeholder="Pimpinan Tinggi/Administrator/Pengawas"></div>
        <div class="field"><label>Eselon</label><select class="ui dropdown" name="eselon">
            <option value="">Non-eselon</option>
            <option>II.a</option>
            <option>II.b</option>
            <option>III.a</option>
            <option>III.b</option>
            <option>IV.a</option>
            <option>IV.b</option>
          </select></div>
        <div class="field"><label>Urutan</label><input type="number" min="1" name="urutan" value="1"></div>
      </div>
      <div class="three fields">
        <div class="required field"><label>Nomor SK Pengangkatan</label><input name="nomor_sk_pengangkatan" maxlength="150" required placeholder="Contoh: SK-001/2026"></div>
        <div class="required field"><label>Tanggal SK</label><div class="ui calendar structure-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_sk_pengangkatan" required autocomplete="off"></div></div></div>
        <div class="required field"><label>TMT/Berlaku Mulai</label><div class="ui calendar structure-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tmt_jabatan" required autocomplete="off"></div></div></div>
      </div>
      <div class="field"><label>Atasan Langsung</label><input type="hidden" name="parent_kd_opd"><select class="ui fluid search dropdown" name="parent_id">
          <option value="">Tidak ada (pimpinan tertinggi)</option>
        </select></div>
      <div class="field"><label>Keterangan</label><textarea name="keterangan" rows="2" maxlength="500"></textarea></div><datalist id="jabatanAsn"></datalist>
    </form>
  </div>
  <div class="actions"><button class="ui deny button">Batal</button><button class="ui primary button" id="simpanJabatan">Simpan</button></div>
</div>
<script>
  (() => {
    const root = $('#strukturOrganisasi'),
      canManage = Number(root.data('manage')) === 1;
    let data = {
      rows: [],
      employees: []
    };
    const esc = s => $('<div>').text(s ?? '').html();
    const pad=n=>String(n).padStart(2,'0');
    $('#strukturForm .structure-calendar').calendar({type:'date',firstDayOfWeek:1,formatter:{date:d=>d?`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`:''}});

    function load() {
      Ajax.request({
        url: '/kepegawaian/struktur/data',
        method: 'GET',
        success: r => {
          if (!r?.success) return;
          data = r.data;
          render();
          fill();
        }
      })
    }

    function fill() {
      const emp = $('#strukturForm [name="pegawai_id"]'),
        par = $('#strukturForm [name="parent_id"]');
      emp.html('<option value="">Pilih ASN</option>' + data.employees.map(x => `<option value="${x.id}">${esc(x.nama)} — ${esc(x.nip||'-')} (${esc(x.jabatan||'tanpa jabatan')})</option>`).join(''));
      par.html('<option value="">Tidak ada (pimpinan tertinggi)</option>' + data.rows.map(x => `<option value="${x.kd_opd}:${x.id}">${esc(x.nama_jabatan)} — ${esc(x.nama_pegawai||'-')} [${esc(x.kd_opd)}]</option>`).join(''));
      $('#jabatanAsn').html([...new Set(data.employees.map(x => x.jabatan).filter(Boolean))].map(x => `<option value="${esc(x)}">`).join(''));
      $('.ui.dropdown').dropdown('refresh');
    }

    function render() {
      if (!data.rows.length) {
        $('#strukturList').html('<div class="ui placeholder segment"><div class="ui icon header"><i class="sitemap icon"></i>Belum ada struktur jabatan OPD</div></div>');
        return;
      }
      const byParent = {},
        keys = new Set(data.rows.map(x => x.struktur_key));
      data.rows.forEach(x => (byParent[x.parent_key] ??= []).push(x));
      const branch = (parentKey, depth = 0) => (byParent[parentKey] || []).map(x => `<div class="ui segment" style="margin-left:${Math.min(depth,5)*28}px;border-left:4px solid ${depth?'#21ba45':'#2185d0'}"><div class="ui right floated buttons">${canManage?`<button class="ui mini basic blue icon button edit-struktur" data-id="${x.id}" data-kd-opd="${esc(x.kd_opd)}"><i class="edit icon"></i></button><button class="ui mini basic red icon button hapus-struktur" title="Akhiri masa berlaku" data-id="${x.id}" data-kd-opd="${esc(x.kd_opd)}"><i class="calendar times icon"></i></button>`:''}</div><div class="ui ${depth?'teal':'blue'} label">${esc(x.eselon||'Non-eselon')}</div> <b>${esc(x.nama_jabatan)}</b><div class="ui small header" style="margin:8px 0 2px">${esc(x.nama_pegawai||'Belum ditetapkan')}</div><div class="meta">NIP ${esc(x.nip||'-')} · ${esc(x.nomor_sk_pengangkatan||'SK belum diisi')} · SK ${esc(x.tanggal_sk_pengangkatan||'-')} · Berlaku ${esc(x.berlaku_mulai||x.tmt_jabatan||'-')} s.d. ${esc(x.berlaku_sampai||'sekarang')}</div></div>${branch(x.struktur_key,depth+1)}`).join('');
      const roots = [...new Set(data.rows.map(x => x.parent_key).filter(key => !keys.has(key)))];
      $('#strukturList').html(roots.map(key => branch(key)).join('') || '<div class="ui warning message">Belum ada struktur jabatan pada scope ini.</div>');
    }
    $(document).off('.struktur').on('change.struktur', '#strukturForm [name="parent_id"]', function() {
      const value = String($(this).val() || '').split(':');
      $('#strukturForm [name="parent_kd_opd"]').val(value.length === 2 ? value[0] : '');
    }).on('change.struktur', '#strukturForm [name="nama_jabatan_preset"]', function() {
      const value = $(this).val();
      if (value && value !== '__custom__') $('#strukturForm [name="nama_jabatan"]').val(value);
      if (value === '__custom__') $('#strukturForm [name="nama_jabatan"]').val('').trigger('focus');
    }).on('click.struktur', '#tambahJabatan', () => {
      $('#strukturForm')[0].reset();
      $('#strukturForm [name=id]').val('');
      fill();
      $('#strukturModal').modal('show')
    }).on('click.struktur', '.edit-struktur', function() {
      const x = data.rows.find(r => Number(r.id) === Number($(this).data('id')));
      if (!x) return;
      $('#strukturForm [name="target_kd_opd"]').val(x.kd_opd);
      Object.entries(x).forEach(([k, v]) => $('#strukturForm [name="' + k + '"]').val(v ?? ''));
      $('#strukturForm [name="parent_id"]').val(x.parent_id ? `${x.parent_kd_opd || x.kd_opd}:${x.parent_id}` : '').trigger('change');
      $('#strukturForm [name="nama_jabatan_preset"]').val('').trigger('change');
      fill();
      Object.entries(x).forEach(([k, v]) => $('#strukturForm [name="' + k + '"]').val(v ?? '').trigger('change'));
      $('#strukturModal').modal('show')
    }).on('click.struktur', '#simpanJabatan', () => Ajax.request({
      url: '/kepegawaian/struktur/save',
      method: 'POST',
      data: $('#strukturForm').serialize(),
      success: r => {
        if (r?.success) {
          $('#strukturModal').modal('hide');
          load();
        }
      }
    })).on('click.struktur', '.hapus-struktur', function() {
      if (!confirm('Akhiri masa berlaku jabatan ini hari ini? Riwayat SK tetap disimpan.')) return;
      Ajax.request({
        url: '/kepegawaian/struktur/delete',
        method: 'POST',
        data: {
          id: $(this).data('id'),
          target_kd_opd: $(this).data('kd-opd')
        },
        success: r => {
          if (r?.success) load();
        }
      })
    });
    load();
  })();
</script>
