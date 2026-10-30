<?php $canManage = !empty($canManage);
$regionalScope = !empty($regionalScope);
$scopeOpd = $scopeOpd ?? ''; ?>
<div class="ui container" id="strukturOrganisasi" data-manage="<?= $canManage ? '1' : '0' ?>">
  <div class="ui blue icon message"><i class="sitemap icon"></i>
    <div class="content">
      <div class="header"><?= $regionalScope ? 'Struktur Pimpinan Wilayah' : 'Struktur Jabatan Struktural OPD' ?></div>
      <p><?= $regionalScope ? 'Role daerah mengisi kepala daerah, wakil kepala daerah, dan Sekretaris Daerah. Data tampil di seluruh OPD wilayah ini.' : 'Kepala daerah, wakil, dan Sekda ditampilkan sesuai wilayah dan dikelola role daerah. Susun jabatan OPD di bawahnya.' ?> Nomor, tanggal SK, dan TMT wajib dicatat.</p>
    </div>
  </div>
  <?php if ($canManage && !$regionalScope): ?><button class="ui teal button" id="susunOtomatis"><i class="magic icon"></i>Susun Otomatis</button><button class="ui primary button" id="tambahJabatan"><i class="plus icon"></i>Tambah Pejabat Struktural</button><?php elseif (!$canManage): ?><div class="ui label">Mode baca — pengaturan dilakukan Admin OPD, Kepala OPD, atau PA/KPA</div><?php endif; ?>
  <div class="ui divider"></div>
  <div id="strukturList">
    <div class="ui active centered inline loader"></div>
  </div>
