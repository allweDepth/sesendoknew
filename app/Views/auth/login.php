<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login SIPD</title>

<link rel="stylesheet" href="/assets/css/fomantic.min.css">

</head>
<body>

<div class="ui middle aligned center aligned grid" style="height:100vh">
    <div class="column" style="max-width:400px">

        <form class="ui large form" method="POST" action="/login/proses">
            <div class="ui segment">

                <h2 class="ui teal header">
                    Login SIPD
                </h2>

                <div class="field">
                    <div class="ui left icon input">
                        <i class="user icon"></i>
                        <input type="text" name="username" placeholder="Username">
                    </div>
                </div>

                <div class="field">
                    <div class="ui left icon input">
                        <i class="lock icon"></i>
                        <input type="password" name="password" placeholder="Password">
                    </div>
                </div>

                <button class="ui fluid teal button">
                    Login
                </button>

            </div>
        </form>

    </div>
</div>

<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/fomantic.min.js"></script>

<script>
$('.ui.form').form({
    fields: {
        username: 'empty',
        password: 'empty'
    }
});
</script>
<?php if(isset($_SESSION['error'])): ?>
<script>
  $('body').toast({
    class: 'error',
    message: '<?= $_SESSION['error']; ?>'
  });
</script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>
