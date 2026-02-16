<form class="ui form">

  <!-- ================= INFORMASI DASAR ================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Informasi Dasar</h4>

    <div class="two fields">
      <div class="field">
        <label>Kode Wilayah</label>
        <input type="text" name="kd_wilayah" placeholder="Kode Wilayah">
      </div>

      <div class="field">
        <label>Tahun</label>
        <input type="number" name="tahun" placeholder="Tahun">
      </div>
    </div>
  </div>

  <!-- ================= DROPDOWN REFERENSI ================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Pengaturan Referensi</h4>

    <div class="four fields">

      <!-- Tahun Renstra -->
      <div class="field">
        <label>Tahun Renstra</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="tahun_renstra">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Tahun Renstra</div>
          <div class="menu">
            <div class="item" data-value="2025-2029">2025-2029</div>
            <div class="item" data-value="2020-2024">2020-2024</div>
          </div>
        </div>
      </div>

      <!-- Aturan Anggaran -->
      <div class="field">
        <label>Aturan Anggaran</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_anggaran">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Anggaran</div>
          <div class="menu">
            <div class="item" data-value="permendagri_77">Permendagri 77/2020</div>
          </div>
        </div>
      </div>

      <!-- Aturan Organisasi -->
      <div class="field">
        <label>Aturan Organisasi</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_organisasi">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Organisasi</div>
          <div class="menu">
            <div class="item" data-value="perda">Perda Organisasi</div>
          </div>
        </div>
      </div>

      <!-- Aturan Pengadaan -->
      <div class="field">
        <label>Aturan Pengadaan</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_pengadaan">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Pengadaan</div>
          <div class="menu">
            <div class="item" data-value="perpres_12">Perpres 12/2021</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Baris kedua dropdown -->
    <div class="four fields">

      <div class="field">
        <label>Aturan Akun</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_akun">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Akun</div>
          <div class="menu">
            <div class="item" data-value="akun_2024">Akun 2024</div>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Aturan ASB</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_asb">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan ASB</div>
          <div class="menu">
            <div class="item" data-value="asb_2024">ASB 2024</div>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Aturan SBU</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_sbu">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan SBU</div>
          <div class="menu">
            <div class="item" data-value="sbu_2024">SBU 2024</div>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Aturan SSH</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_ssh">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan SSH</div>
          <div class="menu">
            <div class="item" data-value="ssh_2024">SSH 2024</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= PERIODE ================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Periode Dokumen</h4>

    <div class="four fields">

      <div class="field">
        <label>Awal Renja</label>
        <div class="ui calendar" id="awal_renja">
          <div class="ui input left icon">
            <i class="calendar icon"></i>
            <input type="text" name="awal_renja">
          </div>
        </div>
      </div>

      <div class="field">
        <label>Akhir Renja</label>
        <div class="ui calendar" id="akhir_renja">
          <div class="ui input left icon">
            <i class="calendar icon"></i>
            <input type="text" name="akhir_renja">
          </div>
        </div>
      </div>

      <div class="field">
        <label>Awal DPA</label>
        <div class="ui calendar" id="awal_dpa">
          <div class="ui input left icon">
            <i class="calendar icon"></i>
            <input type="text" name="awal_dpa">
          </div>
        </div>
      </div>

      <div class="field">
        <label>Akhir DPA</label>
        <div class="ui calendar" id="akhir_dpa">
          <div class="ui input left icon">
            <i class="calendar icon"></i>
            <input type="text" name="akhir_dpa">
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= KONTROL GLOBAL ================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Kontrol Global</h4>

    <div class="ui three column grid">
      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="disable">
          <label>Disable Sistem</label>
        </div>
      </div>

      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="kunci">
          <label>Kunci Global</label>
        </div>
      </div>

      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="setujui">
          <label>Setujui Global</label>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= TOMBOL ================= -->
  <div class="ui right aligned segment">
    <button type="submit" class="ui primary button">
      <i class="save icon"></i> Simpan
    </button>
    <button type="reset" class="ui button">
      Reset
    </button>
  </div>

</form>