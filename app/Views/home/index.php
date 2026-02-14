<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/assets/css/fomantic.min.css">
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/fomantic.min.js"></script>
</head>
<body>

<div class="ui secondary menu">
    <div class="left menu">
        <div class="ui dropdown item">
            Account
            <i class="dropdown icon"></i>
            <div class="menu">
                <div class="item" id="btnLogin">Login</div>
                <div class="item" id="btnRegister">Register</div>
            </div>
        </div>
    </div>
</div>

<div class="ui container">
    <h2 class="ui header">Berita</h2>
    <p>Isi berita...</p>

    <h2 class="ui header">Organisasi</h2>
    <p>Isi organisasi...</p>

    <h2 class="ui header">Pelayanan</h2>
    <p>Isi pelayanan...</p>
</div>

<!-- LOGIN MODAL -->
<div class="ui modal" id="loginModal">
    <div class="header">Login</div>
    <div class="content">
        <form class="ui form" method="POST" action="/login/proses">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password">
            </div>
            <button class="ui primary button">Login</button>
        </form>
    </div>
</div>

<script>
$('.ui.dropdown').dropdown();

$('#btnLogin').click(function(){
    $('#loginModal').modal('show');
});
</script>

</body>
</html>