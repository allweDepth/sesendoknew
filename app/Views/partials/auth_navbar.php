    <div class="ui top attached menu workspace-toolbar">
        <div class="item workspace-controls">
            <button type="button" class="ui icon button" id="toggleSidebar" aria-label="Navigasi ruang kerja" aria-controls="workspaceNavigation" aria-expanded="false" title="Navigasi ruang kerja"><i class="bars icon" aria-hidden="true"></i></button>
            <a class="workspace-toolbar-brand" href="<?= app_url('/dashboard') ?>" data-spa="server">seSendok</a>
        </div>
        <div class="right menu">
            <div class="ui inline item dropdown" id="countRow"><span><i class="list icon"></i></span><input type="hidden" name="countRow" value="5">
                <div class="text">5</div>
                <div class="menu">
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
            <div class="item workspace-table-search">
                <div class="ui cari_data transparent icon input">
                    <input type="search" maxlength="100" autocomplete="off" placeholder="Pencarian tidak tersedia" name="cari_data" id="cari_data" disabled aria-label="Cari data pada tabel" aria-disabled="true">
                    <i class="search link icon"></i>
                </div>
            </div>
            <div class="item workspace-table-sort">
                <button type="button" class="ui basic icon button" id="clearTableSort" disabled aria-disabled="true" aria-label="Bersihkan sortir tabel" title="Bersihkan sortir tabel">
                    <i class="undo alternate icon"></i>
                </button>
            </div>
            <div class="right menu workspace-account-menu">
                <div class="ui dropdown item" id="userMenu" aria-label="Menu pengguna"><span class="workspace-avatar"><i class="user icon" aria-hidden="true"></i></span><span class="workspace-account-label">Akun</span><i class="dropdown icon"></i>
                    <div class="menu">
                        <a class="item" id="userMenuMessages" href="/wallchat" data-spa="server" data-title="Wallchat"><i class="circular comments outline icon"></i>Pesan</a>
                        <a class="item" id="darkToggle" role="button" tabindex="0" aria-pressed="false"><i class="circular moon icon" aria-hidden="true"></i><span>Mode gelap</span><span class="workspace-switch" aria-hidden="true"></span></a>
                        <button type="button" class="item workspace-theme-trigger" data-workspace-theme aria-haspopup="dialog"><i class="circular paint brush icon" aria-hidden="true"></i>Pilih tema</button>
                        <a class="item" id="userMenuProfile" href="/profil" data-spa="server" data-title="Profil &amp; Pengaturan"><i class="circular qrcode icon"></i>Profil &amp; Pengaturan</a>
                        <form method="POST" action="<?= app_url('/logout') ?>" style="margin:0"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="item" id="btnLogout" style="border:0;background:transparent;width:100%;text-align:left"><i class="circular sign out alternate icon"></i>Keluar</button></form>
                      
                    </div>
                </div>
            </div>
        </div>
    </div>
