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
                    <div class="menu">
                        <a class="item" href="/wallchat" data-spa="server"><i class="circular comments outline icon"></i>Pesan</a>
                        <a class="item" id="darkToggle"><i class="circular moon icon"></i>Change Themes</a>
                        <a class="item" href="/profil" data-spa="server"><i class="circular qrcode icon"></i>Profil &amp; Pengaturan</a>
                        <a class="item" href="/logout" id="btnLogout"><i class="circular sign out alternate icon"></i>Log Out</a>
                      
                    </div>
                </div>
            </div>
        </div>
    </div>
