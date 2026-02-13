<!-- resources/views/auth/register.php -->

<div class="ui middle aligned center aligned grid" style="min-height: 100vh; margin: 0;">
    <div class="column" style="max-width: 500px;">
        <h2 class="ui teal image header">
            <div class="content">
                Buat Akun Baru di seSendok
            </div>
        </h2>

        <form class="ui large form error" method="POST" action="/auth/register">
            <div class="ui stacked segment">

                <div class="field">
                    <label>Nama Lengkap</label>
                    <div class="ui left icon input">
                        <input type="text" name="nama" placeholder="Nama Lengkap" required>
                        <i class="user icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Username</label>
                    <div class="ui left icon input">
                        <input type="text" name="username" placeholder="Username (untuk login)" required minlength="4">
                        <i class="at icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Email</label>
                    <div class="ui left icon input">
                        <input type="email" name="email" placeholder="Alamat Email" required>
                        <i class="mail icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Password</label>
                    <div class="ui left icon input">
                        <input type="password" name="password" placeholder="Password minimal 8 karakter" required minlength="8">
                        <i class="lock icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Konfirmasi Password</label>
                    <div class="ui left icon input">
                        <input type="password" name="password_confirmation" placeholder="Ulangi Password" required>
                        <i class="lock icon"></i>
                    </div>
                </div>

                <!-- Field opsional yang sering diisi saat register -->
                <div class="two fields">
                    <div class="field">
                        <label>Kode Organisasi / OPD (opsional)</label>
                        <input type="text" name="kd_organisasi" placeholder="Contoh: 1.03.0.00.0.00.01.0000">
                    </div>

                    <div class="field">
                        <label>Nama Organisasi / OPD (opsional)</label>
                        <input type="text" name="nama_org" placeholder="Contoh: Dinas Pekerjaan Umum">
                    </div>
                </div>

                <div class="field">
                    <label>Kode Wilayah (opsional)</label>
                    <div class="ui left icon input">
                        <input type="text" name="kd_wilayah" placeholder="Contoh: 76.01">
                        <i class="map marker alternate icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Kontak Person / No. HP (opsional)</label>
                    <div class="ui left icon input">
                        <input type="tel" name="kontak_person" placeholder="0812xxxxxxx">
                        <i class="phone icon"></i>
                    </div>
                </div>

                <div class="ui error message"></div>

                <button class="ui fluid large teal submit button" type="submit">
                    <i class="signup icon"></i> Daftar Sekarang
                </button>
            </div>

            <div class="ui message">
                Sudah punya akun? <a href="#" id="switch-to-login">Login di sini</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Optional: client-side validation sederhana menggunakan Fomantic-UI
    $('.ui.form')
        .form({
            fields: {
                nama: 'empty',
                username: ['minLength[4]', 'empty'],
                email: ['email', 'empty'],
                password: ['minLength[8]', 'empty'],
                password_confirmation: ['match[password]', 'empty']
            }
        });

    // Jika ingin switch ke modal login (jika halaman utama pakai modal)
    $('#switch-to-login').on('click', function(e) {
        e.preventDefault();
        $('#modal-register').modal('hide');
        $('#modal-login').modal('show');
    });
</script>