</div>
<div class="ui modal" id="strukturAutoModal"><i class="close icon"></i><div class="header"><i class="sitemap icon"></i>Susun Otomatis Struktur OPD</div><div class="scrolling content"><div class="ui info message">Berdasarkan jabatan ASN dan riwayat yang sudah berlaku. Struktur yang telah diisi tetap dipertahankan. Nomor SK, tanggal SK, dan eselon yang belum tersedia perlu dilengkapi melalui tombol edit.</div><div id="strukturAutoPreview"></div></div><div class="actions"><button class="ui cancel button">Tutup</button><button class="ui teal button" id="terapkanStruktur"><i class="check icon"></i>Terapkan Susunan</button></div></div>
<div class="ui modal" id="strukturModal"><i class="close icon"></i>
  <div class="header">Atur Jabatan Struktural</div>
  <div class="content">
    <form class="ui form" id="strukturForm"><input type="hidden" name="id">
      <input type="hidden" name="target_kd_opd" value="<?= htmlspecialchars($scopeOpd, ENT_QUOTES, 'UTF-8') ?>">
      <div class="two fields">
        <div class="required field"><label>Nama Jabatan</label><select class="ui fluid search dropdown" name="nama_jabatan_preset">
            <option value="">Pilih jabatan struktural</option>
            <option value="__custom__">Jabatan lainnya...</option>
          </select><input class="ui small input" name="nama_jabatan" maxlength="255" required placeholder="Nama jabatan sesuai SK" style="margin-top:8px"><small>Pilihan mengikuti data ASN, riwayat jabatan, dan struktur OPD ini. Pilih Jabatan lainnya untuk menambah nama jabatan sesuai SK.</small></div>
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
<div class="ui modal" id="pimpinanModal"><i class="close icon"></i>
  <div class="header" id="pimpinanTitle">Data Pimpinan Wilayah</div>
  <div class="content"><form class="ui form" id="pimpinanForm" enctype="multipart/form-data">
    <input type="hidden" name="jenis_pimpinan">
    <div class="required field"><label>Nama Pejabat</label><input name="nama_pejabat" maxlength="255" required placeholder="Nama lengkap beserta gelar"></div>
    <div class="required field"><label>Nomor SK</label><input name="nomor_sk_pengangkatan" maxlength="150" required></div>
    <div class="two fields">
      <div class="required field"><label>Tanggal SK</label><input type="date" name="tanggal_sk_pengangkatan" required></div>
      <div class="required field"><label>TMT/Berlaku Mulai</label><input type="date" name="tmt_jabatan" required></div>
    </div>
    <div class="field"><label>File SK (PDF, maksimal 5 MB)</label><input type="file" name="file_sk" accept="application/pdf"></div>
    <div class="field"><label>Foto (JPG, PNG, WebP, maksimal 5 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></div>
    <div class="ui info message">File dan foto sebelumnya tetap digunakan jika tidak memilih unggahan baru.</div>
  </form></div>
  <div class="actions"><button class="ui deny button">Batal</button><button class="ui primary button" id="simpanPimpinan">Simpan</button></div>
</div>
<script>
(() => {
  const initStrukturOrganisasi = () => {
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
          if (!r?.success) { $('#strukturList').html('<div class="ui negative message">Struktur belum dapat dimuat.</div>'); return; }
          data = r.data;
          render();
          fill();
        },
        error: () => $('#strukturList').html('<div class="ui negative message">Struktur belum dapat dimuat. Muat ulang halaman untuk mencoba kembali.</div>')
      })
    }

    function fill() {
      const emp = $('#strukturForm [name="pegawai_id"]'),
        par = $('#strukturForm [name="parent_id"]');
      emp.html('<option value="">Pilih ASN</option>' + data.employees.map(x => `<option value="${x.id}">${esc(x.nama)} — ${esc(x.nip||'-')} (${esc(x.jabatan||'tanpa jabatan')})</option>`).join(''));
      par.html('<option value="">Tidak ada (pimpinan tertinggi)</option>' + data.rows.filter(x => !x.jenis_pimpinan).map(x => `<option value="${x.kd_opd}:${x.id}">${esc(x.nama_jabatan)} — ${esc(x.nama_pegawai||'-')} [${esc(x.kd_opd)}]</option>`).join(''));
      const presets=$('#strukturForm [name="nama_jabatan_preset"]'),selected=presets.val();
      presets.empty().append(new Option('Pilih jabatan dalam OPD',''));
      (data.jobs||[]).forEach(x=>presets.append(new Option(x,x)));
      presets.append(new Option('Jabatan lainnya...','__custom__'));
      presets.dropdown('refresh');
      if(selected)presets.dropdown('set selected',selected);
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
      const normalizeTitle = (name, fallback = 'Kepala OPD') => {
        const raw = String(name ?? '').trim();
        if (!raw) return fallback;
        const lower = raw.toLowerCase();
        return lower.includes('penanggung jawab') || lower.includes('penanggungjawab') || lower.includes('penanggungjawab /') ? fallback : raw;
      };
      const branch = (parentKey, depth = 0) => (byParent[parentKey] || []).map(x => {
        const title = normalizeTitle(x.nama_jabatan_display || x.nama_jabatan, 'Kepala OPD');
        const actions = x.jenis_pimpinan
          ? (x.can_manage ? `<button class="ui mini basic blue button edit-pimpinan" data-role="${esc(x.jenis_pimpinan)}"><i class="edit icon"></i>${x.nama_pegawai ? 'Ubah' : 'Isi data'}</button>` : '<span class="ui tiny basic label">Dikelola role daerah</span>')
          : (x.can_manage ? `<button class="ui mini basic blue icon button edit-struktur" data-id="${x.id}"><i class="edit icon"></i></button><button class="ui mini basic red icon button hapus-struktur" title="Akhiri masa berlaku" data-id="${x.id}" data-kd-opd="${esc(x.kd_opd)}"><i class="calendar times icon"></i></button>` : '');
        const photo = x.foto_url ? `<img src="${esc(x.foto_url)}" alt="Foto ${esc(title)}" style="width:64px;height:80px;object-fit:cover;border-radius:6px;float:left;margin:0 14px 8px 0">` : '';
        const sk = x.file_sk_url ? ` · <a href="${esc(x.file_sk_url)}" target="_blank" rel="noopener">Lihat file SK</a>` : '';
        return `<div class="ui segment" style="margin-left:${Math.min(depth,5)*28}px;border-left:4px solid ${depth?'#21ba45':'#2185d0'};overflow:auto"><div class="ui right floated buttons">${actions}</div>${photo}<div class="ui ${depth?'teal':'blue'} label">${x.jenis_pimpinan ? 'Pimpinan Wilayah' : esc(x.eselon||x.kelompok_jabatan||'Struktural')}</div> <b>${esc(title)}</b><div class="ui small header" style="margin:8px 0 2px">${esc(x.nama_pegawai||'Belum diisi')}</div><div class="meta">${x.jenis_pimpinan ? '' : `NIP ${esc(x.nip||'-')} · `}${esc(x.nomor_sk_pengangkatan||'SK belum diisi')} · Tanggal SK ${esc(x.tanggal_sk_pengangkatan||'-')} · TMT ${esc(x.tmt_jabatan||x.berlaku_mulai||'-')}${sk}</div></div>${branch(x.struktur_key,depth+1)}`;
      }).join('');
      const roots = [...new Set(data.rows.map(x => x.parent_key).filter(key => !keys.has(key)))];
      $('#strukturList').html(roots.map(key => branch(key)).join('') || '<div class="ui warning message">Belum ada struktur jabatan pada scope ini.</div>');
    }
    $(document).off('.struktur').on('click.struktur', '#susunOtomatis', function() {
      const button=$(this).addClass('loading disabled');
      Ajax.request({url:'/kepegawaian/struktur/automate',method:'GET',success:r=>{
        if(!r?.success)return;
        const rows=r.data.rows, names=new Map(rows.map(x=>[x.employee_id,x.name]));
        const labels={existing:'Sudah ada',new:'Akan ditambahkan',conflict:'Jabatan sudah terisi',missing_tmt:'TMT belum tersedia',ambiguous_parent:'Atasan perlu dipilih',duplicate_job:'Pejabat ganda; periksa riwayat'};
        $('#strukturAutoPreview').html('<table class="ui compact celled table"><thead><tr><th>Nama ASN / Jabatan</th><th>Atasan</th><th>Status</th></tr></thead><tbody>'+rows.map(x=>`<tr><td><b>${esc(x.name)}</b><br>${esc(x.job)}</td><td>${esc(names.get(x.parent_employee_id)||'Pimpinan OPD')}</td><td>${esc(labels[x.status])}${x.review.length?'<br><small>'+esc(x.review.join('; '))+'</small>':''}</td></tr>`).join('')+'</tbody></table><div class="ui small message">'+r.data.skipped+' jabatan nonstruktural tidak dimasukkan dalam bagan struktural.</div>');
        $('#terapkanStruktur').toggleClass('disabled',!rows.some(x=>x.status==='new'));
        $('#strukturAutoModal').modal('show');
      },complete:()=>button.removeClass('loading disabled')});
    }).on('click.struktur','#terapkanStruktur',function(){
      const button=$(this);if(button.hasClass('disabled'))return;button.addClass('loading disabled');
      Ajax.request({url:'/kepegawaian/struktur/automate',method:'POST',data:{},success:r=>{if(r?.success){$('#strukturAutoModal').modal('hide');load();}},complete:()=>button.removeClass('loading disabled')});
    }).on('change.struktur', '#strukturForm [name="parent_id"]', function() {
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
      $('#strukturForm [name="nama_jabatan_preset"]').val('').trigger('change');
      fill();
      Object.entries(x).forEach(([k, v]) => $('#strukturForm [name="' + k + '"]').val(v ?? '').trigger('change'));
      $('#strukturForm [name="parent_id"]').val(x.parent_id ? `${x.parent_kd_opd || x.kd_opd}:${x.parent_id}` : '').trigger('change');
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
    $(document).on('click.struktur', '.edit-pimpinan', function() {
      const x = data.rows.find(row => row.jenis_pimpinan === $(this).data('role'));
      if (!x?.can_manage) return;
      const form = $('#pimpinanForm')[0];
      form.reset();
      ['jenis_pimpinan','nomor_sk_pengangkatan','tanggal_sk_pengangkatan','tmt_jabatan'].forEach(key => $(form).find(`[name="${key}"]`).val(x[key] || ''));
      $(form).find('[name="nama_pejabat"]').val(x.nama_pegawai || '');
      $('#pimpinanTitle').text(x.nama_jabatan);
      $('#pimpinanModal').modal('show');
    }).on('click.struktur', '#simpanPimpinan', function() {
      const form = $('#pimpinanForm')[0];
      if (!form.reportValidity()) return;
      const button = $(this);
      button.addClass('loading disabled');
      Ajax.request({url:'/kepegawaian/struktur/save', method:'POST', data:new FormData(form), processData:false, contentType:false,
        success:r => { if (r?.success) { $('#pimpinanModal').modal('hide'); load(); } },
        complete:() => button.removeClass('loading disabled')
      });
    });
    $('#pimpinanForm').on('submit', function(event) { event.preventDefault(); $('#simpanPimpinan').trigger('click'); });
    load();
  };
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initStrukturOrganisasi, {once:true});
  else initStrukturOrganisasi();
})();
</script>
